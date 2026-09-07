import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { ProductPriceRow } from '@/types/Inventory';

export interface PriceListFilters {
    q: string;
    category_id: string;
    brand_id: string;
    sin_precio: boolean;
    bajo_costo: boolean;
    sort: string;
}

export const emptyFilters = (): PriceListFilters => ({
    q: '',
    category_id: 'all',
    brand_id: 'all',
    sin_precio: false,
    bajo_costo: false,
    sort: 'name',
});

const DEBOUNCE_MS = 350;

/** Query string params equivalentes a los filtros activos. */
export function filtersToParams(
    filters: PriceListFilters,
): Record<string, string | number> {
    const params: Record<string, string | number> = {};

    if (filters.q.trim() !== '') {
        params.q = filters.q.trim();
    }

    if (filters.category_id !== 'all') {
        params.category_id = filters.category_id;
    }

    if (filters.brand_id !== 'all') {
        params.brand_id = filters.brand_id;
    }

    if (filters.sin_precio) {
        params.sin_precio = 1;
    }

    if (filters.bajo_costo) {
        params.bajo_costo = 1;
    }

    if (filters.sort !== 'name') {
        params.sort = filters.sort;
    }

    return params;
}

/**
 * Estado y acciones server-side de la Lista de Precios.
 * Los filtros viajan por query string; la recarga es parcial (only: rows).
 */
export function useProductPrices(filters: PriceListFilters) {
    const isFetching = ref(false);

    const applyFilters = () => {
        router.get('/inventory/list-prices', filtersToParams(filters), {
            only: ['rows', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                isFetching.value = true;
            },
            onFinish: () => {
                isFetching.value = false;
            },
        });
    };

    let debounceTimer: ReturnType<typeof setTimeout> | undefined;

    watch(
        () => [
            filters.q,
            filters.category_id,
            filters.brand_id,
            filters.sin_precio,
            filters.bajo_costo,
        ],
        () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilters, DEBOUNCE_MS);
        },
    );

    const setSort = (sort: string) => {
        filters.sort = sort;
        applyFilters();
    };

    const resetFilters = () => {
        Object.assign(filters, emptyFilters());
        applyFilters();
    };

    const hasActiveFilters = () =>
        Object.keys(filtersToParams(filters)).length > 0;

    return {
        applyFilters,
        setSort,
        resetFilters,
        hasActiveFilters,
        isFetching,
    };
}

export type PriceStatus = 'sin-precio' | 'bajo-costo' | 'ok';

export function priceStatus(row: ProductPriceRow): PriceStatus {
    if (row.last_price === null) {
        return 'sin-precio';
    }

    if (row.last_cost > 0 && row.last_price <= row.last_cost) {
        return 'bajo-costo';
    }

    return 'ok';
}

export function formatUsd(value: number | null, decimals = 4): string {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(value);
}

export function formatPercent(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value) + '%'
    );
}

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat('es', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${iso}T00:00:00`));
}
