import { Phone } from 'lucide-react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type BusinessDraft = {
    name: string;
    phone: string;
    website: string;
    description: string;
    city: string;
    region: string;
    timezone: string;
    opens: string;
    closes: string;
    services: string;
    notes: string;
};

export const timezones = [
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Phoenix',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Paris',
    'Australia/Sydney',
];

export function emptyBusiness(): BusinessDraft {
    return {
        name: '',
        phone: '',
        website: '',
        description: '',
        city: '',
        region: '',
        timezone: 'America/Chicago',
        opens: '08:00',
        closes: '18:00',
        services: '',
        notes: '',
    };
}

type Errors = Partial<Record<keyof BusinessDraft, string>>;

export function BusinessFields({
    data,
    setData,
    errors,
}: {
    data: BusinessDraft;
    setData: <K extends keyof BusinessDraft>(key: K, value: BusinessDraft[K]) => void;
    errors: Errors;
}) {
    return (
        <div className="grid gap-5">
            <Field label="Business name" error={errors.name} htmlFor="name">
                <Input
                    id="name"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    placeholder="ABC Roofing"
                    required
                />
            </Field>

            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Phone" error={errors.phone} htmlFor="phone">
                    <div className="relative">
                        <Phone className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(event) => setData('phone', event.target.value)}
                            placeholder="+1 512 555 0148"
                            className="pl-9"
                            autoComplete="tel"
                        />
                    </div>
                </Field>
                <Field label="Website" error={errors.website} htmlFor="website">
                    <Input
                        id="website"
                        value={data.website}
                        onChange={(event) => setData('website', event.target.value)}
                        placeholder="https://abcroofing.com"
                    />
                </Field>
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="City" error={errors.city} htmlFor="city">
                    <Input
                        id="city"
                        value={data.city}
                        onChange={(event) => setData('city', event.target.value)}
                        placeholder="Austin"
                    />
                </Field>
                <Field label="State or region" error={errors.region} htmlFor="region">
                    <Input
                        id="region"
                        value={data.region}
                        onChange={(event) => setData('region', event.target.value)}
                        placeholder="Texas"
                    />
                </Field>
            </div>

            <Field label="What you do" error={errors.description} htmlFor="description">
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(event) => setData('description', event.target.value)}
                    placeholder="Residential roof repair, replacement, and inspections."
                />
            </Field>

            <Field
                label="Services"
                error={errors.services}
                htmlFor="services"
                hint="One service per line. These are the jobs the AI is allowed to talk about."
            >
                <Textarea
                    id="services"
                    value={data.services}
                    onChange={(event) => setData('services', event.target.value)}
                    placeholder={'Roof repair\nRoof replacement\nRoof inspection'}
                />
            </Field>

            <div className="grid gap-5 sm:grid-cols-3">
                <Field label="Opens" error={errors.opens} htmlFor="opens">
                    <Input
                        id="opens"
                        type="time"
                        value={data.opens}
                        onChange={(event) => setData('opens', event.target.value)}
                        required
                    />
                </Field>
                <Field label="Closes" error={errors.closes} htmlFor="closes">
                    <Input
                        id="closes"
                        type="time"
                        value={data.closes}
                        onChange={(event) => setData('closes', event.target.value)}
                        required
                    />
                </Field>
                <Field label="Timezone" error={errors.timezone} htmlFor="timezone">
                    <select
                        id="timezone"
                        value={data.timezone}
                        onChange={(event) => setData('timezone', event.target.value)}
                        className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-3"
                    >
                        {timezones.map((zone) => (
                            <option key={zone} value={zone}>
                                {zone.replaceAll('_', ' ')}
                            </option>
                        ))}
                    </select>
                </Field>
            </div>

            <Field
                label="Anything else the AI should know"
                error={errors.notes}
                htmlFor="notes"
                hint="Emergency calls, areas you do not serve, how deposits work."
            >
                <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(event) => setData('notes', event.target.value)}
                    placeholder="We take emergency leak calls the same day."
                />
            </Field>
        </div>
    );
}

function Field({
    label,
    htmlFor,
    error,
    hint,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
