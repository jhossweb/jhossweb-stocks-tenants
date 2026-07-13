const locale = 'es';

const dateFormatter = new Intl.DateTimeFormat(locale, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

export function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) {
return '-';
}

    const dateStrNormalized = dateStr.includes('T') ? dateStr : dateStr + 'T12:00:00';
    const date = new Date(dateStrNormalized);

    if (isNaN(date.getTime())) {
return '-';
}

    return dateFormatter.format(date);
}

export function today(): string {
    return new Date().toISOString().split('T')[0];
}
