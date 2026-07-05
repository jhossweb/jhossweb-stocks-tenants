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
9. **Modelos:** Aprovechar scopes, relaciones, casts. No modelos anémicos.
10. **shadcn-vue:** Para añadir nuevos componentes UI, usa el CLI `shadcn-vue add <componente>`. No los crees manualmente.
11. **Caching estratégico:** Usa `Cache::remember()` / `Cache::tags()` para datos consultados frecuentemente que cambian con poca frecuencia:
    - Listados maestros (productos, categorías, marcas, clientes, proveedores).
    - Tasas de cambio (mientras no haya una actualización, sirve la cacheada).
    - Reportes o agregaciones (kardex, totals de dashboard).
    - **NO** cachear operaciones transaccionales (crear orden, pagar, ajustar inventario).
    - Si tu caché usa el driver `database` (como en este proyecto), evalúa cambiarlo a `file` o `redis` en producción si los listados crecen mucho.
