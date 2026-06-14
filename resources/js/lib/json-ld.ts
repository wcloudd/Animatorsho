/**
 * Serialize a JSON-LD entry for inline injection into a <script> tag.
 *
 * `JSON.stringify` does not escape `<`, so a value containing `</script>`
 * could break out of the script element. Escaping every `<` as the equivalent
 * `<` keeps the JSON valid (and identical once parsed) while making
 * script break-out impossible.
 */
export function serializeJsonLd(entry: unknown): string {
    return JSON.stringify(entry).replace(/</g, '\\u003c');
}
