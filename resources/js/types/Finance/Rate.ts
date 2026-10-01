export type RateSeverity = 'warning' | 'critical';

export type RateNotice = {
    last_rate_date: string | null;
    days_stale: number | null;
    severity: RateSeverity;
    message: string;
};
