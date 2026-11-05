<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Settings\TenantAuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataStatusController extends Controller
{
    /**
     * @var array<string, array{model: class-string<Model>, permission: string, label: string, name: string, search: list<string>}>
     */
    private const RESOURCES = [
        'products' => ['model' => Product::class, 'permission' => 'inventory.products', 'label' => 'Productos', 'name' => 'pro_name', 'search' => ['pro_code', 'pro_name']],
        'categories' => ['model' => Category::class, 'permission' => 'inventory.categories', 'label' => 'Categorías', 'name' => 'cat_name', 'search' => ['cat_name']],
        'brands' => ['model' => Brand::class, 'permission' => 'inventory.brands', 'label' => 'Marcas', 'name' => 'bra_name', 'search' => ['bra_name']],
        'warehouses' => ['model' => Warehouse::class, 'permission' => 'inventory.warehouses', 'label' => 'Almacenes', 'name' => 'war_name', 'search' => ['war_name']],
        'customers' => ['model' => Customer::class, 'permission' => 'sales.customers', 'label' => 'Clientes', 'name' => 'clt_name', 'search' => ['clt_name', 'clt_cedula', 'clt_email']],
        'suppliers' => ['model' => Supplier::class, 'permission' => 'purchases.suppliers', 'label' => 'Proveedores', 'name' => 'supplier_name', 'search' => ['supplier_name', 'supplier_email']],
    ];

    public function __construct(
        private readonly TenantAuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $search = (string) $request->get('search', '');

        $resources = [];

        foreach (self::RESOURCES as $key => $config) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $config['model'];

            $items = $modelClass::query()
                ->when($search !== '', function (Builder $query) use ($config, $search): void {
                    $query->where(function (Builder $inner) use ($config, $search): void {
                        foreach ($config['search'] as $column) {
                            $inner->orWhere($column, 'like', "%{$search}%");
                        }
                    });
                })
                ->orderBy($config['name'])
                ->limit(100)
                ->get(['id', $config['name'], 'is_active']);

            $resources[] = [
                'key' => $key,
                'label' => $config['label'],
                'items' => $items->map(fn (Model $item): array => [
                    'id' => $item->getKey(),
                    'name' => (string) $item->getAttribute($config['name']),
                    'is_active' => $item->isActive(),
                ])->values()->all(),
            ];
        }

        return Inertia::render('settings/MasterData', [
            'resources' => $resources,
            'filters' => ['search' => $search],
        ]);
    }

    public function toggle(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = self::RESOURCES[$resource] ?? null;

        abort_if($config === null, 404);

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $model = $modelClass::query()->findOrFail($id);

        $activating = ! $model->isActive();
        $permission = $config['permission'].($activating ? '.activate' : '.deactivate');

        abort_unless($request->user()?->can($permission), 403);

        $activating ? $model->activate() : $model->deactivate();

        $this->audit->log(
            $activating ? 'master_data.activated' : 'master_data.deactivated',
            $model,
            ['is_active' => ! $activating],
            ['is_active' => $activating],
        );

        return back()->with('success', $config['label'].' actualizado.');
    }
}
