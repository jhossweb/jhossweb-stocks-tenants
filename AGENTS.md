# Inventary Vidrialum — Guía para Agentes

**Stack:**
- **Backend:** Laravel 13 (PHP 8.3+), Pest PHP, SQLite, Laravel Pint
- **Frontend:** Vue 3 (Composition API, `<script setup>`), Inertia.js v3, TypeScript, Tailwind CSS v4, shadcn-vue (Reka UI), Lucide icons
- **Auth:** Laravel Fortify (registration, password reset, email verification, 2FA)
- **Gestor paquetes:** pnpm (pnpm-lock.yaml). No usar npm install.

---

## Inicio rápido

```bash
composer run setup     # bootstrap completo: dependencias, .env, key, migrate, build
composer run dev       # 4 procesos concurrentes (server + queue + logs + vite)
```

---

## Comandos

| Qué | Comando | Notas |
|---|---|---|
| Servidor dev | `composer run dev` | `php artisan serve`, queue worker, `pail` logs y Vite en simultáneo |
| PHP lint (auto-fix) | `composer run lint` | Pint con `--parallel` |
| PHP lint (check) | `composer run lint:check` | Pint en modo `--test` |
| JS/TS lint (fix) | `pnpm lint` | ESLint flat config |
| JS/TS lint (check) | `pnpm lint:check` | |
| Formato | `pnpm format` | Prettier sobre `resources/` |
| Type check | `pnpm types:check` | `vue-tsc --noEmit` |
| CI completo | `composer run ci:check` | lint:check → format:check → types:check → test |
| **Tests** | `composer run test` | Ejecuta lint:check PRIMERO, luego `php artisan test` (Pest) |
| Build | `pnpm build` / `pnpm build:ssr` | Vite |

**Ojo:** `composer run test` falla si `lint:check` no pasa. Usa `php artisan test` para ejecutar tests rápido durante desarrollo.

---

## Contexto del Proyecto: ERP System

Sistema de inventario y gestión empresarial (ERP) para Vidrialum. Cubre compras, ventas, inventario, kardex, gastos, empleados, nómina, bancos, cuentas por cobrar/pagar.

El proyecto comenzó con lógica temporal en controladores para validar conceptos rápido. La meta es migrar TODO el código a una arquitectura por capas, tanto en backend como frontend.

---

## Estrategia de Desarrollo y Refactorización

### Estado Actual

- **Código temporal en controladores:** Hay lógica de negocio y validaciones directamente en los Controladores, escritas para pruebas rápidas de concepto.
- **Meta final:** TODO el código debe migrar a la arquitectura de capas (servicios, repositorios, DTOs, form requests, observadores en backend; composables, tipos, componentes puros en frontend). No hay código "legacy" que vaya a quedarse como está.

### Reglas

1. **Refactorización proactiva (obligatoria):** Cuando trabajes en un archivo existente (corrigiendo un bug, añadiendo una feature, o por asignación directa), DEBES refactorizar el código acoplado que haya en ese archivo, moviéndolo a su capa correspondiente. No dejes código temporal sin migrar en los archivos que modificas.
2. **Sin refactors masivos:** No reescribas todo el código existente de golpe. El refactor ocurre archivo por archivo, a medida que se intervienen. Esto evita riesgos y mantiene el proyecto funcionando continuamente.
3. **Código nuevo SIEMPRE en capas:** Todo archivo nuevo, nueva funcionalidad o nuevo feature DEBE nacer directamente en la arquitectura de capas.

### Filosofía

- **Pragmatismo:** Aplica SOLID pero sin sobreingeniería. Sin interfaces innecesarias, sin abstracciones rebuscadas. Resuelve con el menor número de archivos y la mayor claridad posible.
- **Consistencia:** La estructura de directorios del frontend debe reflejar la del backend por dominio. Si existe `app/Services/Inventory/`, debe existir `resources/js/composables/Inventory/`.

---

