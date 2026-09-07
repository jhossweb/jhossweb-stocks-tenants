<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Company', [
            'company' => Company::first(),
        ]);
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        Company::updateOrCreate(
            ['id' => 1],
            $request->validated()
        );

        Cache::forget(Company::CACHE_KEY);

        return redirect()->route('company.edit')
            ->with('success', 'Datos de la empresa actualizados exitosamente.');
    }
}
