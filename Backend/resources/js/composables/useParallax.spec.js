import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { useParallax } from './useParallax';

describe('useParallax', () => {
    let rafSpy;
    let rectSpy;

    beforeEach(() => {
        Object.defineProperty(window, 'innerHeight', { value: 800, configurable: true, writable: true });
        rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockReturnValue(1);
        // Stubbed per-test via rectSpy.mockReturnValue(...); every element in
        // the tree shares this since it's on the shared prototype.
        rectSpy = vi.spyOn(Element.prototype, 'getBoundingClientRect').mockReturnValue({ top: 0, bottom: 0, height: 0 });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    function mountParallax() {
        const elA = ref(null);
        const elB = ref(null);
        const wrapper = mount(defineComponent({
            setup() {
                useParallax([
                    { el: elA, speed: 0.3 },
                    { el: elB, speed: 0.6 },
                ]);
                return { elA, elB };
            },
            template: '<section><div ref="elA"></div><div ref="elB"></div></section>',
        }), { attachTo: document.body });
        return { wrapper, elA, elB, section: wrapper.element };
    }

    it('on mount, calls update() synchronously and sets translate3d transforms proportional to each layer speed', () => {
        rectSpy.mockReturnValue({ top: -100, bottom: 500, height: 600 });

        const { elA, elB, wrapper } = mountParallax();

        // scrollProgress = -rect.top = 100 -> translate3d(0, 100*speed, 0)
        expect(elA.value.style.transform).toBe('translate3d(0, 30px, 0)');
        expect(elB.value.style.transform).toBe('translate3d(0, 60px, 0)');

        wrapper.unmount();
    });

    it('registers the scroll listener as passive on mount, and removes the same handler reference on unmount', () => {
        const addSpy = vi.spyOn(window, 'addEventListener');
        const removeSpy = vi.spyOn(window, 'removeEventListener');
        const { wrapper } = mountParallax();

        expect(addSpy).toHaveBeenCalledWith('scroll', expect.any(Function), { passive: true });
        const handler = addSpy.mock.calls.find(([type]) => type === 'scroll')[1];

        wrapper.unmount();

        expect(removeSpy).toHaveBeenCalledWith('scroll', handler);
    });

    it('a scroll event schedules requestAnimationFrame(update), which recomputes the transform from the current rect', () => {
        rectSpy.mockReturnValue({ top: -100, bottom: 500, height: 600 });
        const { wrapper, elA } = mountParallax();
        rafSpy.mockClear(); // drop the call made by onMounted's own update()

        rectSpy.mockReturnValue({ top: -200, bottom: 500, height: 600 }); // scrolled further down
        rafSpy.mockImplementationOnce((cb) => { cb(); return 1; });
        window.dispatchEvent(new Event('scroll'));

        expect(rafSpy).toHaveBeenCalledTimes(1);
        expect(elA.value.style.transform).toBe('translate3d(0, 60px, 0)'); // 200 * 0.3

        wrapper.unmount();
    });

    it('batches scroll events: requestAnimationFrame is only scheduled once until the pending frame runs (the "ticking" guard)', () => {
        const { wrapper } = mountParallax();
        rafSpy.mockClear();

        window.dispatchEvent(new Event('scroll'));
        window.dispatchEvent(new Event('scroll'));
        window.dispatchEvent(new Event('scroll'));

        expect(rafSpy).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });

    it('skips updating transforms when the section is scrolled out of view below the fold (rect.top > innerHeight)', () => {
        rectSpy.mockReturnValue({ top: 5000, bottom: 5100, height: 100 });

        const { elA, wrapper } = mountParallax();

        expect(elA.value.style.transform).toBe('');
        wrapper.unmount();
    });

    it('skips updating transforms once the section has fully scrolled past (rect.bottom < 0)', () => {
        rectSpy.mockReturnValue({ top: -900, bottom: -10, height: 100 });

        const { elA, wrapper } = mountParallax();

        expect(elA.value.style.transform).toBe('');
        wrapper.unmount();
    });

    it('falls back to the parent element as the tracked section when layers[0].el has no <section> ancestor', () => {
        rectSpy.mockReturnValue({ top: -40, bottom: 200, height: 240 });
        const elA = ref(null);
        const wrapper = mount(defineComponent({
            setup() {
                useParallax([{ el: elA, speed: 0.5 }]);
                return { elA };
            },
            // No <section> ancestor at all -> sectionEl must fall back to parentElement (the wrapping <div>).
            template: '<div><span ref="elA"></span></div>',
        }), { attachTo: document.body });

        // scrollProgress = 40 -> translate3d(0, 20px, 0); this only happens if
        // the fallback parentElement was actually picked up as sectionEl.
        expect(elA.value.style.transform).toBe('translate3d(0, 20px, 0)');
        wrapper.unmount();
    });

    it('does not throw when a non-primary layer element ref is null, and still updates the other layers', () => {
        // sectionEl is derived only from layers[0].el, so keep that one bound
        // and leave the second layer's ref null to exercise the `if (el.value)` guard in update().
        rectSpy.mockReturnValue({ top: -100, bottom: 500, height: 600 });
        const elA = ref(null);
        const elB = ref(null); // never bound
        const wrapper = mount(defineComponent({
            setup() {
                useParallax([
                    { el: elA, speed: 0.3 },
                    { el: elB, speed: 0.6 },
                ]);
                return { elA };
            },
            template: '<section><div ref="elA"></div></section>',
        }), { attachTo: document.body });

        expect(elA.value.style.transform).toBe('translate3d(0, 30px, 0)');
        wrapper.unmount();
    });

    it('does nothing at all (no layer updates) when layers[0].el has no value at mount time', () => {
        // sectionEl is computed once from layers[0].el.value; if that ref is
        // still null when onMounted runs, sectionEl resolves to undefined and
        // update() short-circuits forever — even for layers with a real el.
        rectSpy.mockReturnValue({ top: -100, bottom: 500, height: 600 });
        const elA = ref(null); // layers[0] — deliberately unbound
        const elB = ref(null);
        const wrapper = mount(defineComponent({
            setup() {
                useParallax([
                    { el: elA, speed: 0.3 },
                    { el: elB, speed: 0.6 },
                ]);
                return { elB };
            },
            template: '<section><div ref="elB"></div></section>',
        }), { attachTo: document.body });

        expect(elB.value.style.transform).toBe('');
        wrapper.unmount();
    });
});
