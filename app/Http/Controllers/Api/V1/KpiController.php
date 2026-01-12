<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\GreenhouseProductionCycle;
use App\Models\BellPepperHarvest;
use App\Models\ProductionCycleDailyLog;
use App\Models\ProductionCycleAlert;
use App\Models\ProductionCycleHarvestRecord;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KpiController extends Controller
{
    public function salesSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('ADMIN') && !$user->can('kpis.view')) {
            abort(403, 'Unauthorized');
        }
        
        $user = $request->user();
        $farmIds = [];
        
        if (!$user->hasRole('ADMIN')) {
            $farmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($farmIds)) {
                return response()->json(['data' => []]);
            }
        } elseif ($request->has('farm_id')) {
            $farmIds = [(int)$request->farm_id];
        }
        
        $from = $request->input('from', now()->subMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $groupBy = $request->input('group_by', 'day'); // day, week, month
        
        $query = SalesOrder::whereBetween('order_date', [$from, $to]);
        
        if (!empty($farmIds)) {
            $query->whereIn('farm_id', $farmIds);
        }
        
        // Total revenue (all statuses except CANCELLED)
        $totalRevenue = (float)$query->clone()
            ->where('status', '!=', 'CANCELLED')
            ->sum('total_amount');
        
        // Paid revenue (from payments)
        $paidRevenue = (float)DB::table('payments')
            ->join('sales_orders', 'payments.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.order_date', [$from, $to])
            ->where('sales_orders.status', '!=', 'CANCELLED')
            ->when(!empty($farmIds), function ($q) use ($farmIds) {
                $q->whereIn('sales_orders.farm_id', $farmIds);
            })
            ->sum('payments.amount');
        
        $outstandingRevenue = $totalRevenue - $paidRevenue;
        
        // Number of orders
        $numberOfOrders = $query->clone()->where('status', '!=', 'CANCELLED')->count();
        $averageOrderValue = $numberOfOrders > 0 ? $totalRevenue / $numberOfOrders : 0;
        
        // Top customers
        $topCustomers = DB::table('sales_orders')
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereBetween('sales_orders.order_date', [$from, $to])
            ->where('sales_orders.status', '!=', 'CANCELLED')
            ->when(!empty($farmIds), function ($q) use ($farmIds) {
                $q->whereIn('sales_orders.farm_id', $farmIds);
            })
            ->select('customers.id', 'customers.name', DB::raw('SUM(sales_orders.total_amount) as revenue'))
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();
        
        // Revenue by product (if products exist)
        $revenueByProduct = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
            ->whereBetween('sales_orders.order_date', [$from, $to])
            ->where('sales_orders.status', '!=', 'CANCELLED')
            ->when(!empty($farmIds), function ($q) use ($farmIds) {
                $q->whereIn('sales_orders.farm_id', $farmIds);
            })
            ->select(
                DB::raw('COALESCE(products.name, sales_order_items.product_name, sales_order_items.product_description) as product_name'),
                DB::raw('SUM(sales_order_items.line_total) as revenue')
            )
            ->groupBy('product_name')
            ->orderBy('revenue', 'desc')
            ->get();
        
        // Revenue by site
        $revenueBySite = DB::table('sales_orders')
            ->leftJoin('sites', 'sales_orders.site_id', '=', 'sites.id')
            ->whereBetween('sales_orders.order_date', [$from, $to])
            ->where('sales_orders.status', '!=', 'CANCELLED')
            ->when(!empty($farmIds), function ($q) use ($farmIds) {
                $q->whereIn('sales_orders.farm_id', $farmIds);
            })
            ->select(
                'sites.id',
                'sites.name',
                DB::raw('SUM(sales_orders.total_amount) as revenue')
            )
            ->groupBy('sites.id', 'sites.name')
            ->orderBy('revenue', 'desc')
            ->get();
        
        return response()->json([
            'data' => [
                'total_revenue' => $totalRevenue,
                'paid_revenue' => $paidRevenue,
                'outstanding_revenue' => $outstandingRevenue,
                'number_of_orders' => $numberOfOrders,
                'average_order_value' => round($averageOrderValue, 2),
                'top_customers' => $topCustomers,
                'revenue_by_product' => $revenueByProduct,
                'revenue_by_site' => $revenueBySite,
            ],
        ]);
    }

    public function productionProfitability(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('ADMIN') && !$user->can('kpis.view')) {
            abort(403, 'Unauthorized');
        }
        
        $user = $request->user();
        $farmIds = [];
        
        if (!$user->hasRole('ADMIN')) {
            $farmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($farmIds)) {
                return response()->json(['data' => []]);
            }
        } elseif ($request->has('farm_id')) {
            $farmIds = [(int)$request->farm_id];
        }
        
        $from = $request->input('from');
        $to = $request->input('to');
        $greenhouseId = $request->input('greenhouse_id');
        
        $query = GreenhouseProductionCycle::with('greenhouse');
        
        if (!empty($farmIds)) {
            $query->whereIn('farm_id', $farmIds);
        }
        
        if ($greenhouseId) {
            $query->where('greenhouse_id', $greenhouseId);
        }
        
        if ($from) {
            $query->where('planting_date', '>=', $from);
        }
        
        if ($to) {
            $query->where('planting_date', '<=', $to);
        }
        
        $cycles = $query->get();
        
        $results = $cycles->map(function ($cycle) {
            // Get sales revenue linked to this cycle
            $salesData = SalesOrderItem::where('production_cycle_id', $cycle->id)
                ->select(
                    DB::raw('SUM(line_total) as total_sales_revenue'),
                    DB::raw('SUM(quantity) as total_quantity_sold'),
                    DB::raw('AVG(unit_price) as avg_selling_price_per_unit')
                )
                ->first();
            
            $totalSalesRevenue = (float)($salesData->total_sales_revenue ?? 0);
            $totalQuantitySold = (float)($salesData->total_quantity_sold ?? 0);
            $avgSellingPrice = $totalQuantitySold > 0 ? (float)($salesData->avg_selling_price_per_unit ?? 0) : 0;
            
            // Get harvest quantity from production cycle harvest records
            $harvestQuantityTotal = (float)ProductionCycleHarvestRecord::where('production_cycle_id', $cycle->id)
                ->where('status', '!=', 'DRAFT')
                ->sum('total_weight_kg_total');
            
            // Fallback to old BellPepperHarvest if no new records exist
            if ($harvestQuantityTotal == 0) {
                $harvestQuantity = (float)BellPepperHarvest::where('bell_pepper_cycle_id', $cycle->id)
                    ->sum('weight_kg');
                $harvestQuantityTotal = $harvestQuantity;
            }
            
            $sellThroughRate = $harvestQuantityTotal > 0 
                ? ($totalQuantitySold / $harvestQuantityTotal) * 100 
                : 0;
            
            $wasteOrUnallocatedQty = max(0, $harvestQuantityTotal - $totalQuantitySold);
            
            // Get target yield from cycle
            $targetTotalYield = $cycle->target_total_yield_kg ? (float)$cycle->target_total_yield_kg : null;
            $yieldVarianceKg = $targetTotalYield !== null ? $harvestQuantityTotal - $targetTotalYield : null;
            $yieldVariancePct = ($targetTotalYield !== null && $targetTotalYield > 0) 
                ? ($yieldVarianceKg / $targetTotalYield) * 100 
                : null;
            
            // Get grade breakdown from harvest records
            $harvestRecords = ProductionCycleHarvestRecord::where('production_cycle_id', $cycle->id)
                ->where('status', '!=', 'DRAFT')
                ->get();
            
            $gradeA = (float)$harvestRecords->sum('total_weight_kg_a');
            $gradeB = (float)$harvestRecords->sum('total_weight_kg_b');
            $gradeC = (float)$harvestRecords->sum('total_weight_kg_c');
            $totalHarvested = $gradeA + $gradeB + $gradeC;
            
            $gradeMixActual = $totalHarvested > 0 ? [
                'a_pct' => round(($gradeA / $totalHarvested) * 100, 2),
                'b_pct' => round(($gradeB / $totalHarvested) * 100, 2),
                'c_pct' => round(($gradeC / $totalHarvested) * 100, 2),
            ] : [
                'a_pct' => 0,
                'b_pct' => 0,
                'c_pct' => 0,
            ];
            
            return [
                'production_cycle_id' => $cycle->id,
                'production_cycle_code' => $cycle->production_cycle_code,
                'greenhouse' => [
                    'id' => $cycle->greenhouse?->id,
                    'name' => $cycle->greenhouse?->name,
                ],
                'target_total_yield_kg' => $targetTotalYield,
                'actual_total_yield_kg' => round($harvestQuantityTotal, 2),
                'yield_variance_kg' => $yieldVarianceKg !== null ? round($yieldVarianceKg, 2) : null,
                'yield_variance_pct' => $yieldVariancePct !== null ? round($yieldVariancePct, 2) : null,
                'grade_mix_actual' => $gradeMixActual,
                'grade_totals' => [
                    'a_kg' => round($gradeA, 2),
                    'b_kg' => round($gradeB, 2),
                    'c_kg' => round($gradeC, 2),
                ],
                'total_sales_revenue' => round($totalSalesRevenue, 2),
                'total_quantity_sold' => round($totalQuantitySold, 2),
                'avg_selling_price_per_unit' => round($avgSellingPrice, 2),
                'harvest_quantity_total' => round($harvestQuantityTotal, 2),
                'sell_through_rate' => round($sellThroughRate, 2),
                'waste_or_unallocated_qty' => round($wasteOrUnallocatedQty, 2),
            ];
        });
        
        return response()->json(['data' => $results]);
    }

    public function operationsCompliance(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('ADMIN') && !$user->can('kpis.view')) {
            abort(403, 'Unauthorized');
        }
        
        $user = $request->user();
        $farmIds = [];
        
        if (!$user->hasRole('ADMIN')) {
            $farmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($farmIds)) {
                return response()->json(['data' => []]);
            }
        } elseif ($request->has('farm_id')) {
            $farmIds = [(int)$request->farm_id];
        }
        
        $from = $request->input('from', now()->subMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        
        // Get all farms with their timezones
        $farms = Farm::when(!empty($farmIds), function ($q) use ($farmIds) {
            $q->whereIn('id', $farmIds);
        })->get();
        
        $totalExpectedLogs = 0;
        $totalSubmittedLogs = 0;
        $totalOnTimeSubmissions = 0;
        $missingLogCount = 0;
        
        foreach ($farms as $farm) {
            $timezone = $farm->default_timezone ?? config('app.timezone');
            $cutoffTime = $farm->daily_log_cutoff_time ?? '18:00:00';
            
            // Get active cycles in date range
            $cycles = GreenhouseProductionCycle::where('farm_id', $farm->id)
                ->whereIn('cycle_status', ['ACTIVE', 'HARVESTING'])
                ->where(function ($q) use ($from, $to) {
                    $q->where(function ($q2) use ($from, $to) {
                        $q2->where('planting_date', '<=', $to)
                           ->where(function ($q3) use ($from) {
                               $q3->whereNull('ended_at')
                                  ->orWhere('ended_at', '>=', $from);
                           });
                    });
                })
                ->get();
            
            foreach ($cycles as $cycle) {
                $cycleStart = Carbon::parse($cycle->planting_date, $timezone);
                $cycleEnd = $cycle->ended_at 
                    ? Carbon::parse($cycle->ended_at, $timezone)
                    : Carbon::parse($to, $timezone);
                
                $effectiveStart = $cycleStart->gt(Carbon::parse($from, $timezone)) 
                    ? $cycleStart 
                    : Carbon::parse($from, $timezone);
                $effectiveEnd = $cycleEnd->lt(Carbon::parse($to, $timezone)) 
                    ? $cycleEnd 
                    : Carbon::parse($to, $timezone);
                
                // Count days in range
                $daysInRange = $effectiveStart->diffInDays($effectiveEnd) + 1;
                $totalExpectedLogs += $daysInRange;
                
                // Count submitted logs
                $submittedLogs = ProductionCycleDailyLog::where('production_cycle_id', $cycle->id)
                    ->where('status', 'SUBMITTED')
                    ->whereBetween('log_date', [$effectiveStart->toDateString(), $effectiveEnd->toDateString()])
                    ->count();
                
                $totalSubmittedLogs += $submittedLogs;
                
                // Count on-time submissions (before cutoff)
                $onTimeLogs = ProductionCycleDailyLog::where('production_cycle_id', $cycle->id)
                    ->where('status', 'SUBMITTED')
                    ->whereBetween('log_date', [$effectiveStart->toDateString(), $effectiveEnd->toDateString()])
                    ->get()
                    ->filter(function ($log) use ($farm, $timezone, $cutoffTime) {
                        $logDate = Carbon::parse($log->log_date, $timezone);
                        $cutoff = Carbon::parse($logDate->format('Y-m-d') . ' ' . $cutoffTime, $timezone);
                        $submittedAt = Carbon::parse($log->submitted_at, $timezone);
                        return $submittedAt->lte($cutoff);
                    })
                    ->count();
                
                $totalOnTimeSubmissions += $onTimeLogs;
            }
            
            // Count missing log alerts
            $missingLogCount += ProductionCycleAlert::where('farm_id', $farm->id)
                ->where('alert_type', 'MISSING_DAILY_LOG')
                ->whereBetween('log_date', [$from, $to])
                ->where('is_resolved', false)
                ->count();
        }
        
        $complianceRate = $totalExpectedLogs > 0 
            ? ($totalSubmittedLogs / $totalExpectedLogs) * 100 
            : 0;
        
        $onTimeRate = $totalSubmittedLogs > 0 
            ? ($totalOnTimeSubmissions / $totalSubmittedLogs) * 100 
            : 0;
        
        // Alerts count by type
        $alertsByType = ProductionCycleAlert::when(!empty($farmIds), function ($q) use ($farmIds) {
                $q->whereIn('farm_id', $farmIds);
            })
            ->whereBetween('log_date', [$from, $to])
            ->where('is_resolved', false)
            ->select('alert_type', DB::raw('COUNT(*) as count'))
            ->groupBy('alert_type')
            ->get()
            ->pluck('count', 'alert_type');
        
        return response()->json([
            'data' => [
                'daily_log_compliance_rate' => round($complianceRate, 2),
                'expected_logs' => $totalExpectedLogs,
                'submitted_logs' => $totalSubmittedLogs,
                'missing_log_count' => $missingLogCount,
                'on_time_submission_rate' => round($onTimeRate, 2),
                'on_time_submissions' => $totalOnTimeSubmissions,
                'alerts_count_by_type' => $alertsByType,
            ],
        ]);
    }
}
