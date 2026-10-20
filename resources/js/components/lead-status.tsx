import { cn } from '@/lib/utils';

const tones: Record<string, string> = {
    waiting:
        'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-100',
    qualified:
        'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-100',
    booked: 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-900/60 dark:bg-sky-950/50 dark:text-sky-100',
    closed: 'border-border bg-muted text-muted-foreground',
};

export function LeadStatus({
    status,
    label,
}: {
    status: string;
    label: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                tones[status] ?? tones.waiting,
            )}
        >
            {label}
        </span>
    );
}
