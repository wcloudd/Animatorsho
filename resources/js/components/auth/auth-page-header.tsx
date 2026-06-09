type AuthPageHeaderProps = {
    title: string;
    subtitle: string;
};

export function AuthPageHeader({ title, subtitle }: AuthPageHeaderProps) {
    return (
        <header className="flex flex-col items-center justify-center gap-2 text-center">
            <h1 className="font-display text-[1.35rem] font-bold leading-snug text-text">
                {title}
            </h1>
            <p className="max-w-[300px] text-sm font-medium leading-relaxed text-muted">
                {subtitle}
            </p>
        </header>
    );
}
