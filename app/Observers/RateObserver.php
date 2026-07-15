<?php

namespace App\Observers;

use App\Models\Rate;
use Illuminate\Support\Facades\Cache;

class RateObserver
{
    public function created(Rate $rate): void
    {
        Cache::forget('rates:v2');
    }

    public function updated(Rate $rate): void
    {
        Cache::forget('rates:v2');
    }

    public function deleted(Rate $rate): void
    {
        Cache::forget('rates:v2');
    }
}
