<?php

namespace App\Http\Controllers\Settings;

use App\DTOs\Settings\RateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRateRequest;
use App\Http\Requests\Settings\UpdateRateRequest;
use App\Models\Rate;
use App\Services\Settings\RateService;
use Inertia\Inertia;

class RateController extends Controller
{
    public function __construct(
        private readonly RateService $rateService,
    ) {}

    public function index()
    {
        return Inertia::render('settings/Rate/Index', [
            'rates' => $this->rateService->list(),
        ]);
    }

    public function create()
    {
        return Inertia::render('settings/Rate/Create');
    }

    public function store(StoreRateRequest $request)
    {
        $this->rateService->create(RateDTO::fromArray($request->validated()));

        return redirect()->route('rates.index')
            ->with('success', 'Tasa creada exitosamente.');
    }

    public function show(Rate $rate)
    {
        return Inertia::render('settings/Rate/Show', [
            'rate' => $rate,
        ]);
    }

    public function edit(Rate $rate)
    {
        return Inertia::render('settings/Rate/Edit', [
            'rate' => $rate,
        ]);
    }

    public function update(UpdateRateRequest $request, Rate $rate)
    {
        $this->rateService->update($rate, RateDTO::fromArray($request->validated()));

        return redirect()->route('rates.index')
            ->with('success', 'Tasa actualizada exitosamente.');
    }

    public function destroy(Rate $rate)
    {
        $this->rateService->delete($rate);

        return redirect()->route('rates.index')
            ->with('success', 'Tasa eliminada exitosamente.');
    }
}
