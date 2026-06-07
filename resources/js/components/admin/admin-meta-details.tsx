import { ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';

type AdminMetaDetailsProps = {
    title: string;
    children: ReactNode;
    defaultOpen?: boolean;
};

export function AdminMetaDetails({
    title,
    children,
    defaultOpen = false,
}: AdminMetaDetailsProps) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <CollapsibleTrigger
                className="flex w-full items-center justify-between gap-3 rounded-xl bg-purple-soft/30 px-3 py-2.5 text-sm font-medium text-text ring-1 ring-purple/10 transition hover:bg-purple-soft/50"
                aria-expanded={open}
            >
                <span>{title}</span>
                <ChevronDown
                    className={cn(
                        'size-4 shrink-0 text-muted transition-transform',
                        open && 'rotate-180',
                    )}
                />
            </CollapsibleTrigger>
            <CollapsibleContent className="pt-2">{children}</CollapsibleContent>
        </Collapsible>
    );
}
