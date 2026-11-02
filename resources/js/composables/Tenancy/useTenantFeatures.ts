import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type TenantFeatureStates = Record<string, boolean>;

export type SharedTenant = {
    id: string;
    domain: string | null;
    features?: TenantFeatureStates;
};

export function useTenantFeatures() {
    const page = usePage();

    const features = computed<TenantFeatureStates>(() => {
        const tenant = page.props.tenant as SharedTenant | null | undefined;

        return tenant?.features ?? {};
    });

    /**
     * Una funcionalidad ausente en el mapa se considera habilitada para
     * mantener compatibilidad con planes que aún no la definen.
     */
    function hasFeature(key: string): boolean {
        return features.value[key] !== false;
    }

    return { features, hasFeature };
}
