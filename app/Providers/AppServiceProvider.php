<?php

namespace App\Providers;

use App\Models\AdjustmentReason;
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
use App\Observers\Inventory\AdjustmentReasonObserver;
use App\Observers\Inventory\MovInventoryObserver;
use App\Observers\Purchases\MovCostHistoryObserver;
use App\Observers\Purchases\MovPurchasesObserver;
use App\Observers\Purchases\PurchaseOrderObserver;
use App\Observers\RateObserver;
use App\Observers\Sales\MovSalesObserver;
use App\Observers\Sales\SaleOrderObserver;
use App\Services\Inventory\StockService;
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
        $this->app->singleton(StockService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerSqliteDateFunctions();

        // En testing el esquema completo (central + negocio + spatie) vive en un
        // SQLite :memory:. El path de migraciones de tenant se carga solo aquí
        // para no ensuciar el esquema central en dev. (Fase 6: tests con tenancy real.)
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));

            // La tabla central `tenants`/`domains` (y los modelos Tenant/Domain)
            // apuntan a la conexión 'central' (pgsql en dev). En testing esa
            // conexión se redirige al SQLite :memory: para poder probar el alta
            // de tenants sin tocar la BD real.
            config(['tenancy.database.central_connection' => 'sqlite']);
        }

        MovementsPurchase::observe(MovPurchasesObserver::class);
        MovementsPurchase::observe(MovCostHistoryObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        BankTransaction::observe(BankTransactionObserver::class);
        Expense::observe(ExpenseObserver::class);
        Rate::observe(RateObserver::class);

        MovementsSale::observe(MovSalesObserver::class);
        SaleOrder::observe(SaleOrderObserver::class);

        MovementInventory::observe(MovInventoryObserver::class);
        AdjustmentReason::observe(AdjustmentReasonObserver::class);
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
