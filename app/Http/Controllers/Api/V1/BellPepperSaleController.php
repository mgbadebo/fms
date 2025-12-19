<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BellPepperSale;
use App\Models\BellPepperHarvest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BellPepperSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BellPepperSale::with(['farm', 'harvest', 'customer']);

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
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate(20);
        return response()->json($sales);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'bell_pepper_harvest_id' => 'nullable|exists:bell_pepper_harvests,id',
            'sale_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_type' => 'required|in:RETAIL,BULK_BUYER,DISTRIBUTOR,CATERING,HOTEL,OTHER',
            'quantity_kg' => 'required|numeric|min:0',
            'crates_count' => 'nullable|integer|min:0',
            'grade' => 'required|in:A,B,C,MIXED',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'logistics_cost' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:CASH,TRANSFER,POS,CHEQUE,CREDIT',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Generate sale code
        $validated['sale_code'] = 'BP-SALE-' . strtoupper(Str::random(8));

        // Set defaults
        $validated['discount'] = $validated['discount'] ?? 0;
        $validated['logistics_cost'] = $validated['logistics_cost'] ?? 0;
        $validated['amount_paid'] = $validated['amount_paid'] ?? 0;

        // Calculate amounts
        $validated['total_amount'] = $validated['quantity_kg'] * $validated['unit_price'];
        $validated['final_amount'] = $validated['total_amount'] - $validated['discount'];

        // Auto-calculate crates if not provided
        if (!isset($validated['crates_count']) || $validated['crates_count'] == 0) {
            $validated['crates_count'] = (int)ceil($validated['quantity_kg'] / 9.5);
        }

        $sale = BellPepperSale::create($validated);
        
        // Calculate payment
        $sale->calculatePayment();
        $sale->save();

        return response()->json(['data' => $sale->load('farm', 'harvest', 'customer')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $sale = BellPepperSale::with(['farm', 'harvest', 'customer'])->findOrFail($id);
        return response()->json(['data' => $sale]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $sale = BellPepperSale::findOrFail($id);

        $validated = $request->validate([
            'sale_date' => 'sometimes|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:255',
            'customer_type' => 'sometimes|in:RETAIL,BULK_BUYER,DISTRIBUTOR,CATERING,HOTEL,OTHER',
            'bell_pepper_harvest_id' => 'nullable|exists:bell_pepper_harvests,id',
            'quantity_kg' => 'sometimes|numeric|min:0',
            'crates_count' => 'nullable|integer|min:0',
            'grade' => 'sometimes|in:A,B,C,MIXED',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'logistics_cost' => 'nullable|numeric|min:0',
            'payment_method' => 'sometimes|in:CASH,TRANSFER,POS,CHEQUE,CREDIT',
            'payment_status' => 'sometimes|in:PAID,PARTIAL,OUTSTANDING',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Recalculate amounts if needed
        if (isset($validated['quantity_kg']) && isset($validated['unit_price'])) {
            $validated['total_amount'] = $validated['quantity_kg'] * $validated['unit_price'];
            $validated['final_amount'] = $validated['total_amount'] - ($validated['discount'] ?? $sale->discount ?? 0);
        }

        // Handle payment status
        if (isset($validated['payment_status'])) {
            if ($validated['payment_status'] === 'PAID') {
                $validated['amount_paid'] = $sale->final_amount;
                $validated['amount_outstanding'] = 0;
            } elseif ($validated['payment_status'] === 'OUTSTANDING') {
                if (!isset($validated['amount_paid'])) {
                    $validated['amount_paid'] = 0;
                }
                $validated['amount_outstanding'] = $sale->final_amount - ($validated['amount_paid'] ?? 0);
            } else {
                $amountPaid = $validated['amount_paid'] ?? $sale->amount_paid ?? 0;
                $validated['amount_outstanding'] = $sale->final_amount - $amountPaid;
            }
        } elseif (isset($validated['amount_paid'])) {
            $sale->amount_paid = $validated['amount_paid'];
            $sale->calculatePayment();
            $validated['payment_status'] = $sale->payment_status;
            $validated['amount_outstanding'] = $sale->amount_outstanding;
        }

        $sale->update($validated);

        return response()->json(['data' => $sale->load('farm', 'harvest', 'customer')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $sale = BellPepperSale::findOrFail($id);
        $sale->delete();

        return response()->json(null, 204);
    }
}
