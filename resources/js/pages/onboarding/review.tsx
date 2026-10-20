import { Head, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { BusinessDraft, BusinessFields } from '@/components/business-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Fact = {
    id: number;
    category: string;
    label: string;
    value: string;
    source: string;
    confidence: string;
};

export default function Review({ business }: { business: BusinessDraft & { facts: Fact[]; hours_label: string; serving: string } }) {
    const form = useForm<BusinessDraft>({
        name: business.name,
        phone: business.phone,
        website: business.website,
        description: business.description,
        city: business.city,
        region: business.region,
        timezone: business.timezone,
        opens: business.opens.slice(0, 5),
        closes: business.closes.slice(0, 5),
        services: business.services,
        notes: business.notes,
    });

    return (
        <>
            <Head title="Review your business" />
            <form
                className="grid gap-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/onboarding/confirm');
                }}
            >
                <div className="grid gap-2">
                    <p className="text-primary text-sm font-medium">Step 2</p>
                    <h1 className="text-3xl font-semibold tracking-tight">We found this</h1>
                    <p className="text-muted-foreground text-sm">Change anything that is wrong, then confirm. The AI only uses what you confirm.</p>
                </div>

                <div className="bg-background grid gap-4 rounded-2xl border p-6 shadow-sm">
                    <div>
                        <h2 className="text-xl font-semibold">{form.data.name || 'Your business'}</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {form.data.services
                                .split('\n')
                                .map((line) => line.trim())
                                .filter(Boolean)
                                .join(' · ') || 'No services yet'}
                        </p>
                    </div>
                    <dl className="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">Serving</dt>
                            <dd>{[form.data.city, form.data.region].filter(Boolean).join(', ') || 'Not set'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Weekdays</dt>
                            <dd>
                                {form.data.opens} – {form.data.closes}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Phone</dt>
                            <dd>{form.data.phone || 'Not set'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Website</dt>
                            <dd className="truncate">{form.data.website || 'Not set'}</dd>
                        </div>
                    </dl>
                    {business.facts.length > 0 && (
                        <ul className="grid gap-2">
                            {business.facts.map((fact) => (
                                <li key={fact.id} className="bg-muted/50 flex flex-wrap items-center gap-2 rounded-lg px-3 py-2 text-sm">
                                    <span className="font-medium">{fact.label}</span>
                                    <span className="text-muted-foreground">{fact.value}</span>
                                    <Badge variant="secondary" className="capitalize">
                                        {fact.source}
                                    </Badge>
                                    <Badge variant="outline" className="capitalize">
                                        {fact.confidence}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="bg-background rounded-2xl border p-6 shadow-sm">
                    <BusinessFields data={form.data} setData={form.setData} errors={form.errors} />
                </div>

                <Button type="submit" disabled={form.processing} size="lg" className="w-fit">
                    {form.processing ? <Spinner /> : <Check />}
                    Looks correct
                </Button>
            </form>
        </>
    );
}
