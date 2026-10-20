import { Head, useForm } from '@inertiajs/react';
import { Globe, PenLine, Store } from 'lucide-react';
import { useState } from 'react';
import { BusinessFields, emptyBusiness } from '@/components/business-fields';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type Mode = 'website' | 'google' | 'manual' | null;

export default function Onboarding({ ai }: { ai: boolean }) {
    const [mode, setMode] = useState<Mode>(null);
    const website = useForm({ url: '' });
    const google = useForm({ url: '' });
    const manual = useForm(emptyBusiness());

    return (
        <>
            <Head title="Tell us about your business" />
            <div className="grid gap-8">
                <div className="grid gap-2">
                    <p className="text-primary text-sm font-medium">Get started</p>
                    <h1 className="text-3xl font-semibold tracking-tight">How should we learn about your business?</h1>
                    <p className="text-muted-foreground max-w-xl text-sm leading-6">
                        Paste a website or a Google link, or type the few things a customer would ask. We will not make you fill out a long questionnaire.
                    </p>
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <Choice
                        icon={Globe}
                        title="Website"
                        body="We'll read the public page."
                        active={mode === 'website'}
                        onClick={() => setMode('website')}
                    />
                    <Choice
                        icon={Store}
                        title="Google"
                        body="Paste a Maps or Business link."
                        active={mode === 'google'}
                        onClick={() => setMode('google')}
                    />
                    <Choice
                        icon={PenLine}
                        title="Enter it yourself"
                        body="Name, services, hours, and area."
                        active={mode === 'manual'}
                        onClick={() => setMode('manual')}
                    />
                </div>

                {mode === 'website' && (
                    <form
                        className="bg-background grid gap-4 rounded-2xl border p-6 shadow-sm"
                        onSubmit={(event) => {
                            event.preventDefault();
                            website.post('/onboarding/website');
                        }}
                    >
                        <div className="grid gap-2">
                            <label htmlFor="url" className="text-sm font-medium">
                                Website address
                            </label>
                            <Input
                                id="url"
                                value={website.data.url}
                                onChange={(event) => website.setData('url', event.target.value)}
                                placeholder="abcroofing.com"
                                autoFocus
                                required
                            />
                            <InputError message={website.errors.url} />
                            <p className="text-muted-foreground text-xs">
                                {ai
                                    ? 'The AI reads the page and pulls out services, location, and anything a lead would ask.'
                                    : 'Without an OpenRouter key we save the page title and description. You can correct everything on the next screen.'}
                            </p>
                        </div>
                        <Button type="submit" disabled={website.processing} className="w-fit">
                            {website.processing && <Spinner />}
                            Learn about this business
                        </Button>
                    </form>
                )}

                {mode === 'google' && (
                    <form
                        className="bg-background grid gap-4 rounded-2xl border p-6 shadow-sm"
                        onSubmit={(event) => {
                            event.preventDefault();
                            google.post('/onboarding/google');
                        }}
                    >
                        <div className="grid gap-2">
                            <label htmlFor="google-url" className="text-sm font-medium">
                                Google link
                            </label>
                            <Input
                                id="google-url"
                                value={google.data.url}
                                onChange={(event) => google.setData('url', event.target.value)}
                                placeholder="https://maps.google.com/..."
                                autoFocus
                                required
                            />
                            <InputError message={google.errors.url} />
                            <p className="text-muted-foreground text-xs">
                                Paste the public Google Maps or Google Business link. We read it the same way we read a website.
                            </p>
                        </div>
                        <Button type="submit" disabled={google.processing} className="w-fit">
                            {google.processing && <Spinner />}
                            Learn from Google
                        </Button>
                    </form>
                )}

                {mode === 'manual' && (
                    <form
                        className="bg-background grid gap-6 rounded-2xl border p-6 shadow-sm"
                        onSubmit={(event) => {
                            event.preventDefault();
                            manual.post('/onboarding/manual');
                        }}
                    >
                        <BusinessFields data={manual.data} setData={manual.setData} errors={manual.errors} />
                        <Button type="submit" disabled={manual.processing} className="w-fit">
                            {manual.processing && <Spinner />}
                            Continue
                        </Button>
                    </form>
                )}
            </div>
        </>
    );
}

function Choice({
    icon: Icon,
    title,
    body,
    active = false,
    disabled = false,
    onClick,
}: {
    icon: typeof Globe;
    title: string;
    body: string;
    active?: boolean;
    disabled?: boolean;
    onClick?: () => void;
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={`bg-background grid gap-3 rounded-2xl border p-4 text-left shadow-sm transition ${
                active ? 'border-primary ring-primary/20 ring-3' : 'hover:border-foreground/20'
            } ${disabled ? 'cursor-not-allowed opacity-60' : ''}`}
        >
            <span className="bg-muted flex size-10 items-center justify-center rounded-xl">
                <Icon className="size-4" />
            </span>
            <span>
                <span className="block text-sm font-medium">{title}</span>
                <span className="text-muted-foreground mt-1 block text-xs leading-5">{body}</span>
            </span>
        </button>
    );
}
