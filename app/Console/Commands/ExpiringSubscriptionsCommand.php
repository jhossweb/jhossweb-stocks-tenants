<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpiringSubscriptionsCommand extends Command
{
    protected $signature = 'saas:subscriptions:expiring';

    protected $description = 'Notifica suscripciones por vencer';

    public function handle(): int
    {
        $this->info('Suscripciones por vencer (placeholder F6)');

        return self::SUCCESS;
    }
}
