import { Bold, List, ListOrdered } from 'lucide-react';
import type { RefObject } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { userLabelClassName, userTextareaClassName } from '@/lib/user-form-styles';
import { cn } from '@/lib/utils';

type SimpleWritingEditorProps = {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    helperText?: string;
    rows?: number;
    textareaRef?: RefObject<HTMLTextAreaElement | null>;
};

function insertAtCursor(
    textarea: HTMLTextAreaElement,
    before: string,
    after = '',
    placeholder = '',
): string {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.slice(start, end) || placeholder;
    const nextValue =
        textarea.value.slice(0, start) +
        before +
        selected +
        after +
        textarea.value.slice(end);

    const cursor = start + before.length + selected.length + after.length;
    window.requestAnimationFrame(() => {
        textarea.focus();
        textarea.setSelectionRange(cursor, cursor);
    });

    return nextValue;
}

function wrapLinePrefix(textarea: HTMLTextAreaElement, prefix: string): string {
    const start = textarea.selectionStart;
    const value = textarea.value;
    const lineStart = value.lastIndexOf('\n', start - 1) + 1;
    const lineEndIndex = value.indexOf('\n', start);
    const lineEnd = lineEndIndex === -1 ? value.length : lineEndIndex;
    const line = value.slice(lineStart, lineEnd);

    if (line.startsWith(prefix)) {
        return value;
    }

    const nextValue =
        value.slice(0, lineStart) + prefix + value.slice(lineStart);

    window.requestAnimationFrame(() => {
        textarea.focus();
        textarea.setSelectionRange(start + prefix.length, start + prefix.length);
    });

    return nextValue;
}

export function SimpleWritingEditor({
    id,
    label,
    value,
    onChange,
    error,
    helperText,
    rows = 6,
    textareaRef,
}: SimpleWritingEditorProps) {
    const applyBold = () => {
        const textarea = textareaRef?.current;

        if (!textarea) {
            return;
        }

        onChange(insertAtCursor(textarea, '**', '**', 'متن پررنگ'));
    };

    const applyBullet = () => {
        const textarea = textareaRef?.current;

        if (!textarea) {
            return;
        }

        onChange(wrapLinePrefix(textarea, '- '));
    };

    const applyNumbered = () => {
        const textarea = textareaRef?.current;

        if (!textarea) {
            return;
        }

        onChange(wrapLinePrefix(textarea, '1. '));
    };

    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className={userLabelClassName}>
                {label}
            </Label>

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={applyBold}
                    className="inline-flex items-center gap-1 rounded-pill bg-bg px-3 py-1.5 text-xs font-bold text-text ring-1 ring-border/70"
                >
                    <Bold className="size-3.5" />
                    پررنگ
                </button>
                <button
                    type="button"
                    onClick={applyBullet}
                    className="inline-flex items-center gap-1 rounded-pill bg-bg px-3 py-1.5 text-xs font-bold text-text ring-1 ring-border/70"
                >
                    <List className="size-3.5" />
                    لیست
                </button>
                <button
                    type="button"
                    onClick={applyNumbered}
                    className="inline-flex items-center gap-1 rounded-pill bg-bg px-3 py-1.5 text-xs font-bold text-text ring-1 ring-border/70"
                >
                    <ListOrdered className="size-3.5" />
                    شماره‌دار
                </button>
            </div>

            <textarea
                id={id}
                ref={textareaRef}
                name="description"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                rows={rows}
                className={cn(userTextareaClassName, 'min-h-[140px]')}
            />

            {helperText ? (
                <p className="text-xs font-medium text-muted">{helperText}</p>
            ) : null}

            <InputError message={error} />
        </div>
    );
}
