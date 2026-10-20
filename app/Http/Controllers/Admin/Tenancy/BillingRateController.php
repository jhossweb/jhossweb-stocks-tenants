<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tenancy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenancy\BillingRate\StoreBillingRateRequest;
use App\Http\Requests\Admin\Tenancy\BillingRate\UpdateBillingRateRequest;
use App\Models\BillingRate;
use App\Repositories\Tenancy\BillingRateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingRateController extends Controller
{
    public function __construct(
        private readonly BillingRateRepository $repository,
    ) {}

    public function index(Request $request): Response
    {
        $filters = ['search' => $request->get('search')];
        $rates = $this->repository->paginate($filters, (int) $request->get('per_page', 15));

        return Inertia::render('admin/rates/index', [
            'rates' => $rates,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/rates/create', [
            'latest' => $this->repository->latest()?->toArray(),
        ]);
    }

    public function store(StoreBillingRateRequest $request): RedirectResponse
    {
        BillingRate::create($request->validated());

        return redirect()->route('admin.rates.index')->with('success', 'Tasa registrada');
    }

    public function edit(BillingRate $rate): Response
    {
        return Inertia::render('admin/rates/edit', [
            'rate' => $rate->toArray(),
        ]);
    }

    public function update(UpdateBillingRateRequest $request, BillingRate $rate): RedirectResponse
    {
        $rate->update($request->validated());

        return redirect()->route('admin.rates.index')->with('success', 'Tasa actualizada');
    }

    public function destroy(BillingRate $rate): RedirectResponse
    {
        $rate->delete();

        return redirect()->route('admin.rates.index')->with('success', 'Tasa eliminada');
    }
}
