<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SalesOrder;

class UpdateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        if ($user->hasRole('ADMIN')) {
            return true;
        }
        
        $order = SalesOrder::find($this->route('id'));
        if (!$order) {
            return false;
        }
        
        if (!$user->can('sales_orders.update')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $order->farm_id)->exists();
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'order_date' => 'sometimes|date',
            'status' => 'sometimes|in:DRAFT,CONFIRMED,DISPATCHED,INVOICED,PAID,COMPLETED,CANCELLED',
            'currency' => 'sometimes|string|size:3',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
        ];
    }
}
