import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CalendarCheck, MessageSquare, Zap } from 'lucide-react';
import { LeadStatus } from '@/components/lead-status';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const sample = [
    {
        name: 'John Smith',
        service: 'Roof replacement',
        status: 'qualified',
        label: 'Qualified',
        appointment: 'Tomorrow 10:00 AM',
    },
    {
        name: 'Sarah Jones',
        service: 'Roof repair',
        status: 'waiting',
        label: 'Waiting for response',
        appointment: null,
    },
    {
        name: 'Mike Brown',
        service: 'Roof inspection',
        status: 'booked',
        label: 'Appointment booked',
        appointment: 'Tomorrow 2:00 PM',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Speed-to-Lead" />
            <div className="bg-background text-foreground min-h-svh">
                <header className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-6">
                    <div className="flex items-center gap-2 font-semibold tracking-tight">
                        <span className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg">
                            <Zap className="size-4" />
                        </span>
                        Speed-to-Lead
                    </div>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>Open leads</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={register()}>Get started</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-12 lg:grid-cols-2 lg:py-20">
                    <div className="grid gap-6">
                        <p className="text-primary text-sm font-medium">For local businesses</p>
                        <h1 className="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                            A new lead comes in. You never have to answer it yourself.
                        </h1>
                        <p className="text-muted-foreground max-w-md text-base leading-7">
                            Tell Speed-to-Lead about your business. It replies in seconds, asks the right questions, and books the visit.
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <Button size="lg" asChild>
                                <Link href={auth.user ? dashboard() : register()}>
                                    Get started
                                    <ArrowRight />
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <Link href={login()}>I already have an account</Link>
                            </Button>
                        </div>
                        <ol className="mt-4 grid gap-4 sm:grid-cols-3">
                            <Step icon={Zap} title="Learn the business" body="Website or a short profile. You confirm it." />
                            <Step icon={MessageSquare} title="Answer the lead" body="One conversation, from the website form." />
                            <Step icon={CalendarCheck} title="Book the visit" body="Real openings from your weekday hours." />
                        </ol>
                    </div>

                    <div className="bg-muted/40 rounded-3xl border p-4 shadow-sm">
                        <div className="bg-background overflow-hidden rounded-2xl border">
                            <div className="border-b px-5 py-4">
                                <p className="text-sm font-semibold">New leads</p>
                                <p className="text-muted-foreground text-xs">ABC Roofing</p>
                            </div>
                            <ul className="divide-y">
                                {sample.map((lead) => (
                                    <li key={lead.name} className="px-5 py-4">
                                        <p className="font-medium">{lead.name}</p>
                                        <p className="text-muted-foreground text-sm">{lead.service}</p>
                                        <div className="mt-2 flex flex-wrap items-center gap-2">
                                            <LeadStatus status={lead.status} label={lead.label} />
                                            {lead.appointment && (
                                                <span className="text-muted-foreground text-xs">{lead.appointment}</span>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}

function Step({
    icon: Icon,
    title,
    body,
}: {
    icon: typeof Zap;
    title: string;
    body: string;
}) {
    return (
        <li className="grid gap-2">
            <Icon className="text-primary size-4" />
            <p className="text-sm font-medium">{title}</p>
            <p className="text-muted-foreground text-xs leading-5">{body}</p>
        </li>
    );
}
