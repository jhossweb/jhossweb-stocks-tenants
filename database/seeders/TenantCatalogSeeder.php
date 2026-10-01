<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Catálogo mínimo funcional que todo tenant necesita para operar.
 *
 * Deliberadamente NO siembra marcas, categorías ni almacenes de ejemplo
 * (Vidrio Templado, Pilkington, Bodega Vidrio Crudo…): esos viven en
 * BrandSeeder/CategorySeeder/WarehouseSeeder y son datos demo de Vidrialum,
 * no un baseline razonable para un tenant nuevo.
 *
 * SÍ siembra lo funcional: tipos de transacción (necesarios para numerar
 * facturas/ajustes), motivos de ajuste, categorías de gasto y un almacén.
 *
 * Idempotente: se puede correr las veces que haga falta sin duplicar ni pisar
 * folios ya emitidos. NO debe tocar `rates`: la tasa la carga el usuario.
 *
 * Opera sobre la conexión activa. Quien lo invoca es responsable de envolverlo
 * en `$tenant->run()` (ver App\Jobs\Tenant\ProvisionTenantData), para que así
 * sea testeable sin levantar un schema de tenant.
 */
class TenantCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TransactionTypeSeeder::class,
            AdjustmentReasonSeeder::class,
            ExpenseCategorySeeder::class,
        ]);

        Warehouse::firstOrCreate(['war_name' => 'Almacén Principal']);

        // Categoría y marca son NOT NULL en products. Se crea una neutra solo si
        // el tenant no tiene catálogo propio: un tenant que ya cargo sus 9
        // categorías no debe recibir una fila "Indefinida" espuria.
        $this->seedPlaceholderIfEmpty(Category::class, 'cat_name');
        $this->seedPlaceholderIfEmpty(Brand::class, 'bra_name');
    }

    /**
     * @param  class-string  $model
     */
    private function seedPlaceholderIfEmpty(string $model, string $nameColumn): void
    {
        if ($model::query()->exists()) {
            return;
        }

        $model::query()->create([$nameColumn => 'Indefinida']);
    }
}
