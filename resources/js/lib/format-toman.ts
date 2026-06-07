const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'] as const;

export function formatTomanPrice(amount: number): string {
    const western = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const persian = western.replace(
        /\d/g,
        (digit) => persianDigits[Number(digit)] ?? digit,
    );

    return `${persian} تومان`;
}
