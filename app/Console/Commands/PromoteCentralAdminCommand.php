<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteCentralAdminCommand extends Command
{
    protected $signature = 'admin:promote
        {email : Email del usuario central a promover}';

    protected $description = 'Marca un usuario central como super admin (is_admin = true)';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("Usuario [{$this->argument('email')}] no encontrado.");

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("Usuario [{$user->email}] promovido a super admin del portal central.");

        return self::SUCCESS;
    }
}