## Arquitectura Backend (Laravel)

### Flujo típico (código nuevo o refactor)

1. **Form Request** (`app/Http/Requests/`) — Autorización + reglas de validación. Reemplaza `request()->validate()`.
2. **DTO** (`app/DTOs/`) — Clase tipada con propiedades `readonly`. Nunca pases `request()->all()` a capas inferiores.
3. **Service** (`app/Services/`) — Lógica de negocio, flujos intermodulares. Recibe DTOs, usa repositorios.
4. **Action Class** (dentro de `app/Actions/<Dominio>/`) — Clase de un solo propósito con método `handle()`/`execute()`. Para aislar lógica específica y evitar Services inflados (SRP).
5. **Repository** (`app/Repositories/`) — Aísla consultas Eloquent complejas. Métodos semánticos como `getPendingInvoices()`.
6. **Observer** (`app/Observers/`) — Lógica derivada post crear/actualizar registro (integridad pasiva de BD, ej: actualizar stock, recalcular promedios).
7. **Report / Query** (`app/Reports/` o `app/Queries/`) — Reportes densos, exportaciones, queries optimizadas con `select` específicos, `with()`, procesamiento en chunks.

### Reglas backend

- Siempre usa Form Requests (nunca `request()->validate()` en controladores).
- Siempre encapsula datos en DTOs (nunca pases arrays crudos a services/repos).
- Services para flujos amplios; Action Classes para lógica atómica.
- Queries Eloquent complejas van en Repositories, no en controladores ni services.
- Observers para efectos secundarios al crear/actualizar registros.
- No crees interfaces para repositorios si solo hay una implementación.

---

## Arquitectura de Modelos

### Rol del Modelo

Los modelos Eloquent son el corazón del dominio. No son meras representaciones de tablas — deben encapsular la lógica de negocio que opera sobre sus propios datos.

### Qué va en el Modelo (✅)

| Tipo | Ejemplo |
|------|---------|
| **Relationships** | `belongsTo()`, `hasMany()`, `morphMany()` |
| **Scopes** | `scopePending()`, `scopeActive()` |
| **Casts** | Enums (`#[Cast('status', StatusEnum::class)]`), `decimal`, `date` |
| **Accessors/Mutators** | `getFullNameAttribute()`, `setPasswordAttribute()` |
| **Lógica interna** | `isPaid()`, `reduceBalance()`, `calcularCostoCompuesto()` — métodos que operan **solo sobre sus propios atributos y relaciones** |
| **Eventos internos** | `$dispatchesEvents` para lógica derivada leve |

### Qué NO va en el Modelo (🚫)

| Tipo | Destino |
|------|---------|
| **Validación de requests** | `Form Request` |
| **Orquestación cross-entity** (toca 2+ modelos distintos) | `Service` |
| **Queries complejas agregadas** (SQL raw, UNION, reportes) | `Repository` o `Report` |
| **Operación atómica reutilizable** | `Action Class` |
| **Efectos secundarios post persistencia** | `Observer` |

### Reglas de Modelos

- Usa **scopes** para filtros reutilizables (no queries inline en controllers).
- Usa **casts** de enums (`\App\Enums\...`) para campos con valores fijos. No strings sueltos.
- Usa **`$fillable`** (o atributo `#[Fillable]` en PHP 8+) — nunca `$guarded` vacío.
- Los modelos pueden tener métodos de negocio **siempre que operen sobre sus propios datos**. Si necesitas inyectar dependencias externas (ej: otro modelo, un repositorio), es señal de que debe ser un Service.
- No inyectes otros modelos como dependencias en métodos del modelo. Usa relaciones y queries, no dependencias externas.
- Las **relaciones** deben estar tipadas con el tipo de retorno (`BelongsTo`, `HasMany`, etc.).
- **Eventos de modelo** (`creating`, `created`, `updating`, etc.) para lógica simple previa/post guardado. Para lógica derivada que toca otras entidades, prefiere Observers.

