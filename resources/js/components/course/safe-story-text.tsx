type SafeStoryTextProps = {
    html: string;
    className?: string;
};

export function SafeStoryText({ html, className }: SafeStoryTextProps) {
    if (!html) {
        return null;
    }

    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
