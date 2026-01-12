<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Customer::class);
        
        $query = Customer::query();
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $customers = $query->orderBy('name')->paginate(20);
        return CustomerResource::collection($customers)->response();
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);
        
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        
        $customer = Customer::create($validated);
        
        return (new CustomerResource($customer->load('creator')))->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::with('creator')->findOrFail($id);
        Gate::authorize('view', $customer);
        
        return (new CustomerResource($customer))->response();
    }

    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        Gate::authorize('update', $customer);
        
        $customer->update($request->validated());
        
        return (new CustomerResource($customer->load('creator')))->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        Gate::authorize('delete', $customer);
        
        $customer->delete();
        
        return response()->json(null, 204);
    }
}