---

## Arquitectura Frontend (Vue 3 + TypeScript)

### Capas

| Capa | Directorio | Responsabilidad |
|---|---|---|
| **Page** | `resources/js/pages/<Dominio>/` | Componente raíz de Inertia. Delgado: obtiene props, renderiza. Sin lógica de negocio. |
| **Composable** | `resources/js/composables/<Dominio>/` | Equivalente frontend de un Service. Lógica de negocio reutilizable, estado compartido, llamadas a API. |
| **Componente UI** | `resources/js/components/` | Componentes reutilizables de presentación. Sin lógica de negocio. |
| **shadcn-vue** | `resources/js/components/ui/` | Componentes base del kit. NO editar manualmente (se regeneran con `shadcn-vue add`). |
| **Types/Interfaces** | `resources/js/types/<Dominio>/` | Tipos e interfaces de dominio. Reflejan los DTOs del backend. |
| **Layout** | `resources/js/layouts/` | Layouts de página (AppLayout, AuthLayout, SettingsLayout). |
| **Utils** | `resources/js/lib/` | Funciones de utilería puras (ej: `cn()` para Tailwind). |

### Flujo típico frontend

1. **Page** recibe props tipadas desde Inertia. Renderiza componentes UI.
2. **Composable** encapsula lógica reutilizable: formateo de datos, cálculos, estado, llamadas a endpoints AJAX.
3. **Types/Interfaces** definen la forma de los datos. Deben coincidir con los DTOs/Modelos del backend.
4. **Componentes** son puros (presentacionales) o semánticos (usan composables).

### Reglas frontend

- Las **Pages** deben ser delgadas. Mueve lógica pesada a composables.
- Los **composables** son el equivalente a los Services del backend. Organízalos por dominio igual que en `app/Services/`.
- Define **tipos e interfaces** para cada entidad de dominio. No uses `any` ni objetos genéricos.
- Los **componentes** deben ser reutilizables. Extrae partes repetidas a componentes compartidos.
- `<script setup>` es el estándar para componentes Vue.
- Los **eventos** y **llamadas AJAX** deben ir en composables, no en los componentes de página.

---

## Estructura de Directorios (Backend + Frontend)

