import { describe, expect, it } from 'vitest';
import { serializeJsonLd } from '@/lib/json-ld';

describe('serializeJsonLd', () => {
    it('escapes "<" so a value cannot break out of the script tag', () => {
        const result = serializeJsonLd({
            name: '</script><script>alert(1)</script>',
        });

        expect(result).not.toContain('</script>');
        expect(result).not.toContain('<');
        expect(result).toContain('\\u003c');
    });

    it('stays valid JSON that parses back to the original value', () => {
        const entry = { '@type': 'Course', name: 'انیماتورشو', price: 100 };

        expect(JSON.parse(serializeJsonLd(entry))).toEqual(entry);
    });
});
