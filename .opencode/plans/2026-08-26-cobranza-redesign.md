# Plan: Rediseño del Flujo de Cobranza (Receipts)

## Resumen

Reescribir `Finance/Receipts/Create.vue` como un wizard de 3 pasos con soporte multi-cmulti-currency, multi-bank, tasa auto-cargada (igual que Sales), y cambio/vuelto. El backend actual ya soporta 1 cobro = N receipts + N bank transactions — el cambio principal es el frontend y la validación.

---

## Problema Actual

- `Create.vue` actual es una página plana con un solo currency, un solo bank account, un solo rate.
- Si el cliente paga parcialmente en USD y parcialmente en BS, no se puede hacer en una sola operación.
- El usuario tiene que hacer 2 cobros separados para multi-currency.

## Objetivo

1. **Wizard de 3 pasos**: Datos del cliente → Facturas → Origen de fondos
2. **Multi-currency**: Cada línea de pago puede tener su propia moneda y banco
3. **Tasa auto-load**: Mismo patrón que Sales — `getBestRateForDate()` con `resolveRateValue()`
4. **1 cobro = N receipts**: Ya soportado por el backend, solo hay que usarlo

---

## Archivos a Modificar

### 1. `resources/js/pages/Finance/Receipts/Create.vue` — REESCRIBIR

**Cambios:**

#### Estructura Wizard
- Agregar `currentStep` ref (1, 2, 3)
- Botones Siguiente/Anterior entre pasos
- Header con título del paso actual + indicador de progreso

#### Step 1 — Datos del Cliente
- Selector de cliente (`SelectorModal`, igual que actual)
- Fecha de cobro
- Moneda de referencia (opcional, default USD)
- Tasa de cambio auto-load:
  - En `onMounted`: `getBestRateForDate(payment_date)` → `resolveRateValue(coin)` → set `payment_rate`
  - Watch en `payment_date` + `payment_currency`: re-load rate
  - Input editable (override manual)
- Validación: cliente obligatorio antes de pasar a Step 2

#### Step 2 — Facturas Pendientes
- Tabla de facturas del cliente (mismo fetch de `/api/customers/{id}/pending-receivables`)
- Columnas: Factura | Saldo Pendiente | Moneda | Monto a Cobrar
- Cada factura mantiene su moneda original
- Si la moneda del pago difiere de la moneda de la factura:
  - Mostrar monto convertido debajo del saldo
  - Usar `convertCurrency()` con la tasa actual
- Botón "MÁX" por fila (auto-fill al saldo convertido)
- Resumen: total a cobrar en moneda de referencia

#### Step 3 — Origen de Fondos (Multi-Bank)
- Lista de movimientos bancarios (cada uno = bank transaction)
- Cada movimiento tiene:
  - **Moneda** (Select: USD, BS, COP, EUR)
  - **Cuenta bancaria** (Select: filtrada por moneda del movimiento)
  - **Botón "+"** al lado del select de banco → abre `BankAccountCreateModal` (reusar componente existente de `resources/js/components/payments/BankAccountCreateModal.vue`)
    - El modal crea la cuenta vía `POST /api/bank-accounts` (endpoint AJAX existente)
    - Al crear, auto-selecciona la cuenta nueva en el select
    - La moneda del modal se sincroniza con la moneda del movimiento actual
    - `router.reload({ only: ['bankAccounts'] })` refresca la lista de cuentas
  - **Monto** (Input numérico)
  - **Tipo de transacción** (Select: TRANSFERENCIA, PAGO_MOVIL, EFECTIVO, etc.)
  - **Referencia** (Input)
  - **Observaciones** (Input)
- Botón "+ Agregar movimiento bancario" para añadir más filas
- Botón "X" para eliminar movimiento (mínimo 1)
- Validación: la suma de movimientos bancarios debe ser ≥ total a cobrar

#### Tarjeta de Resumen (sidebar)
- Total a liquidar (en moneda de referencia)
- Tasa aplicada
- Conversión de moneda (si aplica)
- Diferencial cambiario estimado
- **Vuelto** calculado si suma de movimientos > total

#### Submisión
- Form transform: agrupar todos los datos
- `form.payments` se mantiene igual (cada línea con `acr_id`, `amount`)
- Agregar `bank_movements` array (cada movimiento con `bank_account_id`, `currency`, `amount`, `method`, `reference`, `description`)
- El backend ya itera sobre `payments` y crea BankTransaction + Receipt por cada uno

