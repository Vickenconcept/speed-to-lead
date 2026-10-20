import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, CalendarClock, Inbox } from 'lucide-react';
import { LeadStatus } from '@/components/lead-status';
import { Button } from '@/components/ui/button';

type LeadRow = {
    id: number;
    name: string;
    phone: string | null;
    service: string | null;
    status: string;
    status_label: string;
    appointment: string | null;
    created: string | null;
};

export default function Dashboard({
    business,
    counts,
    leads,
}: {
    business: { name: string };
    counts: { waiting: number; qualified: number; booked: number };
    leads: LeadRow[];
}) {
    return (
        <>
            <Head title="New leads" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground text-sm">{business.name}</p>
                        <h1 className="text-2xl font-semibold tracking-tight">New leads</h1>
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/sources/website">Website form</Link>
                    </Button>
                </div>

                <div className="grid grid-cols-3 gap-3">
                    <Stat label="Waiting" value={counts.waiting} />
                    <Stat label="Qualified" value={counts.qualified} />
                    <Stat label="Booked" value={counts.booked} />
                </div>

                {leads.length === 0 ? (
                    <div className="bg-background flex flex-col items-start gap-4 rounded-2xl border border-dashed p-8">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-xl">
                            <Inbox className="size-5" />
                        </span>
                        <div className="grid gap-1">
                            <h2 className="font-medium">No leads yet</h2>
                            <p className="text-muted-foreground max-w-md text-sm leading-6">
                                Connect the website form, or send a sample lead and watch it get answered, qualified, and booked.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/sources/website">Connect website leads</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="bg-background overflow-hidden rounded-2xl border shadow-sm">
                        <ul className="divide-y">
                            {leads.map((lead) => (
                                <li key={lead.id}>
                                    <Link
                                        href={`/leads/${lead.id}`}
                                        className="hover:bg-muted/50 flex items-start justify-between gap-4 px-5 py-4 transition"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <p className="truncate font-medium">{lead.name}</p>
                                                <ArrowUpRight className="text-muted-foreground size-3.5 shrink-0" />
                                            </div>
                                            <p className="text-muted-foreground mt-0.5 text-sm">
                                                {lead.service || 'New request'}
                                            </p>
                                            <div className="mt-2 flex flex-wrap items-center gap-2">
                                                <LeadStatus status={lead.status} label={lead.status_label} />
                                                {lead.appointment && (
                                                    <span className="text-muted-foreground inline-flex items-center gap-1 text-xs">
                                                        <CalendarClock className="size-3.5" />
                                                        {lead.appointment}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <span className="text-muted-foreground shrink-0 text-xs">{lead.created}</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </>
    );
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="bg-background rounded-2xl border px-4 py-3 shadow-sm">
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'New leads',
            href: '/dashboard',
        },
    ],
};
