<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\AccountPayments;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\SaleOrder;
use App\Services\Documents\DocumentLifecycleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentLifecycleController extends Controller
{
    /**
     * @var array<string, array{model: class-string<Model>, label: string, permission: string}>
     */
    private const TYPES = [
        'quotes' => ['model' => SaleOrder::class, 'label' => 'Cotización', 'permission' => 'sales.quotes'],
        'sales' => ['model' => SaleOrder::class, 'label' => 'Factura de venta', 'permission' => 'sales.invoices'],
        'sale-returns' => ['model' => SaleOrder::class, 'label' => 'Devolución de venta', 'permission' => 'sales.returns'],
        'purchases' => ['model' => PurchaseOrder::class, 'label' => 'Factura de compra', 'permission' => 'purchases.invoices'],
        'purchase-returns' => ['model' => PurchaseOrder::class, 'label' => 'Devolución de compra', 'permission' => 'purchases.returns'],
        'receipts' => ['model' => Receipt::class, 'label' => 'Cobro', 'permission' => 'sales.receipts'],
        'payments' => ['model' => AccountPayments::class, 'label' => 'Pago', 'permission' => 'purchases.payments'],
        'inventory-adjustments' => ['model' => Inventory::class, 'label' => 'Ajuste de inventario', 'permission' => 'inventory.adjustments'],
        'expenses' => ['model' => Expense::class, 'label' => 'Gasto', 'permission' => 'expenses.expenses'],
        'payrolls' => ['model' => Payroll::class, 'label' => 'Nómina', 'permission' => 'payroll.payrolls'],
        'payroll-periods' => ['model' => PayrollPeriod::class, 'label' => 'Período de nómina', 'permission' => 'payroll.periods'],
    ];

    public function __construct(
        private readonly DocumentLifecycleService $lifecycle,
    ) {}

    public function activate(Request $request): RedirectResponse
    {
        $type = (string) $request->route('type');
        $id = (int) $request->route('id');
        $document = $this->resolve($type, $id);

        abort_unless($request->user()?->can(self::TYPES[$type]['permission'].'.activate'), 403);

        try {
            $this->lifecycle->activate($document);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', self::TYPES[$type]['label'].' activado.');
    }

    public function deactivate(Request $request): RedirectResponse
    {
        $type = (string) $request->route('type');
        $id = (int) $request->route('id');
        $document = $this->resolve($type, $id);

        abort_unless($request->user()?->can(self::TYPES[$type]['permission'].'.deactivate'), 403);

        try {
            $this->lifecycle->deactivate($document, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', self::TYPES[$type]['label'].' inactivado.');
    }

    public function void(Request $request): RedirectResponse
    {
        $type = (string) $request->route('type');
        $id = (int) $request->route('id');
        $document = $this->resolve($type, $id);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        abort_unless($request->user()?->can(self::TYPES[$type]['permission'].'.void'), 403);

        try {
            $this->lifecycle->void($document, $request->user(), $validated['reason']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', self::TYPES[$type]['label'].' anulado.');
    }

    private function resolve(string $type, int $id): Model
    {
        $config = self::TYPES[$type] ?? null;
        abort_if($config === null, 404);

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $query = $modelClass::query();

        if ($type === 'quotes') {
            $query->where('sls_trans_type_code', 'COT');
        } elseif ($type === 'sales') {
            $query->where('sls_trans_type_code', 'FACV');
        } elseif ($type === 'sale-returns') {
            $query->where('sls_trans_type_code', 'DEVV');
        } elseif ($type === 'purchases') {
            $query->where('invoice_trans_type_code', 'FAC');
        } elseif ($type === 'purchase-returns') {
            $query->where('invoice_trans_type_code', 'DEVC');
        }

        return $query->findOrFail($id);
    }
}
