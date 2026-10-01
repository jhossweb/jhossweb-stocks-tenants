import { computed, ref } from 'vue';
import type { Ref } from 'vue';

export type CurrencyMode = 'usd' | 'multi';

const STORAGE_KEY = 'dashboard:currency-mode';

function isCurrencyMode(value: unknown): value is CurrencyMode {
    return value === 'usd' || value === 'multi';
}

/**
 * Preferencia de visualización de los KPI: un único total en dólares o el
 * desglose por moneda.
 *
 * El estado vive en la página que lo crea y baja por props, no en el módulo:
 * con Inertia SSR un estado a nivel de módulo se compartiría entre usuarios
 * en el servidor. Persiste en localStorage para que la preferencia sobreviva a
 * la navegación, con guarda porque en SSR no existe `window`.
 */
export function useCurrencyMode(initial: CurrencyMode = 'usd') {
    const mode = ref<CurrencyMode>(readStoredMode() ?? initial);

    function isUsd(): boolean {
        return mode.value === 'usd';
    }

    function setMode(next: CurrencyMode): void {
        mode.value = next;
        persist(next);
    }

    function toggle(): void {
        setMode(mode.value === 'usd' ? 'multi' : 'usd');
    }

    return {
        mode: mode as Ref<CurrencyMode>,
        isUsd: computed(isUsd),
        canShowUsd: computed(() => isUsd()),
        setMode,
        toggle,
    };
}

function readStoredMode(): CurrencyMode | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return isCurrencyMode(stored) ? stored : null;
    } catch {
        // Modo privado o storage bloqueado: la preferencia simplemente no persiste.
        return null;
    }
}

function persist(mode: CurrencyMode): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(STORAGE_KEY, mode);
    } catch {
        // Sin storage disponible la preferencia vive sólo en la sesión actual.
    }
}
