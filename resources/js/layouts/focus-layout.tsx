import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { logout } from '@/routes';

export default function FocusLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="bg-muted/40 min-h-svh">
            <header className="bg-background border-b">
                <div className="mx-auto flex h-16 max-w-3xl items-center justify-between px-6">
                    <Link href="/" className="flex items-center gap-2 font-semibold tracking-tight">
                        <span className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-lg">
                            <AppLogoIcon className="size-4 fill-current" />
                        </span>
                        Speed-to-Lead
                    </Link>
                    <Link
                        href={logout()}
                        method="post"
                        as="button"
                        className="text-muted-foreground hover:text-foreground text-sm"
                    >
                        Log out
                    </Link>
                </div>
            </header>
            <main className="mx-auto w-full max-w-3xl px-6 py-10">{children}</main>
        </div>
    );
}
