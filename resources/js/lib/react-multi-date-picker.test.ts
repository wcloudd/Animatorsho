import { describe, expect, it } from 'vitest';
import DatePicker from '@/lib/react-multi-date-picker';

describe('react-multi-date-picker import', () => {
    it('exports a renderable react component', () => {
        expect(DatePicker).toBeTruthy();
        expect(
            typeof DatePicker === 'function' ||
                (typeof DatePicker === 'object' &&
                    DatePicker !== null &&
                    'render' in DatePicker),
        ).toBe(true);
    });
});
