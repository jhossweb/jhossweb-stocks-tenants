<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

/**
 * Catálogo fijo de permisos del tenant.
 *
 * Los permisos viven en código para que las rutas puedan verificarlos con
 * nombres estables. Los tenants crean roles y asignan permisos de este
 * catálogo, pero no crean ni renombran permisos.
 */
final class TenantPermission
{
    /** @var array<string, string> */
    public const ACTION_LABELS = [
        'view' => 'Ver',
        'create' => 'Crear',
        'update' => 'Editar',
        'delete' => 'Eliminar',
        'export' => 'Exportar',
        'activate' => 'Activar',
        'deactivate' => 'Inactivar',
        'void' => 'Anular',
        'convert' => 'Convertir',
        'pay' => 'Pagar',
        'cancel_payment' => 'Cancelar pago',
        'generate' => 'Generar',
        'mark_paid' => 'Marcar pagado',
    ];

    /** @var array<string, string> */
    public const GROUP_LABELS = [
        'dashboard' => 'Dashboard',
        'reports' => 'Reportes',
        'inventory' => 'Inventario',
        'sales' => 'Ventas',
        'purchases' => 'Compras',
        'finance' => 'Finanzas',
        'expenses' => 'Gastos',
        'payroll' => 'Nómina',
        'settings' => 'Configuración',
    ];

