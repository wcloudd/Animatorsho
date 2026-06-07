/** Light-only admin button styles — use fixed project tokens, not shadcn CSS vars that flip under .dark */
export const adminButtonStyles = {
    outline:
        'border border-[#e8e0f0] bg-surface text-text shadow-xs hover:!bg-purple-soft hover:!text-purple focus-visible:ring-purple/30',
    brand: 'bg-purple text-white shadow-xs hover:!bg-purple/90 focus-visible:ring-purple/30',
    success:
        'bg-green text-white shadow-xs hover:!bg-green/90 focus-visible:ring-green/30',
    danger: 'bg-red text-white shadow-xs hover:!bg-red/90 focus-visible:ring-red/30',
    dangerOutline:
        'border border-red/25 bg-surface text-red shadow-xs hover:!bg-red-soft focus-visible:ring-red/30',
} as const;

export type AdminButtonStyle = keyof typeof adminButtonStyles;
