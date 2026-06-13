import { describe, expect, it } from 'vitest';
import {
    dateObjectToGregorianIso,
    gregorianIsoToDateObject,
    gregorianIsoToJalaliDisplay,
    isGregorianIsoDate,
    jalaliToGregorianIso,
} from '@/lib/jalali-date';

describe('jalali-date helpers', () => {
    it('validates gregorian iso date strings', () => {
        expect(isGregorianIsoDate('2026-06-01')).toBe(true);
        expect(isGregorianIsoDate('2026-6-01')).toBe(false);
        expect(isGregorianIsoDate('')).toBe(false);
        expect(isGregorianIsoDate(null)).toBe(false);
    });

    it('converts gregorian iso to jalali display format', () => {
        expect(gregorianIsoToJalaliDisplay('2026-06-01')).toBe('۱۴۰۵/۰۳/۱۱');
        expect(gregorianIsoToJalaliDisplay('invalid')).toBe('');
    });

    it('converts jalali parts to gregorian iso', () => {
        expect(jalaliToGregorianIso(1405, 3, 11)).toBe('2026-06-01');
    });

    it('round-trips gregorian iso through date object conversion', () => {
        const dateObject = gregorianIsoToDateObject('2026-06-10');

        expect(dateObject?.format('YYYY/MM/DD')).toBe('۱۴۰۵/۰۳/۲۰');
        expect(dateObjectToGregorianIso(dateObject ?? null)).toBe('2026-06-10');
    });

    it('returns null for empty date object values', () => {
        expect(dateObjectToGregorianIso(null)).toBeNull();
        expect(dateObjectToGregorianIso(undefined)).toBeNull();
    });
});
