import DateObject from 'react-date-object';
import gregorian from 'react-date-object/calendars/gregorian';
import persian from 'react-date-object/calendars/persian';
import gregorian_en from 'react-date-object/locales/gregorian_en';
import persian_fa from 'react-date-object/locales/persian_fa';

const GREGORIAN_ISO_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

export function isGregorianIsoDate(value: string | null | undefined): boolean {
    return value !== null && value !== undefined && GREGORIAN_ISO_PATTERN.test(value);
}

export function gregorianIsoToJalaliDisplay(
    iso: string | null | undefined,
): string {
    if (!isGregorianIsoDate(iso)) {
        return '';
    }

    try {
        return new DateObject({
            date: iso,
            format: 'YYYY-MM-DD',
            calendar: gregorian,
        })
            .convert(persian, persian_fa)
            .format('YYYY/MM/DD');
    } catch {
        return '';
    }
}

export function gregorianIsoToDateObject(
    iso: string | null | undefined,
): DateObject | undefined {
    if (!isGregorianIsoDate(iso)) {
        return undefined;
    }

    try {
        return new DateObject({
            date: iso,
            format: 'YYYY-MM-DD',
            calendar: gregorian,
        }).convert(persian, persian_fa);
    } catch {
        return undefined;
    }
}

export function jalaliToGregorianIso(
    year: number,
    month: number,
    day: number,
): string | null {
    try {
        return new DateObject({
            calendar: persian,
            locale: persian_fa,
            year,
            month,
            day,
        })
            .convert(gregorian, gregorian_en)
            .format('YYYY-MM-DD');
    } catch {
        return null;
    }
}

export function dateObjectToGregorianIso(
    date: DateObject | DateObject[] | null | undefined,
): string | null {
    if (date === null || date === undefined) {
        return null;
    }

    const selected = Array.isArray(date) ? date[0] : date;

    if (!selected) {
        return null;
    }

    try {
        return new DateObject(selected)
            .convert(gregorian, gregorian_en)
            .format('YYYY-MM-DD');
    } catch {
        return null;
    }
}
