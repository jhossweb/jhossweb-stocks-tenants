import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface MonthOption {
    value: string;
    label: string;
}

export function buildMonthOptions(): MonthOption[] {
    const options: MonthOption[] = [];
    const now = new Date();

    for (let i = 0; i < 12; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        const label = d.toLocaleDateString('es', {
            year: 'numeric',
            month: 'long',
        });
        options.push({ value, label });
    }

    return options;
}

export function monthLabel(month: string): string {
    const [year, monthNumber] = month.split('-').map(Number);
    const date = new Date(year, monthNumber - 1, 1);

    return date.toLocaleDateString('es', { year: 'numeric', month: 'long' });
}

export function useDashboardMonth(selectedMonth: string, currentPath: string) {
    const monthOptions = computed<MonthOption[]>(() => buildMonthOptions());

    function goTo(path: string) {
        router.get(path, { month: selectedMonth }, { preserveScroll: true });
    }

    function changeMonth(month: string) {
        router.get(
            currentPath,
            { month },
            { preserveState: true, preserveScroll: true },
        );
    }

    return {
        monthOptions,
        goTo,
        changeMonth,
    };
}
