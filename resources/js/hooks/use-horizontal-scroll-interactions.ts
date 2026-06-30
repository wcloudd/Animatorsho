import { useEffect, useRef } from 'react';

export function useHorizontalScrollInteractions() {
    const ref = useRef<HTMLDivElement>(null);
    const isDragging = useRef(false);
    const hasDragged = useRef(false);
    const suppressNextClick = useRef(false);
    const activePointerId = useRef(-1);
    const startX = useRef(0);
    const startScrollLeft = useRef(0);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        function onWheel(e: WheelEvent) {
            if (e.deltaY === 0) return;
            const isRTL = getComputedStyle(el!).direction === 'rtl';
            // RTL: wheel-down scrolls toward visual left (content end)
            const scrollDelta = isRTL ? -e.deltaY : e.deltaY;
            const before = el!.scrollLeft;
            el!.scrollLeft += scrollDelta;
            // Only consume the event if the container actually moved;
            // at the boundary the page scrolls normally.
            if (Math.abs(el!.scrollLeft - before) > 0.5) {
                e.preventDefault();
            }
            // Wheel scrolling never touches the click-suppression flag.
        }

        function onPointerDown(e: PointerEvent) {
            if (e.button !== 0 || e.pointerType !== 'mouse') return;
            isDragging.current = true;
            hasDragged.current = false;
            startX.current = e.clientX;
            startScrollLeft.current = el!.scrollLeft;
            activePointerId.current = e.pointerId;
            // IMPORTANT: do NOT call setPointerCapture here.
            // Calling it on every pointerdown — before we know whether this is a drag
            // or a plain click — redirects mouseup to the container element, which
            // causes the browser to fire the resulting click on the container instead
            // of the child button/card. That click never bubbles DOWN to the child,
            // so the child's onClick handler never fires.
            // We call setPointerCapture only in onPointerMove once the drag
            // threshold (5 px) has been confirmed.
        }

        function onPointerMove(e: PointerEvent) {
            if (!isDragging.current || e.pointerId !== activePointerId.current) return;
            const dx = e.clientX - startX.current;

            if (!hasDragged.current && Math.abs(dx) > 5) {
                hasDragged.current = true;
                document.body.style.cursor = 'grabbing';
                document.body.style.userSelect = 'none';
                // Capture only now that we are sure this is a drag, not a click.
                // setPointerCapture is valid from pointermove handlers for the same
                // pointer ID; all subsequent pointermove/pointerup will go to el.
                try {
                    el!.setPointerCapture(e.pointerId);
                } catch {
                    // Pointer may have been released between events — safe to ignore.
                }
            }

            if (hasDragged.current) {
                // Works for both LTR and RTL: the scrollLeft direction mirrors the
                // drag direction for both writing modes.
                el!.scrollLeft = startScrollLeft.current - dx;
            }
        }

        function onPointerUp(e: PointerEvent) {
            if (!isDragging.current || e.pointerId !== activePointerId.current) return;
            isDragging.current = false;
            activePointerId.current = -1;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';

            if (hasDragged.current) {
                // Arm a one-shot suppression for the accidental click the browser may
                // synthesise immediately after a drag-release.
                suppressNextClick.current = true;
                // Browsers often skip the click after a real drag (pointerdown and
                // pointerup are far apart). If so onClickCapture never fires, so we
                // clear the flag ourselves before the next frame so future clicks are
                // never permanently blocked.
                requestAnimationFrame(() => {
                    suppressNextClick.current = false;
                });
            }
            hasDragged.current = false;
        }

        function onPointerCancel(e: PointerEvent) {
            if (!isDragging.current || e.pointerId !== activePointerId.current) return;
            isDragging.current = false;
            hasDragged.current = false;
            activePointerId.current = -1;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }

        function onLostPointerCapture(e: PointerEvent) {
            // Guard against the browser revoking capture (navigation, system dialogs).
            if (e.pointerId === activePointerId.current && isDragging.current) {
                isDragging.current = false;
                hasDragged.current = false;
                activePointerId.current = -1;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            }
        }

        // Capture-phase click handler: suppress only the single accidental click that
        // the browser fires immediately after a drag release. After that one shot the
        // flag is cleared and future clicks are allowed through untouched.
        function onClickCapture(e: MouseEvent) {
            if (suppressNextClick.current) {
                suppressNextClick.current = false; // one-shot — consume immediately
                e.stopPropagation();
                e.preventDefault();
            }
        }

        el.addEventListener('wheel', onWheel, { passive: false });
        el.addEventListener('pointerdown', onPointerDown);
        el.addEventListener('pointermove', onPointerMove);
        el.addEventListener('pointerup', onPointerUp);
        el.addEventListener('pointercancel', onPointerCancel);
        el.addEventListener('lostpointercapture', onLostPointerCapture);
        el.addEventListener('click', onClickCapture, { capture: true });

        return () => {
            el.removeEventListener('wheel', onWheel);
            el.removeEventListener('pointerdown', onPointerDown);
            el.removeEventListener('pointermove', onPointerMove);
            el.removeEventListener('pointerup', onPointerUp);
            el.removeEventListener('pointercancel', onPointerCancel);
            el.removeEventListener('lostpointercapture', onLostPointerCapture);
            el.removeEventListener('click', onClickCapture, { capture: true });
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        };
    }, []);

    return ref;
}
