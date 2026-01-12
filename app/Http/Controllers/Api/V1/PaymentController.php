<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\SalesOrder;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index(Request $request, string $salesOrderId): JsonResponse
    {
        $order = SalesOrder::findOrFail($salesOrderId);
        Gate::authorize('view', $order);
        
        $payments = Payment::with('receivedBy')
            ->where('sales_order_id', $order->id)
            ->orderBy('payment_date', 'desc')
            ->get();
        
        return PaymentResource::collection($payments)->response();
    }

    public function store(StorePaymentRequest $request, string $salesOrderId): JsonResponse
    {
        $order = SalesOrder::findOrFail($salesOrderId);
        Gate::authorize('update', $order);
        
        $validated = $request->validated();
        $validated['sales_order_id'] = $order->id;
        $validated['farm_id'] = $order->farm_id;
        $validated['currency'] = $validated['currency'] ?? $order->currency;
        $validated['received_by'] = $request->user()->id;
        
        $payment = Payment::create($validated);
        
        // Payment model boot will refresh payment status automatically
        
        return (new PaymentResource($payment->load('receivedBy')))->response()->setStatusCode(201);
    }
}
