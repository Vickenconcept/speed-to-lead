import { Head, useForm } from '@inertiajs/react';
import { BusinessDraft, BusinessFields } from '@/components/business-fields';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type Fact = {
    id: number;
    label: string;
    value: string;
    source: string;
    confidence: string;
};

export default function BusinessProfile({
    business,
}: {
    business: BusinessDraft & { facts: Fact[] };
}) {
    const website = useForm({ url: business.website ?? '' });
    const google = useForm({ url: '' });
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
            <Head title="Business" />
            <form
                className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-8"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.put('/business');
                }}
            >
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Business</h1>
                    <p className="text-muted-foreground mt-1 text-sm">This is what the AI is allowed to say.</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <LinkCard
                        title="Website"
                        hint="Paste the site and we'll read it again."
                        placeholder="https://yourbusiness.com"
                        value={website.data.url}
                        error={website.errors.url}
                        processing={website.processing}
                        onChange={(value) => website.setData('url', value)}
                        onSubmit={() => website.post('/business/website')}
                        action="Read website"
                    />
                    <LinkCard
                        title="Google"
                        hint="A Maps or Google Business link."
                        placeholder="https://maps.google.com/..."
                        value={google.data.url}
                        error={google.errors.url}
                        processing={google.processing}
                        onChange={(value) => google.setData('url', value)}
                        onSubmit={() => google.post('/business/google')}
                        action="Read Google"
                    />
                </div>

                <div className="bg-background rounded-2xl border p-6 shadow-sm">
                    <BusinessFields data={form.data} setData={form.setData} errors={form.errors} />
                </div>

                {business.facts.length > 0 && (
                    <div className="bg-background grid gap-3 rounded-2xl border p-6 shadow-sm">
                        <h2 className="text-sm font-medium">Saved facts</h2>
                        <ul className="grid gap-2">
                            {business.facts.map((fact) => (
                                <li key={fact.id} className="flex flex-wrap items-center gap-2 text-sm">
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
                    </div>
                )}

                <Button type="submit" disabled={form.processing} className="w-fit">
                    {form.processing && <Spinner />}
                    Save profile
                </Button>
            </form>
        </>
    );
}

function LinkCard({
    title,
    hint,
    placeholder,
    value,
    error,
    processing,
    onChange,
    onSubmit,
    action,
}: {
    title: string;
    hint: string;
    placeholder: string;
    value: string;
    error?: string;
    processing: boolean;
    onChange: (value: string) => void;
    onSubmit: () => void;
    action: string;
}) {
    return (
        <div className="bg-background grid gap-3 rounded-2xl border p-5 shadow-sm">
            <div>
                <h2 className="text-sm font-medium">{title}</h2>
                <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
            </div>
            <Input value={value} placeholder={placeholder} onChange={(event) => onChange(event.target.value)} required />
            <InputError message={error} />
            <Button type="button" variant="outline" disabled={processing} className="w-fit" onClick={onSubmit}>
                {processing && <Spinner />}
                {action}
            </Button>
        </div>
    );
}

BusinessProfile.layout = {
    breadcrumbs: [{ title: 'Business', href: '/business' }],
};
