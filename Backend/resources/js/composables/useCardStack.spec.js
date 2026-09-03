import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { useCardStack } from './useCardStack';

// jsdom does not implement matchMedia — useCardStack reads it synchronously
// on every call to decide whether autoplay/reduced-motion applies.
let matchMediaMatches = false;
beforeEach(() => {
    matchMediaMatches = false;
    vi.stubGlobal('matchMedia', vi.fn((query) => ({ media: query, matches: matchMediaMatches })));
});

// observeVisibility() creates a real IntersectionObserver, unavailable in jsdom by default.
let ioCallback = null;
let ioDisconnect;
beforeEach(() => {
    ioDisconnect = vi.fn();
    vi.stubGlobal('IntersectionObserver', class {
        constructor(cb) {
            ioCallback = cb;
        }
        observe() {}
        unobserve() {}
        disconnect() {
            ioDisconnect();
        }
    });
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

function mountCardStack(count, options) {
    let api;
    const wrapper = mount(defineComponent({
        setup() {
            api = useCardStack(count, options);
            return () => null;
        },
    }));
    return { wrapper, api };
}

function pointerEvent(clientX) {
    return { clientX, target: { closest: () => null } };
}

describe('useCardStack', () => {
    it('starts on card 0, not dragging, and wasClick() is true before any interaction', () => {
        const { api } = mountCardStack(4, { rtl: ref(false) });

        expect(api.activeIndex.value).toBe(0);
        expect(api.isDragging.value).toBe(false);
        expect(api.isPaused.value).toBe(false);
        expect(api.wasClick()).toBe(true);
    });

    it('next()/prev()/goTo() wrap around using modulo arithmetic', () => {
        const { api } = mountCardStack(3, { rtl: ref(false) });

        api.next();
        expect(api.activeIndex.value).toBe(1);
        api.next();
        api.next();
        expect(api.activeIndex.value).toBe(0); // wrapped past the end

        api.prev();
        expect(api.activeIndex.value).toBe(2); // wrapped before the start

        api.goTo(5);
        expect(api.activeIndex.value).toBe(2); // 5 % 3 == 2

        api.goTo(-1);
        expect(api.activeIndex.value).toBe(2); // ((-1 % 3) + 3) % 3 == 2
    });

    it('cardStyle computes translateX/rotate/zIndex/opacity for the active and offset cards (LTR)', () => {
        const { api } = mountCardStack(4, { rtl: ref(false), maxDepth: 2 });

        const active = api.cardStyle(0);
        expect(active.transform).toBe('translateX(0%) scale(1) rotate(0deg)');
        expect(active.opacity).toBe(1);
        expect(active.zIndex).toBe(4);
        expect(active.pointerEvents).toBe('auto');

        // i=3 wraps to offset -1 relative to activeIndex 0 (3 - 4 == -1)
        const wrapped = api.cardStyle(3);
        expect(wrapped.transform).toBe('translateX(-34%) scale(0.9) rotate(3deg)');
        expect(wrapped.opacity).toBeCloseTo(0.68);
        expect(wrapped.zIndex).toBe(3);
        expect(wrapped.pointerEvents).toBe('auto');
    });

    it('cardStyle flips direction (translateX/rotate sign) when rtl is true', () => {
        const { api } = mountCardStack(4, { rtl: ref(true), maxDepth: 2 });

        const wrapped = api.cardStyle(3); // still offset -1 from active 0
        expect(wrapped.transform).toBe('translateX(34%) scale(0.9) rotate(-3deg)');
    });

    it('hides cards beyond maxDepth (opacity 0, pointerEvents none) — edge case around the boundary', () => {
        const { api } = mountCardStack(4, { rtl: ref(false), maxDepth: 1 });

        // offset 2 (i=2, active=0): 2 > count/2(2) is false, so NOT wrapped — stays +2, beyond maxDepth 1
        const hidden = api.cardStyle(2);
        expect(hidden.opacity).toBe(0);
        expect(hidden.pointerEvents).toBe('none');

        // offset 1 is within maxDepth 1 and stays visible
        const visible = api.cardStyle(1);
        expect(visible.pointerEvents).toBe('auto');
        expect(visible.opacity).toBeCloseTo(0.68);
    });

    it('a drag past the threshold to the left triggers next(), and wasClick() becomes false', () => {
        const { api } = mountCardStack(4, { rtl: ref(false) });

        api.onPointerDown(pointerEvent(100));
        expect(api.isDragging.value).toBe(true);
        api.onPointerMove(pointerEvent(50)); // delta -50 -> dragDeltaX ~ -16.7% (falls back to 300px width)
        api.onPointerUp();

        expect(api.activeIndex.value).toBe(1);
        expect(api.isDragging.value).toBe(false);
        expect(api.wasClick()).toBe(false);
    });

    it('a drag past the threshold to the right triggers prev()', () => {
        const { api } = mountCardStack(4, { rtl: ref(false) });

        api.onPointerDown(pointerEvent(100));
        api.onPointerMove(pointerEvent(150)); // delta +50
        api.onPointerUp();

        expect(api.activeIndex.value).toBe(3); // wrapped prev from 0
    });

    it('a small drag under the swipe threshold does not change the active card', () => {
        const { api } = mountCardStack(4, { rtl: ref(false) });

        api.onPointerDown(pointerEvent(100));
        api.onPointerMove(pointerEvent(105)); // delta 5 -> dragDeltaX ~1.7%, well under the 12% threshold
        api.onPointerUp();

        expect(api.activeIndex.value).toBe(0);
    });

    it('onPointerMove/onPointerUp are no-ops when not currently dragging', () => {
        const { api } = mountCardStack(4, { rtl: ref(false) });

        api.onPointerMove(pointerEvent(200)); // never pointer-down'ed
        api.onPointerUp();

        expect(api.activeIndex.value).toBe(0);
        expect(api.isDragging.value).toBe(false);
    });

    it('autoplay advances the active card every autoplayMs while mounted, in view, and not paused', async () => {
        vi.useFakeTimers();
        const { api } = mountCardStack(3, { rtl: ref(false), autoplayMs: 1000 });

        await vi.advanceTimersByTimeAsync(1000);
        expect(api.activeIndex.value).toBe(1);

        await vi.advanceTimersByTimeAsync(1000);
        expect(api.activeIndex.value).toBe(2);
    });

    it('autoplay is skipped while isPaused is true', async () => {
        vi.useFakeTimers();
        const { api } = mountCardStack(3, { rtl: ref(false), autoplayMs: 1000 });

        api.isPaused.value = true;
        await vi.advanceTimersByTimeAsync(3000);

        expect(api.activeIndex.value).toBe(0);
    });

    it('autoplay is skipped while the section is out of view, via observeVisibility + the IntersectionObserver callback', async () => {
        vi.useFakeTimers();
        const { api } = mountCardStack(3, { rtl: ref(false), autoplayMs: 1000 });

        api.observeVisibility({});
        ioCallback([{ isIntersecting: false }]);

        await vi.advanceTimersByTimeAsync(3000);
        expect(api.activeIndex.value).toBe(0);

        ioCallback([{ isIntersecting: true }]);
        await vi.advanceTimersByTimeAsync(1000);
        expect(api.activeIndex.value).toBe(1);
    });

    it('does not start autoplay at all when prefers-reduced-motion is set', async () => {
        matchMediaMatches = true;
        vi.useFakeTimers();
        const { api } = mountCardStack(3, { rtl: ref(false), autoplayMs: 1000 });

        await vi.advanceTimersByTimeAsync(5000);
        expect(api.activeIndex.value).toBe(0);
    });

    it('dragging pauses autoplay (stopAutoplay on pointerdown) and resumes it on pointerup', async () => {
        vi.useFakeTimers();
        const { api } = mountCardStack(3, { rtl: ref(false), autoplayMs: 1000 });

        api.onPointerDown(pointerEvent(100));
        await vi.advanceTimersByTimeAsync(1000);
        expect(api.activeIndex.value).toBe(0); // autoplay timer was cleared by pointerdown

        api.onPointerUp(); // small/no movement -> no next/prev, but restarts autoplay
        await vi.advanceTimersByTimeAsync(1000);
        expect(api.activeIndex.value).toBe(1);
    });

    it('unmounting stops the autoplay timer and disconnects the IntersectionObserver', async () => {
        vi.useFakeTimers();
        const { api, wrapper } = mountCardStack(3, { rtl: ref(false), autoplayMs: 1000 });
        api.observeVisibility({});

        wrapper.unmount();

        expect(ioDisconnect).toHaveBeenCalledTimes(1);
        await vi.advanceTimersByTimeAsync(5000);
        expect(api.activeIndex.value).toBe(0);
    });
});