### 1b. `resources/js/components/payments/BankAccountCreateModal.vue` — REUSAR (sin cambios)

Este componente ya existe y funciona con `useInlineCreate`. Se integra directamente en el Step 3 del wizard:

```vue
<!-- En cada fila de bank movement -->
<div class="flex items-center gap-2">
    <Select v-model="movement.bank_account_id" ...>
        ...
    </Select>
    <Button @click="openBankModalForMovement(index)" ...>
        <Plus class="w-4 h-4" />
    </Button>
</div>

<!-- Un solo modal, compartido para todas las filas -->
<BankAccountCreateModal
    ref="bankModalRef"
    :currency="movementCurrency"
    @created="(bank) => onBankCreated(index, bank)"
/>
```

**Flujo:**
1. Usuario hace click en "+" → `openBankModalForMovement(index)` guarda el índice activo
2. Modal se abre con la moneda del movimiento actual
3. Usuario crea la cuenta → `onBankCreated(index, bank)` auto-selecciona en el select
4. `router.reload({ only: ['bankAccounts'] })` refresca la lista para otros movimientos

**No se duplica código** — se reutiliza el mismo componente y composable.

### 2. `resources/js/composables/useReceiptForm.ts` — NUEVO

Composable que encapsula la lógica del wizard de cobranza:

```ts
export function useReceiptForm(props) {
  // Wizard state
  const currentStep = ref(1);
  const canProceed = computed(() => ...);

  // Rate logic (mismo patrón que useTransactionForm)
  const getBestRateForDate = (date: string) => ...;
  const resolveRateValue = (coin: string, rate) => ...;
  const updateExchangeRate = () => ...;

  // Invoice logic
  const pendingReceivables = ref([]);
  const fetchPendingReceivables = async (customerId) => ...;
  const calculateConvertedBalance = (invoice) => ...;

  // Bank movements
  const bankMovements = ref([{ bank_account_id, currency, amount, method, reference, description }]);
  const addBankMovement = () => ...;
  const removeBankMovement = (index) => ...;

  // Summary
  const totalToPay = computed(() => ...);
  const totalBankMovements = computed(() => ...);
  const vuelto = computed(() => ...);

  return { ... };
}
```

### 3. `app/Http/Requests/Finance/Cobranza/StoreReceiptRequest.php` — MODIFICAR

Cambiar la validación para soportar multi-currency:

```php
public function rules(): array
{
    return [
        'payment_date' => ['required', 'date'],
        'customer_id' => ['required', 'exists:customers,id'],
        'bank_movements' => ['required', 'array', 'min:1'],
        'bank_movements.*.bank_account_id' => ['required', 'exists:bank_accounts,id'],
        'bank_movements.*.currency' => ['required', 'string'],
        'bank_movements.*.amount' => ['required', 'numeric', 'min:0.0001'],
        'bank_movements.*.method' => ['required', 'string'],
        'bank_movements.*.reference' => ['required', 'string', 'max:50'],
        'bank_movements.*.description' => ['nullable', 'string', 'max:150'],
        'payments' => ['required', 'array', 'min:1'],
        'payments.*.acr_id' => ['required', 'exists:accounts_receivable,id'],
        'payments.*.amount' => ['required', 'numeric', 'min:0'],
        'apply_vuelto' => ['nullable', 'boolean'],
        'vueltos' => ['nullable', 'array'],
    ];
}
```

**NOTA**: Se eliminan `bank_account_id` (global), `payment_currency`, `payment_rate`, `payment_method` (ahora son por movimiento). Se agrega `bank_movements` como array.

### 4. `app/Services/Finance/Cobranza/ReceiptService.php` — MODIFICAR

Cambiar `processPayments()` para recibir `bank_movements` en vez de un solo bank account:

```php
public function processPayments(array $validated): void
{
    $activePayments = array_values(array_filter(
        $validated['payments'],
        fn ($p) => (float) $p['amount'] > 0
    ));

    $bankMovements = $validated['bank_movements'] ?? [];

    if (empty($activePayments)) {
        throw new \RuntimeException('Debe ingresar al menos un monto a cobrar.');
    }

    if (empty($bankMovements)) {
        throw new \RuntimeException('Debe registrar al menos un movimiento bancario.');
    }

    DB::transaction(function () use ($activePayments, $bankMovements, $validated) {
        // Para cada payment, encontrar el bank_movement correspondiente
        // (por currency o por índice)
        // Crear BankTransaction + Receipt por cada payment
        // Manejar vueltos si aplica
    });
}
```

