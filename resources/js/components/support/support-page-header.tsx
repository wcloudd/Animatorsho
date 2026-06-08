export function SupportPageHeader() {
    return (
        <header className="flex flex-col items-center gap-3 text-center">
            <span className="inline-flex items-center rounded-pill bg-blue/10 px-3 py-1 text-[11px] font-bold text-blue ring-1 ring-blue/15">
                همراه یادگیری‌ات
            </span>

            <h1 className="font-display text-[1.625rem] leading-tight font-bold text-text">
                <span className="text-gradient-support-title">پشتیبانی</span>
                <span className="text-text"> انیماتورشو</span>
            </h1>

            <p className="max-w-[300px] text-sm font-medium leading-relaxed text-muted">
                اگر درباره ثبت‌نام، لایسنس، پرداخت یا مسیر دوره سوالی داری،
                از اینجا پیام بفرست.
            </p>
        </header>
    );
}
