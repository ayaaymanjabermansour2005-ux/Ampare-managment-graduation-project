import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';

// jsdom does not implement matchMedia — useScrollGlow reads it once,
// synchronously, when the composable function runs.
let matchMediaMatches = false;
beforeEach(() => {
    matchMediaMatches = false;
    vi.stubGlobal('matchMedia', vi.fn((query) => ({ media: query, matches: matchMediaMatches })));
});

// IntersectionObserver is unavailable in jsdom by default.
let ioCallback = null;
let ioObserve;
let ioDisconnect;
let IntersectionObserverCtor;
beforeEach(() => {
    ioObserve = vi.fn();
    ioDisconnect = vi.fn();
    IntersectionObserverCtor = vi.fn(function (cb) {
        ioCallback = cb;
        this.observe = ioObserve;
        this.disconnect = ioDisconnect;
    });
    vi.stubGlobal('IntersectionObserver', IntersectionObserverCtor);
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

const { useScrollGlow } = await import('./useScrollGlow');

function mountScrollGlow() {
    let api;
    const wrapper = mount(defineComponent({
        setup() {
            api = useScrollGlow();
            return { stageEl: api };
        },
        template: '<section ref="stageEl"></section>',
    }), { attachTo: document.body });
    return { wrapper, stageEl: api };
}

describe('useScrollGlow', () => {
    it('returns a stageEl ref that stays null, and skips creating an observer, when it is never bound to an element', () => {
        let api;
        const wrapper = mount(defineComponent({
            setup() {
                api = useScrollGlow();
                return () => null; // no template ref ever assigns api.value
            },
        }));

        expect(api.value).toBe(null);
        expect(IntersectionObserverCtor).not.toHaveBeenCalled();
        expect(() => wrapper.unmount()).not.toThrow();
    });

    it('on mount, observes stageEl with an IntersectionObserver (threshold 0)', () => {
        const { wrapper, stageEl } = mountScrollGlow();

        expect(IntersectionObserverCtor).toHaveBeenCalledWith(expect.any(Function), { threshold: 0 });
        expect(ioObserve).toHaveBeenCalledWith(stageEl.value);

        wrapper.unmount();
    });

    it('when the observer reports isIntersecting:true, starts listening (adds scroll+resize) and immediately updates --scroll-glow', () => {
        const { wrapper, stageEl } = mountScrollGlow();
        stageEl.value.getBoundingClientRect = () => ({ top: 100, height: 200 });
        Object.defineProperty(window, 'innerHeight', { value: 800, configurable: true });
        const addSpy = vi.spyOn(window, 'addEventListener');

        ioCallback([{ isIntersecting: true }]);

        expect(addSpy).toHaveBeenCalledWith('scroll', expect.any(Function), { passive: true });
        expect(addSpy).toHaveBeenCalledWith('resize', expect.any(Function), { passive: true });
        // total = height(200) + vh(800) = 1000; progress = (800 - 100) / 1000 = 0.7
        expect(stageEl.value.style.getPropertyValue('--scroll-glow')).toBe('0.7000');

        wrapper.unmount();
    });

    it('when the observer reports isIntersecting:false, stops listening (removes scroll+resize)', () => {
        const { wrapper, stageEl } = mountScrollGlow();
        stageEl.value.getBoundingClientRect = () => ({ top: 100, height: 200 });
        const removeSpy = vi.spyOn(window, 'removeEventListener');

        ioCallback([{ isIntersecting: true }]); // start first
        ioCallback([{ isIntersecting: false }]); // then stop

        expect(removeSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
        expect(removeSpy).toHaveBeenCalledWith('resize', expect.any(Function));

        wrapper.unmount();
    });

    it('start() is idempotent: repeated isIntersecting:true entries do not double-register listeners', () => {
        const { wrapper, stageEl } = mountScrollGlow();
        stageEl.value.getBoundingClientRect = () => ({ top: 0, height: 100 });
        const addSpy = vi.spyOn(window, 'addEventListener');

        ioCallback([{ isIntersecting: true }]);
        ioCallback([{ isIntersecting: true }]);

        expect(addSpy.mock.calls.filter(([type]) => type === 'scroll')).toHaveLength(1);

        wrapper.unmount();
    });

    it('batches scroll/resize ticks: requestAnimationFrame(update) is only scheduled once until the pending frame runs', () => {
        const rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockReturnValue(1);
        const { wrapper, stageEl } = mountScrollGlow();
        stageEl.value.getBoundingClientRect = () => ({ top: 0, height: 100 });
        ioCallback([{ isIntersecting: true }]);
        rafSpy.mockClear(); // drop the call made by start()'s own update()

        window.dispatchEvent(new Event('scroll'));
        window.dispatchEvent(new Event('resize'));

        expect(rafSpy).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });

    it('does not create an IntersectionObserver when prefers-reduced-motion is set', () => {
        matchMediaMatches = true;
        const { wrapper } = mountScrollGlow();

        expect(IntersectionObserverCtor).not.toHaveBeenCalled();

        expect(() => wrapper.unmount()).not.toThrow();
    });

    it('does not create an IntersectionObserver, and does not throw on unmount, when IntersectionObserver is unavailable', () => {
        vi.unstubAllGlobals(); // remove the stubbed IntersectionObserver entirely
        matchMediaMatches = false;
        vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: false })));

        const { wrapper } = mountScrollGlow();

        expect(() => wrapper.unmount()).not.toThrow();
    });

    it('on unmount, stops listening (removes scroll+resize, cancels any pending rAF) and disconnects the observer', () => {
        const cancelRafSpy = vi.spyOn(window, 'cancelAnimationFrame').mockImplementation(() => {});
        vi.spyOn(window, 'requestAnimationFrame').mockReturnValue(42);
        const removeSpy = vi.spyOn(window, 'removeEventListener');
        const { wrapper, stageEl } = mountScrollGlow();
        stageEl.value.getBoundingClientRect = () => ({ top: 0, height: 100 });
        ioCallback([{ isIntersecting: true }]);
        window.dispatchEvent(new Event('scroll')); // schedule a pending rAF frame

        wrapper.unmount();

        expect(removeSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
        expect(removeSpy).toHaveBeenCalledWith('resize', expect.any(Function));
        expect(cancelRafSpy).toHaveBeenCalledWith(42);
        expect(ioDisconnect).toHaveBeenCalledTimes(1);
    });
});