### 5. `app/Http/Controllers/Finance/Cobranza/ReceiptController.php` — MODIFICAR

**`create()`**: No necesita cambios significativos — los props ya incluyen `rates`, `latestRate`, `currencies`, `methods`, `bankAccounts`.

**`store()`**: Actualizar para manejar la estructura nueva de `bank_movements`.

---

## Flujo de Datos (Multi-Currency)

```
Step 1: Customer → Date → Rate (auto-loaded)
Step 2: Invoices [USD: $500, BS: Bs 3500]
Step 3: Bank Movements [
  { bank: Banesco USD, currency: USD, amount: 500 },
  { bank: Mercantil BS, currency: BS, amount: 3500 }
]
```

Backend crea:
- BankTransaction 1: Banesco USD, inflow $500
- Receipt 1: AR → $500, USD
- BankTransaction 2: Mercantil BS, inflow Bs 3500
- Receipt 2: AR → Bs 3500, BS

---

## Validaciones Frontend

| Regla | Mensaje |
|-------|---------|
| Paso 1: customer obligatorio | "Seleccione un cliente" |
| Paso 2: al menos 1 factura con amount > 0 | "Ingrese al menos un monto a cobrar" |
| Paso 3: al menos 1 bank movement | "Registre al menos un movimiento bancario" |
| Paso 3: suma de movimientos ≥ total a cobrar | "Los movimientos bancarios no cubren el total" |
| Opcional: vuelto si suma > total | "Se generará un vuelto de X" |

---

## Tasa de Cambio (Patrón Sales)

```ts
const getBestRateForDate = (date: string) => {
  // 1. Buscar rate exacto para la fecha
  // 2. Si no hay, buscar el más reciente anterior
  // 3. Si no hay, usar rates[0]
};

const resolveRateValue = (coin: string, rate) => {
  // USD/BS/VES → rate.rate_usd
  // COP → rate.rate_cop
  // EUR → rate.rate_eur
};

const updateExchangeRate = () => {
  payment_rate.value = resolveRateValue(payment_currency.value, getBestRateForDate(payment_date.value));
  // Re-calcular montos convertidos en facturas
};
```

---

## Multi-Currency en Facturas

Cuando la moneda de pago difiere de la moneda de la factura:

```ts
// En la tabla de facturas
const convertedBalance = convertCurrency(
  invoice.balance,        // saldo en moneda original
  invoice.currency,       // moneda de la factura
  payment_currency,       // moneda de pago seleccionada
  currentRate             // tasa actual
);
```

El monto a cobrar se ingresa en la moneda de la factura. El saldo convertido se muestra como referencia.

---

## Implementación

### Fase 1: Backend (StoreReceiptRequest + ReceiptService)
1. Modificar `StoreReceiptRequest` para `bank_movements` array
2. Modificar `ReceiptService.processPayments()` para recibir bank_movements
3. Crear tests para el nuevo formato

### Fase 2: Frontend (Create.vue + composable)
1. Crear `useReceiptForm.ts` composable
2. Reescribir `Create.vue` con wizard de 3 pasos
3. Integrar `BankAccountCreateModal` en Step 3 (reusar componente existente)
4. Integrar `useCurrencyConversion` para multi-currency
5. Integrar `useFlashToast` para errores

### Fase 3: Testing
1. Tests para multi-currency (USD + BS en un solo cobro)
2. Tests para vuelto
3. Tests para validación de bank_movements

---

## Notas

- **No DB migration**: El esquema actual soporta 1 AR → N Receipts → N BankTransactions
- **Edit mode**: Se mantiene el flujo actual (un solo pago) — el wizard es solo para creación
- **vue-sonner**: Ya montado globalmente, usar `toast.success()` / `toast.error()`
- **Pint**: Ejecutar `vendor/bin/pint --dirty --format agent` después de cambios PHP
- **ESLint/Prettier**: Ejecutar después de cambios TS/Vue
