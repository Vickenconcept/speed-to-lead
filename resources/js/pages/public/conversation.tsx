import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Message = {
    id: number;
    role: string;
    body: string;
};

export default function Conversation({
    business,
    lead,
    messages,
}: {
    business: { name: string };
    lead: { name: string; status: string; appointment: string | null };
    messages: Message[];
}) {
    const scroller = useRef<HTMLDivElement>(null);
    const [draft, setDraft] = useState('');
    const [pending, setPending] = useState<string | null>(null);
    const [sending, setSending] = useState(false);
    const alreadyShown = messages.some((message) => message.role !== 'assistant' && message.body === pending);

    useEffect(() => {
        const node = scroller.current;

        if (node) {
            node.scrollTop = node.scrollHeight;
        }
    }, [messages, pending, sending]);

    function send() {
        const body = draft.trim();

        if (body === '' || sending) {
            return;
        }

        setPending(body);
        setDraft('');
        setSending(true);

        router.post(
            window.location.pathname,
            { body },
            {
                preserveScroll: true,
                onFinish: () => setSending(false),
                onSuccess: () => setPending(null),
                onError: () => {
                    setDraft(body);
                    setPending(null);
                },
            },
        );
    }

    return (
        <>
            <Head title={business.name} />
            <div className="bg-muted/40 flex h-svh justify-center">
                <div className="bg-background flex h-svh w-full max-w-lg flex-col border-x">
                    <header className="shrink-0 border-b px-5 py-4">
                        <p className="text-sm font-semibold">{business.name}</p>
                        <p className="text-muted-foreground text-xs">
                            {lead.appointment ? `Booked · ${lead.appointment}` : `Hi ${lead.name}`}
                        </p>
                    </header>
                    <div ref={scroller} className="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto px-4 py-5">
                        {messages.map((message) => (
                            <div
                                key={message.id}
                                className={`max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-6 whitespace-pre-wrap ${
                                    message.role === 'assistant'
                                        ? 'bg-muted self-start rounded-bl-md'
                                        : 'bg-primary text-primary-foreground self-end rounded-br-md'
                                }`}
                            >
                                {message.body}
                            </div>
                        ))}
                        {pending && !alreadyShown && (
                            <div className="bg-primary text-primary-foreground max-w-[85%] self-end rounded-2xl rounded-br-md px-4 py-2.5 text-sm leading-6 whitespace-pre-wrap">
                                {pending}
                            </div>
                        )}
                        {sending && (
                            <div className="bg-muted flex w-fit items-center gap-1 self-start rounded-2xl rounded-bl-md px-4 py-3">
                                <span className="bg-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:0ms]" />
                                <span className="bg-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:150ms]" />
                                <span className="bg-foreground/40 size-1.5 animate-bounce rounded-full [animation-delay:300ms]" />
                            </div>
                        )}
                    </div>
                    {lead.status === 'booked' ? (
                        <div className="shrink-0 border-t px-5 py-4 text-sm">
                            You're booked{lead.appointment ? ` for ${lead.appointment}` : ''}.
                        </div>
                    ) : (
                        <form
                            className="flex shrink-0 items-end gap-2 border-t p-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                send();
                            }}
                        >
                            <textarea
                                value={draft}
                                onChange={(event) => setDraft(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' && !event.shiftKey) {
                                        event.preventDefault();
                                        send();
                                    }
                                }}
                                placeholder="Write a reply"
                                rows={2}
                                className="border-input focus-visible:border-ring focus-visible:ring-ring/50 max-h-28 min-h-11 flex-1 resize-none rounded-xl border px-3 py-2 text-sm outline-none focus-visible:ring-3"
                            />
                            <Button type="submit" disabled={sending || draft.trim() === ''}>
                                {sending ? <Spinner /> : 'Send'}
                            </Button>
                        </form>
                    )}
                </div>
            </div>
        </>
    );
}
