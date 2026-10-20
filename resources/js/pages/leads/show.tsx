import { Head, Link } from '@inertiajs/react';
import { CalendarClock, MapPin, Phone } from 'lucide-react';
import { LeadStatus } from '@/components/lead-status';
import { Button } from '@/components/ui/button';

type Message = {
    id: number;
    role: string;
    body: string;
    at: string | null;
};

type LeadDetail = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    service: string | null;
    status: string;
    status_label: string;
    appointment: string | null;
    location: string | null;
    intent: string | null;
    urgency: string | null;
    source: string;
    conversation_url: string;
    messages: Message[];
};

export default function LeadShow({ lead }: { lead: LeadDetail }) {
    return (
        <>
            <Head title={lead.name} />
            <div className="mx-auto grid w-full max-w-5xl gap-6 p-4 md:p-8 lg:grid-cols-[minmax(0,1fr)_280px]">
                <section className="bg-background flex min-h-[32rem] flex-col rounded-2xl border shadow-sm">
                    <header className="flex items-start justify-between gap-4 border-b px-5 py-4">
                        <div>
                            <h1 className="text-lg font-semibold">{lead.name}</h1>
                            <p className="text-muted-foreground text-sm">{lead.service || 'New request'}</p>
                        </div>
                        <LeadStatus status={lead.status} label={lead.status_label} />
                    </header>
                    <div className="flex flex-1 flex-col gap-3 px-5 py-5">
                        {lead.messages.map((message) => (
                            <div
                                key={message.id}
                                className={`max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-6 ${
                                    message.role === 'assistant'
                                        ? 'bg-primary text-primary-foreground self-start rounded-bl-md'
                                        : 'bg-muted self-end rounded-br-md'
                                }`}
                            >
                                <p>{message.body}</p>
                                {message.at && (
                                    <p className={`mt-1 text-[11px] ${message.role === 'assistant' ? 'text-primary-foreground/70' : 'text-muted-foreground'}`}>
                                        {message.at}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                </section>

                <aside className="grid content-start gap-4">
                    <div className="bg-background grid gap-3 rounded-2xl border p-5 shadow-sm">
                        <h2 className="text-sm font-medium">This lead</h2>
                        {lead.phone && (
                            <p className="flex items-center gap-2 text-sm">
                                <Phone className="text-muted-foreground size-4" />
                                {lead.phone}
                            </p>
                        )}
                        {lead.location && (
                            <p className="flex items-center gap-2 text-sm">
                                <MapPin className="text-muted-foreground size-4" />
                                {lead.location}
                            </p>
                        )}
                        {lead.appointment && (
                            <p className="flex items-center gap-2 text-sm">
                                <CalendarClock className="text-muted-foreground size-4" />
                                {lead.appointment}
                            </p>
                        )}
                        <dl className="grid gap-2 text-sm">
                            <Row label="Source" value={lead.source} />
                            <Row label="Intent" value={lead.intent} />
                            <Row label="Urgency" value={lead.urgency} />
                        </dl>
                    </div>
                    <Button asChild variant="outline">
                        <a href={lead.conversation_url} target="_blank" rel="noreferrer">
                            Open customer chat
                        </a>
                    </Button>
                    <Button asChild variant="ghost">
                        <Link href="/dashboard">Back to leads</Link>
                    </Button>
                </aside>
            </div>
        </>
    );
}

function Row({ label, value }: { label: string; value: string | null }) {
    if (!value) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="capitalize">{value}</dd>
        </div>
    );
}

LeadShow.layout = {
    breadcrumbs: [
        { title: 'New leads', href: '/dashboard' },
        { title: 'Conversation', href: '/dashboard' },
    ],
};
