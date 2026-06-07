import { TRUST_NOTE_TEXT } from '@/lib/checkout-confirm';

export function TrustNote() {
    return (
        <p className="text-center text-xs font-medium leading-relaxed text-muted">
            {TRUST_NOTE_TEXT}
        </p>
    );
}