```
app/
├── Actions/
│   └── Fortify/                     # CreateNewUser, ResetUserPassword (legacy)
├── Concerns/                         # Traits compartidos
├── DTOs/                             # Data Transfer Objects
│   ├── Expense/
│   ├── Finance/
│   │   ├── Bank/
│   │   └── Cobranza/
│   ├── Inventory/
│   ├── Payroll/
│   ├── Purchases/
│   ├── Sales/
│   │   ├── Customer/
│   │   ├── SaleOrder/
│   │   └── SaleReturn/
│   └── Settings/
├── Enum/
│   ├── Employees/
│   ├── Expense/
│   ├── Finanzas/
│   ├── Inventary/
│   ├── Payroll/
│   ├── Purchases/
│   └── Sales/
├── Http/
│   ├── Controllers/                  # Thin controllers
│   │   ├── Employees/
│   │   ├── Expense/
│   │   ├── Finance/
│   │   │   ├── Bank/
│   │   │   └── Cobranza/
│   │   ├── Inventory/
│   │   ├── Payroll/
│   │   ├── Purchase/
│   │   ├── Sales/
│   │   │   ├── Customer/
│   │   │   ├── SaleOrder/
│   │   │   └── SaleReturn/
│   │   └── Settings/
│   ├── Requests/                     # Form Requests (mismo mirror)
│   └── Middleware/
├── Models/                           # 33 modelos Eloquent
├── Observers/
├── Providers/
├── Repositories/
│   ├── Expense/
│   ├── Finance/
│   │   ├── Bank/
│   │   └── Cobranza/
│   ├── Inventory/
│   ├── Payroll/
│   ├── Purchases/
│   ├── Sales/
│   └── Settings/
├── Reports/
│   ├── Finance/
│   └── Inventory/
└── Services/
    ├── Expense/
    ├── Finance/
    │   ├── Bank/
    │   └── Cobranza/
    ├── Inventory/
    ├── Payroll/
    ├── Purchases/
    ├── Sales/
    └── Settings/

resources/js/
├── app.ts                            # Entry point Inertia + resolución layouts
├── pages/                            # Pages Inertia (delgadas, por dominio)
│   ├── auth/
│   ├── Customers/
│   ├── Dashboard.vue
│   ├── Employees/
│   ├── Expense/
│   ├── Finance/
│   ├── Inventory/
│   ├── Payroll/
│   ├── Purchases/
│   ├── PurchaseReturns/
│   ├── SaleReturns/
│   ├── Sales/
│   ├── settings/
│   └── Welcome.vue
├── components/
│   ├── ui/                           # shadcn-vue — NO editar manualmente
│   ├── payments/
│   └── ...componentes compartidos
├── composables/                      # Lógica reutilizable (equivalente a Services)
│   ├── useAppearance.ts
│   ├── useCurrentUrl.ts
│   ├── useDate.ts
│   ├── useInitials.ts
│   ├── usePayment.ts
│   ├── useTwoFactorAuth.ts
│   └── Inventory/                    # Organizar por dominio al crecer
│   └── Sales/
│   └── Finance/
├── layouts/
│   ├── app/
│   ├── auth/
│   ├── settings/
│   ├── AppLayout.vue
│   ├── AuthLayout.vue
├── lib/
│   └── utils.ts                      # cn() helper
├── types/
│   ├── auth.ts
│   ├── global.d.ts
│   ├── index.ts
│   ├── navigation.ts
│   ├── ui.ts
│   ├── vue-shims.d.ts
│   ├── Inventory/                    # Tipos por dominio al crecer
│   ├── Sales/
│   ├── Finance/
│   └── ...
├── actions/                          # Generado — ESLint-ignored, NO editar
├── routes/                           # Generado — ESLint-ignored, NO editar
└── wayfinder/                        # Generado — ESLint-ignored, NO editar

routes/
├── web.php                           # Rutas protegidas por dominio + AJAX /api/*
├── settings.php                      # Profile, security, appearance, rates
└── console.php

database/
├── migrations/                       # 43 migraciones
├── factories/                        # 24 factories
└── seeders/                          # DatabaseSeeder, ExpenseCategorySeeder
```

---

## Testing

- **Pest PHP v4** (sobre PHPUnit). Aserciones fluidas con `expect()->...`.
- Suites: `tests/Unit/`, `tests/Feature/`
- Tests Feature extienden `Tests\TestCase` (Laravel TestCase)
- `RefreshDatabase` NO es global. Añadirlo por clase o vía `pest()->use(RefreshDatabase::class)->in('Feature')`.
- Rápido (sin lint): `php artisan test`
- Completo (con lint gate): `composer run test`

---

## Code Style

### PHP

- Laravel Pint (preset laravel). Tipado fuerte en argumentos y retornos.

### JS/TS (ESLint + Prettier + vue-tsc)

- Grupos de import ordenados: builtin → external → internal → parent → sibling → index
- Type imports: estilo `type-imports`, `prefer-top-level`
- 1tbs brace style, nunca en una sola línea
- Líneas en blanco antes/después de estructuras de control
- `curly: all` — llaves obligatorias en todo bloque de control
- **Prettier tabWidth: 4** (no standard para JS). YAML: 2.
- Single quotes, semicolons, 80 caracteres de ancho

### EditorConfig

- Indent 4 espacios, LF, trim trailing whitespace

### Vue 3

- Priorizar `<script setup>`.
- Componentes puros (presentacionales). Lógica pesada delegada a composables.
- Nombres multi-palabra (regla `vue/multi-word-component-names` desactivada — se permite una sola palabra).

