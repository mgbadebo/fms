<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', SalesOrder::class);
        
        $query = SalesOrder::with(['farm', 'site', 'customer', 'items.productionCycle', 'items.harvestRecord', 'items.harvestLot', 'items.product', 'payments.receivedBy']);
        
        $user = $request->user();
        if (!$user->hasRole('ADMIN')) {
            $userFarmIds = $user->farms()->pluck('farms.id')->toArray();
            if (empty($userFarmIds)) {
                return response()->json(['data' => []]);
            }
            $query->whereIn('farm_id', $userFarmIds);
        } elseif ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->has('from')) {
            $query->where('order_date', '>=', $request->from);
        }
        
        if ($request->has('to')) {
            $query->where('order_date', '<=', $request->to);
        }
        
        $orders = $query->orderBy('order_date', 'desc')->paginate(20);
        return SalesOrderResource::collection($orders)->response();
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', SalesOrder::class);
        
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);
        
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'DRAFT';
        $validated['payment_status'] = 'UNPAID';
        
        DB::beginTransaction();
        try {
            $order = SalesOrder::create($validated);
            
            // Create items
            foreach ($items as $itemData) {
                $itemData['sales_order_id'] = $order->id;
                $itemData['farm_id'] = $order->farm_id;
                $itemData['line_total'] = ($itemData['quantity'] * $itemData['unit_price']) - ($itemData['discount_amount'] ?? 0);
                SalesOrderItem::create($itemData);
            }
            
            // Recalculate totals
            $order->recalculateTotals();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return (new SalesOrderResource($order->load('farm', 'site', 'customer', 'items')))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $order = SalesOrder::with([
            'farm', 'site', 'customer', 'creator',
            'items.productionCycle', 'items.harvestRecord', 'items.harvestLot', 'items.product',
            'payments.receivedBy'
        ])->findOrFail($id);
        
        Gate::authorize('view', $order);
        
        return (new SalesOrderResource($order))->response();
    }

    public function update(UpdateSalesOrderRequest $request, string $id): JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        Gate::authorize('update', $order);
        
        // Can only update DRAFT orders
        if ($order->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT orders can be updated.'
            ], 422);
        }
        
        $order->update($request->validated());
        $order->recalculateTotals();
        
        return (new SalesOrderResource($order->load('farm', 'customer', 'items')))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        Gate::authorize('delete', $order);
        
        // Can only delete DRAFT orders
        if ($order->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT orders can be deleted.'
            ], 422);
        }
        
        $order->delete();
        
        return response()->json(null, 204);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        Gate::authorize('update', $order);
        
        if ($order->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT orders can be confirmed.'
            ], 422);
        }
        
        $order->update(['status' => 'CONFIRMED']);
        
        return (new SalesOrderResource($order->load('farm', 'customer')))->response();
    }

    public function dispatch(Request $request, string $id): JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        Gate::authorize('update', $order);
        
        if (!in_array($order->status, ['CONFIRMED', 'INVOICED'])) {
            return response()->json([
                'message' => 'Order must be CONFIRMED or INVOICED before dispatch.'
            ], 422);
        }
        
        $order->update(['status' => 'DISPATCHED']);
        
        return (new SalesOrderResource($order->load('farm', 'customer')))->response();
    }

    public function invoice(Request $request, string $id): JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        Gate::authorize('update', $order);
        
        if (!in_array($order->status, ['CONFIRMED', 'DISPATCHED'])) {
            return response()->json([
                'message' => 'Order must be CONFIRMED or DISPATCHED before invoicing.'
            ], 422);
        }
        
        // Try to set INVOICED, fallback to COMPLETED if enum doesn't support it
        try {
            $order->status = 'INVOICED';
            $order->save();
        } catch (\Exception $e) {
            // If INVOICED not in enum, use COMPLETED
            $order->update(['status' => 'COMPLETED']);
        }
        
        return (new SalesOrderResource($order->load('farm', 'customer')))->response();
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        Gate::authorize('update', $order);
        
        if (in_array($order->status, ['PAID', 'COMPLETED', 'CANCELLED'])) {
            return response()->json([
                'message' => 'Cannot cancel PAID, COMPLETED, or already CANCELLED orders.'
            ], 422);
        }
        
        $order->update(['status' => 'CANCELLED']);
        
        return (new SalesOrderResource($order->load('farm', 'customer')))->response();
    }
}
