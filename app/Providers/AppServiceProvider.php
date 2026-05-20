<?php

namespace App\Providers;

use App\Models\BankTransaction;
use App\Models\MovementsPurchase;
use App\Models\MovementsSale;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Observers\BankTransactionObserver;
use App\Observers\Purchases\CostHistoryObserver;
use App\Observers\Purchases\MovPurchasesObserver;
use App\Observers\Purchases\PurchaseOrderObserver;
use App\Observers\Sales\MovSalesObserver;
use App\Observers\Sales\SaleOrderObserver;
use Carbon\CarbonImmutable;
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
        MovementsPurchase::observe(MovPurchasesObserver::class);
        MovementsPurchase::observe(CostHistoryObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        BankTransaction::observe(BankTransactionObserver::class);

        MovementsSale::observe(MovSalesObserver::class);
        SaleOrder::observe(SaleOrderObserver::class);
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
}
