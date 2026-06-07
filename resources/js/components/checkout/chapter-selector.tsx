import type { CatalogPackage } from '@/lib/checkout-catalog';
import { cn } from '@/lib/utils';

type ChapterSelectorProps = {
    chapterPackages: CatalogPackage[];
    selectedSlug: string;
    onSelectSlug: (slug: string) => void;
};

export function ChapterSelector({
    chapterPackages,
    selectedSlug,
    onSelectSlug,
}: ChapterSelectorProps) {
    return (
        <fieldset className="flex w-full flex-col gap-2">
            <legend className="sr-only">انتخاب فصل</legend>
            {chapterPackages.map((chapter) => {
                const isSelected = selectedSlug === chapter.slug;

                return (
                    <label
                        key={chapter.slug}
                        className={cn(
                            'flex cursor-pointer items-center gap-3 rounded-2xl border px-4 py-3 text-start text-sm font-medium transition-colors',
                            isSelected
                                ? 'border-purple bg-purple-soft text-text'
                                : 'border-border bg-surface text-muted hover:bg-purple-soft/50',
                        )}
                    >
                        <input
                            type="radio"
                            name="chapter"
                            value={chapter.slug}
                            checked={isSelected}
                            onChange={() => onSelectSlug(chapter.slug)}
                            className="size-4 accent-purple"
                        />
                        <span>{chapter.title}</span>
                    </label>
                );
            })}
        </fieldset>
    );
}
