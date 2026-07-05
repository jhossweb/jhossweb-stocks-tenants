<?php

namespace App\Http\Controllers\Sales\Customer;

use App\DTOs\Sales\Customer\CustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Customer\StoreCustomerRequest;
use App\Http\Requests\Sales\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Sales\Customer\CustomerService;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index()
    {
        return Inertia::render('Customers/Index', [
            'customers' => $this->customerService->list(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Customers/Create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->customerService->create(CustomerDTO::fromArray($request->validated()));

        return redirect()->route('customers.index')->with('success', 'Cliente creado exitosamente.');
    }

    public function show(Customer $customer)
    {
        return redirect()->route('customers.edit', $customer->id);
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->customerService->update($customer, CustomerDTO::fromArray($request->validated()));

        return redirect()->route('customers.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Customer $customer)
    {
        $this->customerService->delete($customer);

        return redirect()->route('customers.index')->with('success', 'Cliente eliminado exitosamente.');
    }
}
