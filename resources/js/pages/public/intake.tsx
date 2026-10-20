import { Head, useForm } from '@inertiajs/react';
import { Phone } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Spinner } from '@/components/ui/spinner';

export default function Intake({
    business,
}: {
    business: { name: string; city: string; description: string | null };
}) {
    const form = useForm({
        name: '',
        phone: '',
        email: '',
        message: '',
    });

    return (
        <>
            <Head title={`Contact ${business.name}`} />
            <div className="bg-muted/40 flex min-h-svh items-center justify-center p-6">
                <form
                    className="bg-background w-full max-w-md rounded-3xl border p-8 shadow-sm"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(window.location.pathname);
                    }}
                >
                    <p className="text-primary text-sm font-medium">{business.city || 'Local service'}</p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight">{business.name}</h1>
                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                        {business.description || 'Tell us what you need. We reply right away and can book a visit.'}
                    </p>

                    <div className="mt-6 grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="John Smith"
                                required
                                autoFocus
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone</Label>
                            <div className="relative">
                                <Phone className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(event) => form.setData('phone', event.target.value)}
                                    placeholder="+1 512 555 0199"
                                    className="pl-9"
                                    autoComplete="tel"
                                    required
                                />
                            </div>
                            <InputError message={form.errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                placeholder="john@email.com"
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="message">What do you need?</Label>
                            <Textarea
                                id="message"
                                value={form.data.message}
                                onChange={(event) => form.setData('message', event.target.value)}
                                placeholder="My roof is leaking and I need someone to come look at it."
                                required
                            />
                            <InputError message={form.errors.message} />
                        </div>
                    </div>

                    <Button type="submit" className="mt-6 w-full" disabled={form.processing} size="lg">
                        {form.processing && <Spinner />}
                        Request a visit
                    </Button>
                </form>
            </div>
        </>
    );
}
