import { http, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

export function useFlashToast(): void {
    const page = usePage();

    watch(
        () => (page.props as Record<string, unknown>).flash,
        (flash) => {
            if (!flash || typeof flash !== 'object') {
                return;
            }

            const f = flash as Record<string, string | null | undefined>;

            if (f.success) {
                toast.success(String(f.success));
            }

            if (f.error) {
                toast.error(String(f.error));
            }
        },
        { immediate: true },
    );

    watch(
        () => (page.props as Record<string, unknown>).errors,
        (errors) => {
            if (!errors || typeof errors !== 'object') {
                return;
            }

            const e = errors as Record<string, string>;
            const keys = Object.keys(e).filter((k) => k !== '');

            if (keys.length === 0) {
                return;
            }

            if (keys.length === 1) {
                toast.error(e[keys[0]]);
            } else {
                toast.error(
                    'Hay errores en el formulario. Por favor, revisa los campos.',
                );
            }
        },
        { immediate: true },
    );

    http.onError((error: Error) => {
        if (error.name === 'HttpNetworkError') {
            toast.error(
                'No se pudo conectar. Verifica tu conexión a internet.',
            );
        } else if (error.name === 'HttpResponseError') {
            toast.error('Error del servidor. Intenta más tarde.');
        }
    });
}
