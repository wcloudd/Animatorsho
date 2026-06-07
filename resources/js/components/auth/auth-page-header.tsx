type AuthPageHeaderProps = {
    title: string;
    subtitle: string;
};

export function AuthPageHeader({ title, subtitle }: AuthPageHeaderProps) {
    return (
        <header className="flex flex-col items-center justify-center gap-2 text-center">
            <h1 className="font-display text-2xl font-bold text-text">{title}</h1>
            <p className="w-[253px] text-sm font-medium leading-relaxed text-muted">
                {subtitle}
            </p>
        </header>
    );
}