### ESLint ignora (NO modificar estos directorios)

```
vendor/
node_modules/
public/
bootstrap/ssr/
resources/js/actions/**
resources/js/components/ui/*
resources/js/routes/**
resources/js/wayfinder/**
```

---

## Stack Details

- **Inertia SSR** habilitado (`http://127.0.0.1:13714`)
- **Fortify**: registration, password reset, email verification, 2FA activados
- **DB por defecto**: SQLite (`database/database.sqlite`). Session/queue/cache usan driver `database`.
- **DB de testing**: SQLite in-memory (`:memory:`) — no necesita DB externa.
- **Alias**: `@/*` → `resources/js/*`
- **shadcn-vue**: estilo `new-york-v4`, iconos lucide, color base neutral. Config en `components.json`.
- **Resolución de layouts Inertia**: En `resources/js/app.ts`:
  - `Welcome` → sin layout
  - `auth/*` → AuthLayout
  - `settings/*` → AppLayout + SettingsLayout
  - resto → AppLayout

---

## Notas para Agentes

1. **Nuevos módulos backend:** Crear mirror en cada capa: Controller, Model, Service, Repository, DTO, Form Request, Observer según corresponda.
2. **Nuevos módulos frontend:** Crear página en `pages/<Dominio>/`, composable en `composables/<Dominio>/`, tipos en `types/<Dominio>/`. Componentes compartidos en `components/`.
3. **Ruteo:** Preferir `Route::resource()` para CRUD con `only`/`except` según el caso.
4. **Fortify:** Login rate limit: 5 req/min por email+IP. Password update throttle: 6,1.
5. **PDFs:** `barryvdh/laravel-dompdf` para exportaciones (ej. Kardex PDF).
6. **AJAX:** Endpoints bajo `/api/*` en web.php para selects y validaciones (ej. `/api/suppliers/{supplier}/pending-invoices`).
7. **Observers:** Úsarlos para lógica derivada post crear/actualizar (ej. actualizar stock, costo promedio, saldos).
8. **Al refactorizar controladores existentes:** Mover validación → Form Request, lógica de negocio → Service o Action, queries → Repository, efectos secundarios → Observer. Un archivo a la vez, sin refactors masivos.
9. **Modelos:** Sigue la sección *Arquitectura de Modelos*. Modelos ricos (con scopes, relaciones, casts, lógica interna) — las capas superiores se apoyan en ellos.
10. **shadcn-vue:** Para añadir nuevos componentes UI, usa el CLI `shadcn-vue add <componente>`. No los crees manualmente.
11. **Caching estratégico:** Usa `Cache::remember()` / `Cache::tags()` para datos consultados frecuentemente que cambian con poca frecuencia:
    - Listados maestros (productos, categorías, marcas, clientes, proveedores).
    - Tasas de cambio (mientras no haya una actualización, sirve la cacheada).
    - Reportes o agregaciones (kardex, totals de dashboard).
    - **NO** cachear operaciones transaccionales (crear orden, pagar, ajustar inventario).
    - Si tu caché usa el driver `database` (como en este proyecto), evalúa cambiarlo a `file` o `redis` en producción si los listados crecen mucho.
12. **mvs_factor (movements_sales):** Relación entre `sale_orders` y `movements_sales`:

    | Código | Factor | Significado |
    |--------|--------|-------------|
    | FACV (Factura Venta) | `-1` | Venta — reduce inventario |
    | DEVV (Devolución Venta) | `1` | Retorno — aumenta inventario |
    | FAC (Factura Compra) | `1` | Compra — aumenta inventario |
    | DEVC (Devolución Compra) | `-1` | Devolución — reduce inventario |

    Para COGS: INNER JOIN por `sls_number ↔ mvs_number` **y** `sls_trans_type_code ↔ mvs_trans_type_code`, filtrar `FACV` y `mvs_factor = '-1'`.

## Límites y overrides por plan