    /**
     * @var array<string, array{label: string, group: string, actions: list<string>}>
     */
    public const RESOURCES = [
        'dashboard' => ['label' => 'Dashboard', 'group' => 'dashboard', 'actions' => ['view']],
        'reports' => ['label' => 'Reportes', 'group' => 'reports', 'actions' => ['view', 'export']],

        'inventory.products' => ['label' => 'Productos', 'group' => 'inventory', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'inventory.categories' => ['label' => 'Categorías', 'group' => 'inventory', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'inventory.brands' => ['label' => 'Marcas', 'group' => 'inventory', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'inventory.warehouses' => ['label' => 'Almacenes', 'group' => 'inventory', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'inventory.adjustments' => ['label' => 'Ajustes de inventario', 'group' => 'inventory', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'inventory.prices' => ['label' => 'Listas de precios', 'group' => 'inventory', 'actions' => ['view', 'update', 'export']],
        'inventory.kardex' => ['label' => 'Kardex', 'group' => 'inventory', 'actions' => ['view', 'export']],

        'sales.invoices' => ['label' => 'Facturas de venta', 'group' => 'sales', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'sales.quotes' => ['label' => 'Cotizaciones', 'group' => 'sales', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void', 'convert']],
        'sales.returns' => ['label' => 'Devoluciones de venta', 'group' => 'sales', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'sales.customers' => ['label' => 'Clientes', 'group' => 'sales', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'sales.receivables' => ['label' => 'Cuentas por cobrar', 'group' => 'sales', 'actions' => ['view', 'export']],
        'sales.receipts' => ['label' => 'Cobros', 'group' => 'sales', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'void']],

        'purchases.invoices' => ['label' => 'Facturas de compra', 'group' => 'purchases', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'purchases.returns' => ['label' => 'Devoluciones de compra', 'group' => 'purchases', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'purchases.suppliers' => ['label' => 'Proveedores', 'group' => 'purchases', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'purchases.payables' => ['label' => 'Cuentas por pagar', 'group' => 'purchases', 'actions' => ['view', 'export']],
        'purchases.payments' => ['label' => 'Pagos a proveedores', 'group' => 'purchases', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'void']],

        'finance.accounts' => ['label' => 'Cuentas bancarias', 'group' => 'finance', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'finance.transfers' => ['label' => 'Transferencias bancarias', 'group' => 'finance', 'actions' => ['view', 'create', 'void']],
        'finance.analysis' => ['label' => 'Análisis bancario', 'group' => 'finance', 'actions' => ['view', 'export']],
        'finance.cash_closing' => ['label' => 'Cierre de caja', 'group' => 'finance', 'actions' => ['view', 'create', 'export', 'void']],

        'expenses.expenses' => ['label' => 'Gastos', 'group' => 'expenses', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void', 'pay', 'cancel_payment']],
        'expenses.categories' => ['label' => 'Categorías de gastos', 'group' => 'expenses', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],

        'payroll.employees' => ['label' => 'Empleados', 'group' => 'payroll', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate']],
        'payroll.periods' => ['label' => 'Períodos de nómina', 'group' => 'payroll', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'payroll.payrolls' => ['label' => 'Nóminas', 'group' => 'payroll', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void', 'generate', 'mark_paid']],
        'payroll.loans' => ['label' => 'Préstamos', 'group' => 'payroll', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'payroll.damages' => ['label' => 'Daños', 'group' => 'payroll', 'actions' => ['view', 'create', 'update', 'delete', 'export', 'activate', 'deactivate', 'void']],
        'payroll.deductions' => ['label' => 'Deducciones', 'group' => 'payroll', 'actions' => ['create', 'delete']],

        'settings.company' => ['label' => 'Datos de la empresa', 'group' => 'settings', 'actions' => ['view', 'update']],
        'settings.rates' => ['label' => 'Tasas de cambio', 'group' => 'settings', 'actions' => ['view', 'create', 'update', 'delete', 'export']],
        'settings.users' => ['label' => 'Usuarios', 'group' => 'settings', 'actions' => ['view', 'create', 'update', 'deactivate', 'activate', 'export']],
        'settings.roles' => ['label' => 'Roles', 'group' => 'settings', 'actions' => ['view', 'create', 'update', 'delete', 'export']],
        'settings.permissions' => ['label' => 'Permisos', 'group' => 'settings', 'actions' => ['view', 'export']],
        'settings.master_data' => ['label' => 'Estados de datos maestros', 'group' => 'settings', 'actions' => ['view', 'update']],
        'settings.audit' => ['label' => 'Auditoría', 'group' => 'settings', 'actions' => ['view', 'export']],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        $keys = [];

        foreach (self::RESOURCES as $resource => $definition) {
            foreach ($definition['actions'] as $action) {
                $keys[] = $resource.'.'.$action;
            }
        }

        return $keys;
    }

    public static function exists(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    public static function labelFor(string $key): string
    {
        [$resource, $action] = self::split($key);

        $resourceLabel = self::RESOURCES[$resource]['label'] ?? $resource;
        $actionLabel = self::ACTION_LABELS[$action] ?? $action;

        return "{$actionLabel} {$resourceLabel}";
    }

    public static function resourceFor(string $key): string
    {
        return self::split($key)[0];
    }

    public static function actionFor(string $key): string
    {
        return self::split($key)[1];
    }

    /**
     * Agrupa por módulo para el panel de roles.
     *
     * @return list<array{key: string, label: string, permissions: list<array{key: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::RESOURCES as $resource => $definition) {
            $group = $definition['group'];
            $groups[$group] ??= [
                'key' => $group,
                'label' => self::GROUP_LABELS[$group] ?? $group,
                'permissions' => [],
            ];

            foreach ($definition['actions'] as $action) {
                $key = $resource.'.'.$action;
                $groups[$group]['permissions'][] = [
                    'key' => $key,
                    'label' => self::labelFor($key),
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * Expande patrones tipo `sales.*` o `*` contra el catálogo.
     *
     * @param  list<string>  $patterns
     * @return list<string>
     */
    public static function expand(array $patterns): array
    {
        $all = self::keys();

        if (in_array('*', $patterns, true)) {
            return $all;
        }

        $result = [];

        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -1);
                foreach ($all as $key) {
                    if (str_starts_with($key, $prefix)) {
                        $result[] = $key;
                    }
                }

                continue;
            }

            if (in_array($pattern, $all, true)) {
                $result[] = $pattern;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function split(string $key): array
    {
        $position = strrpos($key, '.');

        if ($position === false) {
            return [$key, ''];
        }

        return [substr($key, 0, $position), substr($key, $position + 1)];
    }
}
