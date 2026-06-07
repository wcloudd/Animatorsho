import type { ResolvedAvatar } from '@/lib/resolve-preset-avatar';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type ProfileUserAvatarProps = {
    resolved: ResolvedAvatar;
    className?: string;
    fallbackClassName?: string;
    emojiClassName?: string;
};

export function ProfileUserAvatar({
    resolved,
    className,
    fallbackClassName,
    emojiClassName,
}: ProfileUserAvatarProps) {
    const getInitials = useInitials();

    if (resolved.kind === 'initials') {
        return (
            <Avatar className={className}>
                <AvatarFallback
                    className={cn(
                        'bg-purple-gradient text-lg font-bold text-white',
                        fallbackClassName,
                    )}
                >
                    {getInitials(resolved.name)}
                </AvatarFallback>
            </Avatar>
        );
    }

    if (resolved.kind === 'static') {
        return (
            <Avatar className={className}>
                <img
                    src={resolved.src}
                    alt={resolved.preset.labelFa}
                    className="aspect-square size-full object-cover"
                />
                <AvatarFallback
                    className={cn(
                        resolved.preset.placeholder.bgClass,
                        fallbackClassName,
                    )}
                >
                    <span className={cn('text-2xl', emojiClassName)} aria-hidden>
                        {resolved.preset.placeholder.emoji}
                    </span>
                </AvatarFallback>
            </Avatar>
        );
    }

    const preset = resolved.preset;

    return (
        <Avatar className={className}>
            <AvatarFallback
                className={cn(
                    preset.placeholder.bgClass,
                    'font-bold text-text',
                    fallbackClassName,
                )}
            >
                <span className={cn('text-2xl leading-none', emojiClassName)} aria-hidden>
                    {preset.placeholder.emoji}
                </span>
                <span className="sr-only">{preset.labelFa}</span>
            </AvatarFallback>
        </Avatar>
    );
}