### Fuente de verdad
- `plans.limits` (jsonb): mapa de límites del plan. Clave ausente o `null` = **sin límite**; `0` = **bloqueo total**.
- `tenant_limit_overrides`: excepción por tenant. **Tiene prioridad sobre el plan.**
- `app/Enum/Tenancy/TenantLimit.php`: catálogo único de claves, etiquetas, alcance (`monthly`/`total`) y agrupación. Toda clave nueva se agrega aquí.
- `app/Enum/Tenancy/TenantFeature.php`: submódulos opcionales dentro de módulos core (ej. `sales.quotes`). `plans.features` es una **lista de features habilitadas**; `null` = todas habilitadas.

### Claves actuales

| Clave | Etiqueta | Alcance |
|---|---|---|
| `sales.invoices_monthly` | Facturas de venta mensuales | mensual (`created_at`) |
| `sales.quotes_monthly` | Cotizaciones mensuales | mensual (`created_at`) |
| `sales.returns_monthly` | Devoluciones de venta mensuales | mensual (`created_at`) |
| `purchases.returns_monthly` | Devoluciones de compra mensuales | mensual (`created_at`) |
| `customers.max_total` | Clientes | total histórico |
| `suppliers.max_total` | Proveedores | total histórico |
| `warehouses.max_total` | Almacenes | total histórico |
| `products.max_total` | Productos | total histórico |

### Conteo (en vivo)
- **Mensual:** `created_at` dentro de `now()->startOfMonth()` … `now()->endOfMonth()`.
- **Total:** `count()` de la tabla.
- `app/Services/Tenancy/PlanUsageCounter.php` centraliza los conteos. **No** usar contadores manuales. La tabla `tenant_usages` quedó como legado sin uso.

### Enforcement (doble barrera)
1. **Form Request:** `new WithinPlanLimit(TenantLimit::X)` en el campo principal del `Store*Request`.
2. **Servicio/Acción:** `UsageLimiter::assertCanCreate(TenantLimit::X)` como defensa para flujos no-HTTP.
- `ConvertToSaleAction` también valida `sales.invoices_monthly` (conversión cotización → factura).
- Los límites aplican **solo a creación**, no a `update`.

### Overrides por tenant
- Tabla `tenant_limit_overrides` (`tenant_id` string, `key`, `value` unsigned int, unique `tenant_id+key`, FK a `tenants`).
- `UsageLimiter::getLimit()` consulta primero el override y luego `plans.limits`.
- Admin: `PUT /admin/tenants/{tenant}/limit-overrides` → `TenantLimitOverrideService::sync()`.
- UI en `resources/js/pages/admin/tenants/Show.vue`. Vacío = elimina el override (vuelve al plan); `0` = bloqueo total.

### Admin de planes
- `PlanController` pasa `limit_groups` (`TenantLimit::grouped()`); `create.vue`/`edit.vue` renderizan los inputs dinámicamente.
- Validación: `limits` como arreglo + `limits.*` nullable integer min:0. Las claves desconocidas se rechazan con `after()`.
- **No usar `array:clave.con.puntos`** en validación: Laravel codifica los puntos de las claves y la regla falla. Usar `after()` con `$this->input()`.

### Cómo agregar un límite nuevo
1. Agregar el caso en `TenantLimit` (label, scope, group).
2. Implementar el conteo en `PlanUsageCounter`.
3. Añadir `new WithinPlanLimit(...)` al `Store*Request` correspondiente.
4. Añadir `assertCanCreate(...)` en el servicio/acción.
5. La UI de planes y tenants se actualiza sola vía `grouped()`.
6. Añadir tests de conteo, enforcement y override.

### Notas
- `QueryException` ya **no** se mapea a 422; ahora responde 500, para no disfrazar fallos de BD como validación.
- Las rutas `/quotes/*` están protegidas con `feature:sales.quotes` y el menú se oculta desde `tenant.features`.
- Endpoint admin de overrides documentado en `routes/admin.php`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
