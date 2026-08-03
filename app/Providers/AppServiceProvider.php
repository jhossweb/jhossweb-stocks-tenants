<?php

namespace App\Providers;

use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\MovementInventory;
use App\Models\MovementsPurchase;
use App\Models\MovementsSale;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Observers\BankTransactionObserver;
use App\Observers\ExpenseObserver;
use App\Observers\Inventory\MovInventoryObserver;
use App\Observers\Purchases\CostHistoryObserver;
use App\Observers\Purchases\MovPurchasesObserver;
use App\Observers\Purchases\PurchaseOrderObserver;
use App\Observers\RateObserver;
use App\Observers\Sales\MovSalesObserver;
use App\Observers\Sales\SaleOrderObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerSqliteDateFunctions();

        MovementsPurchase::observe(MovPurchasesObserver::class);
        MovementsPurchase::observe(CostHistoryObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        BankTransaction::observe(BankTransactionObserver::class);
        Expense::observe(ExpenseObserver::class);
        Rate::observe(RateObserver::class);

        MovementsSale::observe(MovSalesObserver::class);
        SaleOrder::observe(SaleOrderObserver::class);

        MovementInventory::observe(MovInventoryObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Emula TO_CHAR de PostgreSQL en SQLite para consultas por fecha.
     */
    protected function registerSqliteDateFunctions(): void
    {
        $connection = DB::connection();

        if (! $connection instanceof SQLiteConnection) {
            return;
        }

        $connection->getPdo()->sqliteCreateFunction('TO_CHAR', function ($value, $format) {
            if ($value === null) {
                return null;
            }

            $date = new CarbonImmutable((string) $value);

            $replacements = [
                'YYYY-MM' => $date->format('Y-m'),
                'YYYY' => $date->format('Y'),
                'YY' => $date->format('y'),
                'MM' => $date->format('m'),
                'MON' => strtoupper($date->format('M')),
                'Mon' => $date->format('M'),
                'DD' => $date->format('d'),
                'HH24' => $date->format('H'),
                'HH' => $date->format('H'),
                'MI' => $date->format('i'),
                'SS' => $date->format('s'),
            ];

            return strtr((string) $format, $replacements);
        });
    }
}
