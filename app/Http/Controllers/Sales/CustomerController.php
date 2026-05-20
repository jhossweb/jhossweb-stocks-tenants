<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('clt_name')
            ->paginate(10);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Customers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clt_name' => 'required|string|max:100|unique:customers,clt_name',
            'clt_address' => 'required|string|max:255',
            'clt_phone' => 'required|string|max:20|unique:customers,clt_phone',
            'clt_email' => 'required|email|max:100|unique:customers,clt_email',
            'clt_website' => 'nullable|string|max:100',
        ]);

        Customer::create($validated);

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

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'clt_name' => 'required|string|max:100|unique:customers,clt_name,'.$customer->id,
            'clt_address' => 'required|string|max:255',
            'clt_phone' => 'required|string|max:20|unique:customers,clt_phone,'.$customer->id,
            'clt_email' => 'required|email|max:100|unique:customers,clt_email,'.$customer->id,
            'clt_website' => 'nullable|string|max:100',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Cliente eliminado exitosamente.');
    }
}
