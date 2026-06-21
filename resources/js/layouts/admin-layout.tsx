import type { ReactNode } from 'react';
import { AdminTextLink } from '@/components/admin/admin-text-link';
import { AdminNavMenu } from '@/components/admin/admin-nav-menu';
import { NoIndexSeoHead } from '@/components/seo/seo-head';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useState } from 'react';
import { Menu } from 'lucide-react';
import { usePage } from '@inertiajs/react';

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="min-h-dvh overflow-x-hidden bg-bg text-text" dir="rtl">
            <NoIndexSeoHead />
            <header className="sticky top-0 z-10 border-b border-purple/10 bg-surface/95 shadow-soft backdrop-blur-sm">
                <div className="mx-auto flex w-full max-w-[390px] flex-col gap-3 px-4 py-4 sm:max-w-5xl">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-2">
                            <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                                <SheetTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="shrink-0 border-purple/15 bg-surface text-purple hover:bg-purple-soft lg:hidden"
                                        aria-label="باز کردن منوی مدیریت"
                                    >
                                        <Menu className="size-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent
                                    side="right"
                                    className="flex w-[min(100vw-2rem,20rem)] flex-col overflow-hidden border-purple/10 bg-surface p-0 text-text"
                                >
                                    <SheetHeader className="flex flex-row items-center border-b border-purple/10 px-4 py-4 pr-12">
                                        <SheetTitle className="flex-1 text-right font-display text-base text-purple">
                                            منوی مدیریت
                                        </SheetTitle>
                                    </SheetHeader>
                                    <AdminNavMenu
                                        url={url}
                                        onNavigate={() => setMenuOpen(false)}
                                        variant="mobile"
                                    />
                                </SheetContent>
                            </Sheet>
                            <h1 className="min-w-0 truncate font-display text-lg text-purple">
                                پنل مدیریت
                            </h1>
                        </div>
                        <AdminTextLink href="/" variant="subtle">
                            بازگشت به سایت
                        </AdminTextLink>
                    </div>
                    <AdminNavMenu url={url} variant="desktop" />
                </div>
            </header>
            <main className="mx-auto w-full max-w-[390px] px-4 py-6 sm:max-w-5xl">
                {children}
            </main>
        </div>
    );
}
