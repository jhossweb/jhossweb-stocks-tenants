import { computed, ref } from 'vue';

/**
 * Estado compartido para la página de registro de empresa/tenant.
 * Genera un slug en vivo a partir del nombre para previsualizar el dominio.
 */
export function useTenantRegistration(domainSuffix: string) {
    const companyName = ref('');

    const slug = computed(() => toSlug(companyName.value));

    const domainPreview = computed(() => `${slug.value}.${domainSuffix}`);

    const canSubmit = computed(() => slug.value.length >= 2);

    return { companyName, slug, domainPreview, canSubmit };
}

function toSlug(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
}
