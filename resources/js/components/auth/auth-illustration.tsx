export function AuthIllustration() {
    return (
        <div
            className="relative mx-auto flex h-[88px] w-[200px] items-center justify-center"
            aria-hidden="true"
        >
            <div className="absolute inset-0 rounded-[28px] bg-gradient-to-br from-purple-soft via-surface to-gold-soft shadow-soft ring-1 ring-border" />
            <div className="absolute -start-2 top-3 size-10 rounded-2xl bg-gold/25 ring-1 ring-gold/30" />
            <div className="absolute -end-1 bottom-2 size-8 rounded-full bg-purple/15 ring-1 ring-purple/20" />
            <div className="relative flex flex-col items-center gap-1.5">
                <div className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-full bg-purple" />
                    <span className="size-2.5 rounded-full bg-gold" />
                    <span className="size-2.5 rounded-full bg-green" />
                </div>
                <div className="h-7 w-24 rounded-xl bg-purple/10 ring-1 ring-purple/15" />
                <div className="h-2 w-16 rounded-full bg-gold/20" />
            </div>
        </div>
    );
}
