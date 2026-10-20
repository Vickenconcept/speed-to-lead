import { Head, useForm } from '@inertiajs/react';
import { Check, Copy, Globe } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useClipboard } from '@/hooks/use-clipboard';

export default function WebsiteSource({
    form_url,
    webhook_url,
}: {
    form_url: string;
    webhook_url: string;
}) {
    const sample = useForm({});

    return (
        <>
            <Head title="Website leads" />
            <div className="mx-auto grid w-full max-w-3xl gap-6 p-4 md:p-8">
                <div className="flex items-start gap-3">
                    <span className="bg-primary text-primary-foreground flex size-11 items-center justify-center rounded-xl">
                        <Globe className="size-5" />
                    </span>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Website leads</h1>
                        <p className="text-muted-foreground mt-1 max-w-xl text-sm leading-6">
                            This is the one channel for now. Send people to the form, or post the same fields to the webhook. Every lead becomes the same conversation.
                        </p>
                    </div>
                </div>

                <div className="bg-background grid gap-4 rounded-2xl border p-6 shadow-sm">
                    <CopyRow label="Form" value={form_url} />
                    <CopyRow label="Webhook" value={webhook_url} />
                    <pre className="bg-muted overflow-x-auto rounded-xl p-4 text-xs leading-6">
{`{
  "name": "John",
  "phone": "+1...",
  "message": "My roof is leaking and I need someone to come look at it.",
  "source": "website"
}`}
                    </pre>
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        sample.post('/sources/website/sample');
                    }}
                >
                    <Button type="submit" disabled={sample.processing}>
                        {sample.processing && <Spinner />}
                        Send a sample lead
                    </Button>
                </form>
            </div>
        </>
    );
}

function CopyRow({ label, value }: { label: string; value: string }) {
    const [, copy] = useClipboard();
    const [done, setDone] = useState(false);

    return (
        <div className="grid gap-2">
            <p className="text-sm font-medium">{label}</p>
            <div className="flex gap-2">
                <code className="bg-muted flex-1 truncate rounded-lg px-3 py-2 text-xs">{value}</code>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={async () => {
                        const ok = await copy(value);
                        if (ok) {
                            setDone(true);
                            window.setTimeout(() => setDone(false), 1500);
                        }
                    }}
                >
                    {done ? <Check /> : <Copy />}
                    <span className="sr-only">Copy {label}</span>
                </Button>
            </div>
        </div>
    );
}

WebsiteSource.layout = {
    breadcrumbs: [{ title: 'Website leads', href: '/sources/website' }],
};
