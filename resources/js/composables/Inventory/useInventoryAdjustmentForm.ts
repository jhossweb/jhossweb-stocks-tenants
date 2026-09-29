import { computed, ref } from 'vue';

import type {
    AdjustmentProduct,
    AdjustmentReason,
    InventoryAdjustmentItem,
    Warehouse,
} from '@/types/Inventory';

const UNIT_LABELS: Record<string, string> = {
    und: 'Und',
    mts: 'Mts',
    kg: 'Kg',
    m2: 'm²',
};

const NO_PRODUCT = 'Seleccione producto';
const NO_REASON = 'Seleccione';

/**
 * Lógica compartida por las páginas de create/edit de ajustes de inventario.
 *
 * `items` es el array reactivo que vive dentro del `useForm` de la página: el
 * composable no crea el formulario, solo opera sobre sus renglones.
 */
export function useInventoryAdjustmentForm(
    items: InventoryAdjustmentItem[],
    catalogs: {
        warehouses: Warehouse[];
        products: AdjustmentProduct[];
        reasons: AdjustmentReason[];
    },
) {
    // shadcn/reka Select maneja mejor valores string; el id numérico se
    // deriva con Number() para los cálculos de stock.
    const selectedWarehouseId = ref<string>('');

    const warehouseId = computed(() => Number(selectedWarehouseId.value) || 0);

    const hasReasons = computed(() => catalogs.reasons.length > 0);

    const hasWarehouses = computed(() => catalogs.warehouses.length > 0);

    const productsWithStock = computed(() => {
        if (!warehouseId.value) {
            return [];
        }

        return catalogs.products.filter((product) => stockOf(product) > 0);
    });

    function stockOf(product: AdjustmentProduct): number {
        return (
            product.stocks.find((stock) => stock.war_id === warehouseId.value)
                ?.cant ?? 0
        );
    }

    function stockForProduct(proCode: string): number {
        if (!proCode || !warehouseId.value) {
            return 0;
        }

        const product = catalogs.products.find(
            (candidate) => candidate.pro_code === proCode,
        );

        return product ? stockOf(product) : 0;
    }

    function productName(proCode: string): string {
        if (!proCode) {
            return NO_PRODUCT;
        }

        return (
            catalogs.products.find((product) => product.pro_code === proCode)
                ?.pro_name ?? NO_PRODUCT
        );
    }

    function unitLabel(proCode: string): string {
        const und = catalogs.products.find(
            (product) => product.pro_code === proCode,
        )?.pro_und;

        if (!und) {
            return '';
        }

        return UNIT_LABELS[und] ?? und;
    }

    function reasonName(reasonId: number | string): string {
        if (reasonId === '' || reasonId === 0) {
            return NO_REASON;
        }

        return (
            catalogs.reasons.find((reason) => reason.id === Number(reasonId))
                ?.adjr_name ?? NO_REASON
        );
    }

    function addItem(): void {
        items.push({
            min_pro_code: '',
            min_war_id: warehouseId.value,
            min_quantity: 1,
            min_reason_id: '',
        });
    }

    function removeItem(index: number): void {
        items.splice(index, 1);
    }

    function selectProduct(index: number, product: AdjustmentProduct): void {
        items[index].min_pro_code = product.pro_code;
        items[index].min_war_id = warehouseId.value;
    }

    function formatStock(value: number | string): string {
        return Number(value).toFixed(4);
    }

    return {
        selectedWarehouseId,
        warehouseId,
        hasReasons,
        hasWarehouses,
        productsWithStock,
        stockForProduct,
        productName,
        unitLabel,
        reasonName,
        addItem,
        removeItem,
        selectProduct,
        formatStock,
    };
}
