import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface InlineCreateOptions {
    endpoint: string;
    /** Prop de página que se recarga parcialmente tras crear (sin remontar la vista). */
    refreshProp?: string;
}

/**
 * Crea entidades relacionadas desde un modal, sin navegar fuera del
 * formulario actual. Envía el payload por fetch al endpoint AJAX y
 * devuelve la entidad creada para poder auto-seleccionarla.
 */
export function useInlineCreate<T extends { id: number }>(
    options: InlineCreateOptions,
) {
    const isOpen = ref(false);
    const processing = ref(false);
    const errors = ref<Record<string, string>>({});

    const getCsrfToken = (): string => {
        const match = document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='));

        return match ? decodeURIComponent(match.split('=')[1] ?? '') : '';
    };

    const open = () => {
        errors.value = {};
        isOpen.value = true;
    };

    const close = () => {
        if (processing.value) {
            return;
        }

        isOpen.value = false;
    };

    const submit = async (
        payload: Record<string, unknown>,
    ): Promise<T | null> => {
        processing.value = true;
        errors.value = {};

        try {
            const response = await fetch(options.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });

            if (response.status === 422) {
                const data = await response.json();

                errors.value = data.errors ?? {};

                return null;
            }

            if (!response.ok) {
                errors.value = {
                    general:
                        'Ocurrió un error inesperado. Intente nuevamente.',
                };

                return null;
            }

            const entity = (await response.json()) as T;

            isOpen.value = false;

            if (options.refreshProp) {
                router.reload({ only: [options.refreshProp] });
            }

            return entity;
        } catch {
            errors.value = {
                general: 'Ocurrió un error inesperado. Intente nuevamente.',
            };

            return null;
        } finally {
            processing.value = false;
        }
    };

    return { isOpen, processing, errors, open, close, submit };
}
