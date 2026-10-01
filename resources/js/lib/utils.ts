import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function formFieldError(form: { errors: unknown }, path: string) {
    const value = (form.errors as Record<string, unknown> | undefined)?.[path];

    return typeof value === 'string' ? value : undefined;
}

export function generateUid(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    return `uid-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}
