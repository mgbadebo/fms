<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GariSale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GariSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GariSale::with(['farm', 'customer']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate(20);
        return response()->json($sales);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'sale_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_type' => 'required|in:RETAIL,BULK_BUYER,DISTRIBUTOR,CATERING,HOTEL,OTHER',
            'gari_type' => 'required|in:WHITE,YELLOW',
            'gari_grade' => 'required|in:FINE,COARSE,MIXED',
            'packaging_type' => 'required|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'required|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|default:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:CASH,TRANSFER,POS,CHEQUE,CREDIT',
            'amount_paid' => 'nullable|numeric|min:0|default:0',
            'sales_channel' => 'nullable|string|max:255',
            'sales_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Generate sale code
        $validated['sale_code'] = 'SALE-' . strtoupper(Str::random(8));

        // Calculate amounts
        $validated['total_amount'] = $validated['quantity_kg'] * $validated['unit_price'];
        $validated['final_amount'] = $validated['total_amount'] - ($validated['discount'] ?? 0);

        $sale = GariSale::create($validated);
        
        // Calculate margins and payment
        $sale->calculateMargins();
        $sale->calculatePayment();
        $sale->save();

        return response()->json(['data' => $sale->load('farm', 'customer')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $sale = GariSale::with(['farm', 'customer'])->findOrFail($id);
        return response()->json(['data' => $sale]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $sale = GariSale::findOrFail($id);

        $validated = $request->validate([
            'sale_date' => 'sometimes|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_type' => 'sometimes|in:RETAIL,BULK_BUYER,DISTRIBUTOR,CATERING,HOTEL,OTHER',
            'gari_type' => 'sometimes|in:WHITE,YELLOW',
            'gari_grade' => 'sometimes|in:FINE,COARSE,MIXED',
            'packaging_type' => 'sometimes|in:1KG_POUCH,2KG_POUCH,5KG_PACK,50KG_BAG,BULK',
            'quantity_kg' => 'sometimes|numeric|min:0',
            'quantity_units' => 'nullable|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'payment_method' => 'sometimes|in:CASH,TRANSFER,POS,CHEQUE,CREDIT',
            'amount_paid' => 'nullable|numeric|min:0',
            'sales_channel' => 'nullable|string|max:255',
            'sales_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Recalculate amounts if needed
        if (isset($validated['quantity_kg']) && isset($validated['unit_price'])) {
            $validated['total_amount'] = $validated['quantity_kg'] * $validated['unit_price'];
            $validated['final_amount'] = $validated['total_amount'] - ($validated['discount'] ?? 0);
        }

        $sale->update($validated);
        
        // Recalculate margins and payment
        $sale->calculateMargins();
        $sale->calculatePayment();
        $sale->save();

        return response()->json(['data' => $sale->load('farm', 'customer')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $sale = GariSale::findOrFail($id);
        $sale->delete();

        return response()->json(null, 204);
    }

    // Get sales summary/analytics
    public function summary(Request $request): JsonResponse
    {
        $query = GariSale::query();

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        $summary = $query->selectRaw('
            customer_type,
            packaging_type,
            SUM(quantity_kg) as total_kg_sold,
            SUM(final_amount) as total_revenue,
            SUM(total_cost) as total_cost,
            SUM(gross_margin) as total_margin,
            AVG(unit_price) as avg_price_per_kg,
            COUNT(*) as total_sales
        ')
        ->groupBy('customer_type', 'packaging_type')
        ->get();

        return response()->json(['data' => $summary]);
    }
}

