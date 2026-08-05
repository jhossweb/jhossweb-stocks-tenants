import type { Ref } from 'vue';
import type {
    QuoteCustomer,
    QuoteItemForm,
    QuoteProduct,
    QuoteRate,
    QuoteWarehouse,
} from '@/types/Quotes';

interface LatestRateData {
    rate_usd: number | string;
    rate_cop: number | string;
    rate_eur: number | string;
    rate_date?: string;
    [key: string]: unknown;
}

export function useQuoteForm(
    products: Ref<QuoteProduct[]>,
    warehouses: Ref<QuoteWarehouse[]>,
    rates: Ref<QuoteRate[]>,
    latestRate: Ref<LatestRateData | null>,
) {
    const getBestRateForDate = (date: string): QuoteRate | null => {
        if (!rates.value || rates.value.length === 0) {
            return null;
        }

        const exact = rates.value.find((r) => r.rate_date === date);

        if (exact) {
            return exact;
        }

        const past = rates.value
            .filter((r) => r.rate_date < date)
            .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

        return past.length > 0 ? past[0] : rates.value[0];
    };

    const resolveRateValue = (
        coin: string,
        rate: QuoteRate | LatestRateData,
    ): number => {
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

    const getProduct = (code: string): QuoteProduct | undefined =>
        products.value.find((p) => p.pro_code === code);

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

    const getProductUnd = (code: string): string =>
        getProduct(code)?.pro_und ?? '';

    const undLabelMap: Record<string, string> = {
        und: 'Und',
        mts: 'Mts',
        kg: 'Kg',
        m2: 'm²',
    };

    const getUndLabel = (und: string): string => undLabelMap[und] || und;

    const getProductName = (code: string): string =>
        getProduct(code)?.pro_name ?? 'Seleccione producto';

    const getWarehouseName = (id: string | number): string => {
        const w = warehouses.value.find((warehouse) => warehouse.id == id);

        return w?.war_name ?? String(w?.id ?? '');
    };

    const getCustomerName = (
        customers: QuoteCustomer[],
        id: string | number,
    ): string =>
        customers.find((c) => c.id == id)?.clt_name ?? 'Seleccione cliente';

    const getUnitPriceFromRef = (
        ref: number,
        coin: string,
        valorcam: number,
    ): number => (coin === 'USD' ? ref : ref * (valorcam || 1));

    const buildItemFromRef = (
        product: QuoteProduct,
        coin: string,
        valorcam: number,
    ): Partial<QuoteItemForm> => {
        const usdPrice = Number(product.latest_price) || 0;

        return {
            mvs_pro_code: product.pro_code,
            mvs_unit_price_ref: usdPrice,
            mvs_unit_price: getUnitPriceFromRef(usdPrice, coin, valorcam),
        };
    };

    const getItemTotal = (item: QuoteItemForm): number => {
        const baseTotal =
            Number(item.mvs_quantity) * Number(item.mvs_unit_price);
        const ivaRate = getProductIvaRate(item.mvs_pro_code);

        return baseTotal + baseTotal * ivaRate;
    };

    const recalc = (
        items: QuoteItemForm[],
        setters: {
            setSubtotal: (v: number) => void;
            setExento: (v: number) => void;
            setIva: (v: number) => void;
            setTotal: (v: number) => void;
        },
    ): void => {
        let subtotal = 0;
        let sumIva = 0;
        let exento = 0;

        for (const item of items) {
            const lineTotal =
                Number(item.mvs_quantity) * Number(item.mvs_unit_price);
            subtotal += lineTotal;
            const ivaRate = getProductIvaRate(item.mvs_pro_code);
            const ivaAmount = lineTotal * ivaRate;
            sumIva += ivaAmount;
            item.mvs_pro_iva = ivaRate * 100;

            if (ivaRate === 0) {
                exento += lineTotal;
            }
        }

        setters.setSubtotal(Number(subtotal.toFixed(4)));
        setters.setIva(Number(sumIva.toFixed(4)));
        setters.setExento(Number(exento.toFixed(4)));
        setters.setTotal(Number((subtotal + sumIva).toFixed(4)));
    };

    const addItem = (
        items: QuoteItemForm[],
        createBlank: () => QuoteItemForm,
    ): void => {
        items.push(createBlank());
    };

    const removeItem = (
        items: QuoteItemForm[],
        index: number,
        onRemove: () => void,
    ): void => {
        if (items.length > 1) {
            items.splice(index, 1);
            onRemove();
        }
    };

    const blankItem = (): QuoteItemForm => ({
        mvs_pro_code: '',
        mvs_war_id: '',
        mvs_quantity: 1,
        mvs_unit_price: 0,
        mvs_unit_price_ref: 0,
        mvs_pro_iva: 0,
        _pending: false,
    });

    return {
        getBestRateForDate,
        resolveRateValue,
        updateExchangeRate,
        getProduct,
        getProductIvaRate,
        getProductIvaLabel,
        getProductUnd,
        getUndLabel,
        getProductName,
        getWarehouseName,
        getCustomerName,
        getUnitPriceFromRef,
        buildItemFromRef,
        getItemTotal,
        recalc,
        addItem,
        removeItem,
        blankItem,
    };
}
