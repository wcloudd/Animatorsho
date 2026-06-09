export function secondsUntil(isoDate: string): number {
    const target = new Date(isoDate).getTime();
    const diff = Math.ceil((target - Date.now()) / 1000);

    return diff > 0 ? diff : 0;
}
