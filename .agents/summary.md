# Contexto vivo del proyecto

> Este archivo es el estado de trabajo acumulado. Actualizarlo **al cerrar cada fase**
> para que el siguiente agente no tenga que reconstruir el contexto desde cero.

## Objective

Refactorizar el ERP por capas (DTO, FormRequest, Service, Repository, Observer) y cerrar
los bugs de integridad de stock que aparecen al hacerlo. Prioridad actual: módulo de
**Inventory / Ajustes de inventario** (Fases 3-5 + Fase 2A) y **cobranza** (Fase 2 pendiente).

## Important Details

### Reglas de negocio / decisiones firmes

- **Ajustes de inventario**: un ajuste es siempre de **un solo almacén** (encabezado) y el
  movimiento hereda ese almacén. `MovementInventory::min_inv_number` referencia
  `inventories.inv_number` como FK **sin `ON UPDATE CASCADE`**.
- **`inv_number` es inmutable una vez creado el ajuste.** El `UpdateInventoryAdjustmentRequest`
  lo valida con `Rule::in([currentNumber()])` y el Service nunca lo actualiza. No intentar
  renumerar sin antes agregar una migración con `ON UPDATE CASCADE`.
- **Producto compuesto**: mueve stock **solo en sus componentes**, nunca en el compuesto padre.
- **Reversaciones de stock no generan warnings** de stock bajo (queda negativo, es correcto).
- **Borrado masivo de movimientos no dispara eventos Eloquent.** Para que
  `MovInventoryObserver::deleted` revierta stock hay que borrar **uno por uno**
  (`->each(fn ($m) => $m->delete())`), nunca `whereIn(...)->delete()`.
- **Watchers de stock en `updated`**: revertir sobre los valores **originales**
  (`$movement->getOriginal('*')`), no sobre los nuevos, o el stock vuelve al producto/almacén
  equivocado al editar un renglón.
- **Cachés de tenant usan arrays planos, nunca modelos Eloquent.** Con el driver `database`
  un modelo cacheado vuelve como `__PHP_Incomplete_Class`. Invalidar con un Observer
  (`saved`/`deleted`), no dentro del Service.

### Catálogo mínimo por tenant (`TenantCatalogSeeder`)

- Se ejecuta **solo en el pipeline `TenantCreated`** (vía `ProvisionTenantData`), **antes del
  early-return** de datos de admin. **NO hay backfill** de tenants existentes (decisión del
  usuario, 2026-09-28).
- siembra: 6 tipos de transacción, 3 motivos de ajuste, 15 categorías de gasto,
  `Almacén Principal`, e `Indefinida` en categoría/marca **solo si la tabla está vacía**
  (protege a `vidrialum-ca`, que tiene 9 categorías / 8 marcas reales).
- **No** siembra `rates` (la carga el usuario) ni los catálogos demo de
  `BrandSeeder`/`CategorySeeder`/`WarehouseSeeder` (Pilkington, Vidrio Templado, Bodega…).

### Trampas de la migración legacy (`LegacyVidrialumImporter`)

- `copyTable()` hace `INSERT INTO … SELECT` **sin truncar**. Antes de copiar hay que vaciar
  las tablas maestras que el provisioning sembró como placeholder, o choca con los ids del
  legacy: ver `PROVISIONING_PLACEHOLDERS` (company, brands, categories, warehouses,
  expense_categories, adjustment_reasons). `transaction_types` queda **fuera** a propósito
  (está en `EXCLUDED_TABLES`, el legacy no lo tiene).
- `assertIdempotent()` solo protege `products`, `suppliers`, `customers` — **no** las maestras.
- `legacy:migrate` opera sobre un tenant **ya existente** (`vidrialum-ca` por defecto), no crea.

### Entorno

- Postgres: cada tenant es un **schema** `tenant<slug>`. `php artisan db:seed` siembra la
  **conexión central, no el tenant**. Para un tenant puntual:
  `php artisan tenants:run db:seed --tenants=<slug> --argument=--class=TenantCatalogSeeder`.
- Transaccional es **Postgres**, no SQLite. `tenants:run` en esta versión de stancl es
  `tenants:run <commandname> --tenants=… --argument=…` (el comando va primero).
- Tests: SQLite `:memory:`, una sola conexión. **No hay tests de tenancy multi-schema**
  (`$tenant->run()`); por eso los seeders se prueban sobre la conexión por defecto y se
  diseñan **sin** `tenants:run()` adentro.
- `pnpm lint:check` y `pnpm format:check` fallan con ~60 errores / ~62 archivos **preexistentes**
  (28 `no-unused-vars`, 20 `no-mutating-props` en `AppSidebar` + `components/payments/*`,
  11 `v-html` sobre componente). **`composer run ci:check` no puede pasar** hasta que se
  limpien. Verificar siempre que los archivos tocados no sumen findings nuevos.

## Work State

### Completed

- **Fase 3 — backend ajustes en capas**: `InventoryAdjustmentDTO`,
  `InventoryAdjustmentItemDTO`, `Store/UpdateInventoryAdjustmentRequest`,
  `InventoryAdjustmentRepository` (sin N+1, reasons cacheadas 1 h por tenant),
  `InventoryAdjustmentService` (todo en `DB::transaction`, reconciliación create/update/delete),
  `AdjustmentReasonObserver` (invalida caché), `MovInventoryObserver` corregido,
  `AdjustmentReason`/`Inventory` con `HasFactory` + factories, controller 232→105 líneas.
