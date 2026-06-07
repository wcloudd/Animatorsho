const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'] as const;

function toPersianDigits(value: string): string {
    return value.replace(
        /\d/g,
        (digit) => persianDigits[Number(digit)] ?? digit,
    );
}

export function formatProfileDate(isoDate: string | null): string | null {
    if (!isoDate) {
        return null;
    }

    const date = new Date(isoDate);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    const formatted = date.toLocaleDateString('fa-IR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });

    return toPersianDigits(formatted);
}
