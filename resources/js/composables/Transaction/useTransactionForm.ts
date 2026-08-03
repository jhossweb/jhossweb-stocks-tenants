import type { Ref } from 'vue';

interface RateData {
    rate_date: string;
    rate_usd: number | string;
    rate_cop: number | string;
    rate_eur: number | string;
    [key: string]: unknown;
}

interface LatestRateData {
    rate_usd: number | string;
    rate_cop: number | string;
    rate_eur: number | string;
    [key: string]: unknown;
}

interface Product {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    pro_iva: string;
    pro_conversion_factor?: number;
    [key: string]: unknown;
}

interface Warehouse {
    id: number;
    [key: string]: unknown;
}

export function useTransactionForm(
    products: Ref<Product[]>,
    warehouses: Ref<Warehouse[]>,
    rates: Ref<RateData[]>,
    latestRate: Ref<LatestRateData | null>,
) {
    const getBestRateForDate = (date: string): RateData | null => {
        if (!rates.value || rates.value.length === 0) {
            return null;
        }

        const exact = rates.value.find(r => r.rate_date === date);

        if (exact) {
            return exact;
        }

        const past = rates.value
            .filter(r => r.rate_date < date)
            .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

        return past.length > 0 ? past[0] : rates.value[0];
    };

    const resolveRateValue = (coin: string, rate: RateData | LatestRateData): number => {
        if (coin === 'USD' || coin === 'BS' || coin === 'VES') {
            return Number(rate.rate_usd);
        }

        if (coin === 'COP') {
            return Number(rate.rate_cop);
        }

        if (coin === 'EUR') {
            return Number(rate.rate_eur);
        }

        return Number(rate.rate_usd);
    };

    const updateExchangeRate = (
        date: string,
        coin: string,
        valorcam: Ref<number>,
        onUpdate?: () => void,
    ): void => {
        const today = new Date().toISOString().split('T')[0];

        let newRate: number;

        if (date === today && latestRate.value) {
            newRate = resolveRateValue(coin, latestRate.value);
        } else {
            const selected = getBestRateForDate(date);
            newRate = selected ? resolveRateValue(coin, selected) : 1;
        }

        valorcam.value = Number(newRate.toFixed(4));
        onUpdate?.();
    };

    const getProduct = (code: string): Product | undefined =>
        products.value.find(p => p.pro_code === code);

    const getProductIvaRate = (code: string): number => {
        const p = getProduct(code);

        return p?.pro_iva ? Number(p.pro_iva) / 100 : 0;
    };

    const getProductIvaLabel = (code: string): string => {
        const p = getProduct(code);

        if (!p?.pro_iva) {
            return '-';
        }

        return p.pro_iva === '0' ? 'EX' : `${p.pro_iva}%`;
    };

    const getProductUnd = (code: string): string => getProduct(code)?.pro_und ?? '';

    const getProductConversion = (code: string): number => getProduct(code)?.pro_conversion_factor ?? 1;

    const undLabelMap: Record<string, string> = {
        und: 'Und',
        mts: 'Mts',
        kg: 'Kg',
        m2: 'm²',
    };

    const getUndLabel = (und: string): string => undLabelMap[und] || und;

    const getWarehouseName = (id: string | number): string => {
        const w = warehouses.value.find(w => w.id == id);

        return w?.war_name ?? String(w?.id ?? '');
    };

    const getItemTotal = (item: {
        mov_quantity: number | string;
        mov_unit_cost: number | string;
        mov_pro_code: string;
    }): string => {
        const qty = Number(item.mov_quantity) || 0;
        const cost = Number(item.mov_unit_cost) || 0;
        const base = qty * cost;
        const ivaRate = getProductIvaRate(item.mov_pro_code);

        return (base + base * ivaRate).toFixed(4);
    };

    const calculateTotals = <T extends { mov_pro_code: string; mov_quantity: number | string; mov_unit_cost: number | string; mov_pro_iva: number }>(
        items: T[],
        setSubtotal: (v: number) => void,
        setIva: (v: number) => void,
        setExento: (v: number) => void,
        setTotal: (v: number) => void,
    ): void => {
        let subtotal = 0;
        let sumIva = 0;
        let exento = 0;

        for (const item of items) {
            const lineTotal = Number(item.mov_quantity) * Number(item.mov_unit_cost);
            subtotal += lineTotal;
            const ivaRate = getProductIvaRate(item.mov_pro_code);
            const ivaAmount = lineTotal * ivaRate;
            sumIva += ivaAmount;
            item.mov_pro_iva = ivaRate * 100;

            if (ivaRate === 0) {
                exento += lineTotal;
            }
        }

        setSubtotal(Number(subtotal.toFixed(4)));
        setIva(Number(sumIva.toFixed(4)));
        setExento(Number(exento.toFixed(4)));
        setTotal(Number((subtotal + sumIva).toFixed(4)));
    };

    const updateCostRef = (
        coin: string,
        valorcam: number,
        item: { mov_unit_cost: number | string; mov_unit_cost_ref?: number },
    ): void => {
        const cost = Number(item.mov_unit_cost) || 0;
        const rate = valorcam || 1;

        let ref = cost;

        if (coin !== 'USD') {
            ref = cost / rate;
        }

        item.mov_unit_cost_ref = Number(ref.toFixed(4));
    };

    const addItem = <T>(items: T[], createBlank: () => T): void => {
        items.push(createBlank());
    };

    const removeItem = <T>(items: T[], index: number, recalc: () => void): void => {
        if (items.length > 1) {
            items.splice(index, 1);
            recalc();
        }
    };

    const getSupplierName = (suppliers: { id: number; supplier_name?: string; name?: string }[], id: string | number): string => {
        const s = suppliers.find(s => s.id == id);

        return s ? (s.supplier_name || s.name || '') : 'Seleccione proveedor';
    };

    const getProductName = (code: string): string => {
        const p = getProduct(code);

        return p ? p.pro_name : 'Seleccione producto';
    };

    const getCustomerName = (customers: { id: number; customer_name?: string; name?: string; clt_name?: string }[], id: string | number): string => {
        const c = customers.find(c => c.id == id);

        return c ? (c.customer_name || c.name || c.clt_name || '') : 'Seleccione cliente';
    };

    return {
        getBestRateForDate,
        resolveRateValue,
        updateExchangeRate,
        getProduct,
        getProductIvaRate,
        getProductIvaLabel,
        getProductUnd,
        getProductConversion,
        getUndLabel,
        getWarehouseName,
        getItemTotal,
        calculateTotals,
        updateCostRef,
        addItem,
        removeItem,
        getSupplierName,
        getProductName,
        getCustomerName,
    };
}