- **Fase 4 — frontend**: tipos en `resources/js/types/Inventory/index.ts`, composable
  `useInventoryAdjustmentForm` (dedup Create/Edit), ambos selects migrados a `<Select>` de
  shadcn con valores string, estados vacíos para almacén/producto/motivo, Wayfinder en las
  3 páginas, `transactionTypes` eliminado del payload (no se usaba).
- **Fase 5 — tests**: `tests/Feature/Inventory/InventoryAdjustmentTest.php`, 18 tests /
  91 aserciones, incluida regresión de stock (delta de cantidad, renglón eliminado, cambio de
  producto/almacén, renombrar rechazado, borrado completo, caché invalidada).
- **Fase 2A — catálogo por tenant**: `TenantCatalogSeeder` nuevo; `TransactionTypeSeeder` y
  `ExpenseCategorySeeder` convertidos a idempotentes (`firstOrCreate`); `ProvisionTenantData`
  siembra el catálogo antes del early-return; `DatabaseSeeder` delega en
  `AdjustmentReasonSeeder`; `LegacyVidrialumImporter` con `PROVISIONING_PLACEHOLDERS`; hint
  del estado vacío corregido en `Create.vue`/`Edit.vue` (antes apuntaba a la conexión central).
  Tests: `tests/Feature/TenantCatalogSeederTest.php`, 7 tests / 23 aserciones.
- **Bugs de stock corregidos**: (1) mass delete sin eventos → stock no volvía; (2) `updated`
  revirtiendo sobre el producto nuevo; (3) renumerado de ajustes imposible por FK.
- **Suite completa**: 285 tests / 1700 aserciones, todas en verde. `pint --test` pass,
  `types:check` pass.

### Active

- (ninguno)

### Blocked

- **Fases 2B (recordatorio diario de tasa) y 2C (tests)**: requieren autorización explícita
  del usuario. Pendiente decidir: `RateService` con detección de tasa ausente/vencida,
  caché de freshness, prop compartida por Inertia, `RateReminderBanner.vue` con cierre
  diario vía localStorage y enlace a `rates/create`.
- **`acme-vidrios` y `techglass-ca` quedan con el catálogo vacío** (0 transaction types,
  0 categorías, 0 marcas, 0 almacenes) por decisión del usuario de no backfillearlos.
  Quedan inservibles hasta que alguien corra `TenantCatalogSeeder` a mano. El aviso ámbar
  del selector de motivos ya documenta el comando.
- **Deuda de lint/format preexistente** bloquea `composer run ci:check` (ver Important Details).

## Next Move

1. Pedir autorización para **Fase 2B** (recordatorio diario de tasa) y/o **2C**.
2. Refactor pendiente de **cobranza** (`app/Services/Finance/Cobranza/` y
   `app/Repositories/Finance/Cobranza/`), siguiendo el plan
   `.opencode/plans/2026-08-26-cobranza-redesign.md`.
3. Opcional: limpiar la deuda de lint global para desbloquear `ci:check`.

## Relevant Files

**Ajustes de inventario (Fases 3-5)**
- `app/Http/Controllers/Inventory/InventoryAdjustmentController.php`
- `app/Http/Requests/Inventory/StoreInventoryAdjustmentRequest.php`
- `app/Http/Requests/Inventory/UpdateInventoryAdjustmentRequest.php` — incluye la regla de
  `inv_number` inmutable
- `app/Repositories/Inventory/InventoryAdjustmentRepository.php`
- `app/Services/Inventory/InventoryAdjustmentService.php`
- `app/Services/Inventory/StockService.php`
- `app/DTOs/Inventory/InventoryAdjustmentDTO.php`, `InventoryAdjustmentItemDTO.php`
- `app/Observers/Inventory/MovInventoryObserver.php` — aplica/revierte stock
- `app/Observers/Inventory/AdjustmentReasonObserver.php` — invalida caché
- `app/Models/{Inventory,MovementInventory,AdjustmentReason,TransactionType}.php`
- `app/Providers/AppServiceProvider.php` — registro de observers
- `resources/js/composables/Inventory/useInventoryAdjustmentForm.ts`
- `resources/js/pages/Inventory/Adjustments/{Create,Edit,Index}.vue`
- `resources/js/types/Inventory/index.ts`
- `tests/Feature/Inventory/InventoryAdjustmentTest.php`

**Catálogo por tenant (Fase 2A)**
- `database/seeders/TenantCatalogSeeder.php` — catálogo mínimo, idempotente
- `database/seeders/{TransactionTypeSeeder,AdjustmentReasonSeeder,ExpenseCategorySeeder}.php`
- `database/seeders/{BrandSeeder,CategorySeeder,WarehouseSeeder,RateSeeder}.php` — **solo demo**
- `app/Jobs/Tenant/ProvisionTenantData.php` — siembra el catálogo antes del early-return
- `app/Console/Commands/{ProvisionTenantDataCommand,MigrateLegacyVidrialum,NewTenantCommand}.php`
- `app/Services/Legacy/LegacyVidrialumImporter.php` — `PROVISIONING_PLACEHOLDERS`
- `app/Providers/TenancyServiceProvider.php` — `Jobs\SeedDatabase::class` **comentado** a
  propósito (carga demo masiva)
- `tests/Feature/TenantCatalogSeederTest.php`, `tests/Feature/Console/`

**Pendiente 2B (tasa)**
- `app/Services/Finance/RateService.php`, `app/Observers/RateObserver.php`
- `app/Http/Middleware/HandleInertiaRequests.php` (prop compartida)
- `resources/js/components/RateReminderBanner.vue`, `resources/js/layouts/app/AppSidebarLayout.vue`

**Contexto general**
- `AGENTS.md` — reglas del proyecto, convenciones, comandos
- `.opencode/plans/` — planes por módulo
