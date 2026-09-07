import { toast } from 'vue-sonner';

const errorMessages: Record<number, string> = {
    401: 'Tu sesión expiró. Por favor, recarga la página.',
    403: 'No tienes permisos para realizar esta acción.',
    404: 'El recurso solicitado no fue encontrado.',
    419: 'Tu sesión expiró. Por favor, recarga la página.',
    422: 'Los datos enviados no son válidos.',
    429: 'Demasiadas solicitudes. Intenta de nuevo en unos segundos.',
    500: 'Error del servidor. Intenta más tarde.',
    503: 'El servicio no está disponible temporalmente.',
};

export async function apiRequest<T = unknown>(
    url: string,
    options?: RequestInit,
): Promise<T | null> {
    try {
        const res = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options?.headers,
            },
            ...options,
        });

        if (!res.ok) {
            const message =
                errorMessages[res.status] ?? 'Ocurrió un error inesperado.';
            toast.error(message);

            return null;
        }

        return (await res.json()) as T;
    } catch (error) {
        const isNetwork =
            error instanceof TypeError && error.message === 'Failed to fetch';
        toast.error(
            isNetwork
                ? 'No se pudo conectar. Verifica tu conexión a internet.'
                : 'No se pudo completar la solicitud.',
        );

        return null;
    }
}
