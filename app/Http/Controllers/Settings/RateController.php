<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rates = Rate::orderBy("rate_date", "desc")->paginate(10);
        return Inertia::render('settings/Rate/Index', [
            'rates' => $rates,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('settings/Rate/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rate_date' => 'required|date|unique:rates,rate_date',
            'rate_usd' => 'required|numeric|min:0',
            'rate_cop' => 'required|numeric|min:0',
            'rate_eur' => 'required|numeric|min:0',
        ]);

        Rate::create($validated);

        return redirect()->route('rates.index')
            ->with('success', 'Tasa creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Rate $rate)
    {
        return Inertia::render('settings/Rate/Show', [
            'rate' => $rate,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rate $rate)
    {
        return Inertia::render('settings/Rate/Edit', [
            'rate' => $rate,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rate $rate)
    {
        $validated = $request->validate([
            'rate_date' => 'required|date|unique:rates,rate_date,' . $rate->id,
            'rate_usd' => 'required|numeric|min:0',
            'rate_cop' => 'required|numeric|min:0',
            'rate_eur' => 'required|numeric|min:0',
        ]);

        $rate->update($validated);

        return redirect()->route('rates.index')
            ->with('success', 'Tasa actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rate $rate)
    {
        $rate->delete();

        return redirect()->route('rates.index')
            ->with('success', 'Tasa eliminada exitosamente.');
    }
}
