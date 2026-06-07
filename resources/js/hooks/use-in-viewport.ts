import { useEffect, useState, type RefObject } from 'react';

type UseInViewportOptions = {
    rootMargin?: string;
    threshold?: number | number[];
    enabled?: boolean;
};

export function useInViewport(
    ref: RefObject<Element | null>,
    {
        rootMargin = '200px 0px',
        threshold = 0.01,
        enabled = true,
    }: UseInViewportOptions = {},
): boolean {
    const [isInViewport, setIsInViewport] = useState(false);

    useEffect(() => {
        const node = ref.current;

        if (!enabled || !node) {
            setIsInViewport(false);
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                setIsInViewport(entry.isIntersecting);
            },
            { rootMargin, threshold },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [enabled, rootMargin, threshold]);

    return isInViewport;
}
