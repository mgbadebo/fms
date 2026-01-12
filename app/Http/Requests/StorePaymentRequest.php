<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SalesOrder;

class StorePaymentRequest extends FormRequest
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
        
        $order = SalesOrder::find($this->route('sales_order_id'));
        if (!$order) {
            return false;
        }
        
        if (!$user->can('payments.record')) {
            return false;
        }
        
        return $user->farms()->where('farms.id', $order->farm_id)->exists();
    }

    public function rules(): array
    {
        return [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'method' => 'required|in:CASH,TRANSFER,POS,ONLINE',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
