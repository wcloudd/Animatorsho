import { cn } from '@/lib/utils';

export const userFieldClassName =
    'h-11 rounded-2xl border-border bg-surface text-sm text-text shadow-xs ring-1 ring-border placeholder:text-muted/80 focus-visible:border-purple focus-visible:ring-purple/25';

export const userTextareaClassName = cn(userFieldClassName, 'min-h-[120px] py-2');

export const userSelectTriggerClassName = cn(
    userFieldClassName,
    'h-10 w-full dark:border-border dark:bg-surface dark:text-text dark:hover:bg-surface',
);

export const userSelectContentClassName = cn(
    'border-border bg-surface text-text',
    'dark:border-border dark:bg-surface dark:text-text',
);

export const userSelectItemClassName = cn(
    'text-text focus:bg-purple-soft focus:text-text',
    'dark:text-text dark:focus:bg-purple-soft',
);

export const userLabelClassName = 'text-sm font-bold text-text';

export const userSubmitButtonClassName =
    'btn-cta-green flex h-11 w-full items-center justify-center rounded-pill text-sm font-bold text-white disabled:opacity-60';
