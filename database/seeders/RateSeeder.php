<?php

namespace Database\Seeders;

use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    public function run(): void
    {
        $last = Rate::orderBy('rate_date', 'desc')->first();

        if (! $last) {
            $this->command->warn('No hay rates registrados. Crea uno primero.');

            return;
        }

        $start = Carbon::parse($last->rate_date)->addDay();
        $end = now();

        if ($start->greaterThan($end)) {
            $this->command->info('Ya existe rate para la fecha actual. No se requieren registros nuevos.');

            return;
        }

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        $existing = Rate::whereIn('rate_date', $dates)->pluck('rate_date')
            ->map(fn ($d) => $d instanceof Carbon ? $d->format('Y-m-d') : Carbon::parse($d)->format('Y-m-d'));

        foreach ($dates as $date) {
            if ($existing->contains($date)) {
                continue;
            }

            Rate::create([
                'rate_date' => $date,
                'rate_usd' => $last->rate_usd,
                'rate_cop' => $last->rate_cop,
                'rate_eur' => $last->rate_eur,
                'rate_cop_bs' => $last->rate_cop_bs,
            ]);
        }

        $this->command->info('Rates generados del ' . $dates[0] . ' al ' . $dates[array_key_last($dates)] . '.');
    }
}
