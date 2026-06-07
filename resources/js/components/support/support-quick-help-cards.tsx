import type { SupportQuickHelpItem } from '@/types/support';
import { cn } from '@/lib/utils';

type SupportQuickHelpCardsProps = {
    items: SupportQuickHelpItem[];
    onItemSelect?: (itemId: string) => void;
};

export function SupportQuickHelpCards({
    items,
    onItemSelect,
}: SupportQuickHelpCardsProps) {
    return (
        <section
            aria-label="راهنمای سریع"
            className="grid grid-cols-2 gap-3"
        >
            {items.map((item) => (
                <a
                    key={item.id}
                    href="#new-ticket"
                    onClick={() => onItemSelect?.(item.id)}
                    className={cn(
                        'flex min-h-[72px] items-center justify-center rounded-2xl bg-surface-warm px-3 py-4 text-center text-sm font-bold text-text shadow-soft ring-1 ring-border transition-colors hover:bg-purple-soft',
                    )}
                >
                    {item.label}
                </a>
            ))}
        </section>
    );
}
