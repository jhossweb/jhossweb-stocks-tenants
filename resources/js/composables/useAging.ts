import type { AgingSummary } from '@/types/Sales';

export interface AgingRow {
    key: keyof AgingSummary;
    label: string;
    value: number;
    class: string;
}

interface AgingBucketMeta {
    key: keyof AgingSummary;
    label: string;
    class: string;
}

const AGING_BUCKETS: AgingBucketMeta[] = [
    { key: 'current', label: 'Al día', class: 'text-emerald-400' },
    { key: 'days_1_30', label: '1-30', class: 'text-lime-400' },
    { key: 'days_31_60', label: '31-60', class: 'text-amber-400' },
    { key: 'days_61_90', label: '61-90', class: 'text-orange-400' },
    { key: 'days_over_90', label: '+90', class: 'text-red-400' },
];

export const useAging = () => {
    const agingRows = (aging: AgingSummary): AgingRow[] =>
        AGING_BUCKETS.map(({ key, label, class: bucketClass }) => ({
            key,
            label,
            value: aging[key] ?? 0,
            class: bucketClass,
        }));

    const dayBadge = (days: number) => {
        if (days < 0) {
            return {
                class: 'bg-red-900/30 text-red-400',
                label: `${Math.abs(days)} días vencido`,
            };
        }

        if (days === 0) {
            return {
                class: 'bg-amber-900/30 text-amber-400',
                label: 'Vence hoy',
            };
        }

        return {
            class: 'bg-emerald-900/30 text-emerald-400',
            label: `${days} días`,
        };
    };

    return { agingRows, dayBadge };
};
