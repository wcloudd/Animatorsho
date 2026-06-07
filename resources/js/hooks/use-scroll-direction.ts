import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';

const SCROLL_DELTA_THRESHOLD = 8;
const TOP_OFFSET_THRESHOLD = 12;
const MIN_SCROLLABLE_HEIGHT = 72;

export function useScrollDirectionNavVisible(): boolean {
    const isMobile = useIsMobile();
    const { url } = usePage();
    const [visible, setVisible] = useState(true);
    const lastScrollY = useRef(0);
    const ticking = useRef(false);

    useEffect(() => {
        if (!isMobile) {
            setVisible(true);
            return;
        }

        setVisible(true);
        lastScrollY.current = window.scrollY;

        const evaluateVisibility = (): void => {
            const scrollY = window.scrollY;
            const maxScroll =
                document.documentElement.scrollHeight - window.innerHeight;

            if (maxScroll < MIN_SCROLLABLE_HEIGHT) {
                setVisible(true);
                lastScrollY.current = scrollY;
                ticking.current = false;
                return;
            }

            if (scrollY <= TOP_OFFSET_THRESHOLD) {
                setVisible(true);
            } else {
                const delta = scrollY - lastScrollY.current;

                if (delta > SCROLL_DELTA_THRESHOLD) {
                    setVisible(false);
                } else if (delta < -SCROLL_DELTA_THRESHOLD) {
                    setVisible(true);
                }
            }

            lastScrollY.current = scrollY;
            ticking.current = false;
        };

        const onScroll = (): void => {
            if (ticking.current) {
                return;
            }

            ticking.current = true;
            window.requestAnimationFrame(evaluateVisibility);
        };

        evaluateVisibility();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => {
            window.removeEventListener('scroll', onScroll);
        };
    }, [isMobile, url]);

    return isMobile ? visible : true;
}
