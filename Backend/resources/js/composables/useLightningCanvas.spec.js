import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { useLightningCanvas } from './useLightningCanvas';

// jsdom has no real 2D canvas backend, so getContext('2d') returns null by
// default — stub it with just enough of the CanvasRenderingContext2D surface
// this composable actually calls (mirrors LightningCanvas.spec.js).
function stubCanvasContext() {
    const ctx = {
        save: vi.fn(), restore: vi.fn(), beginPath: vi.fn(),
        moveTo: vi.fn(), lineTo: vi.fn(), stroke: vi.fn(), clearRect: vi.fn(),
    };
    vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(ctx);
    return ctx;
}

function mountLightning(options) {
    const wrapper = mount(defineComponent({
        setup() {
            const canvasEl = ref(null);
            useLightningCanvas(canvasEl, options);
            return { canvasEl };
        },
        template: '<canvas ref="canvasEl"></canvas>',
    }), { attachTo: document.body });
    return wrapper;
}

describe('useLightningCanvas', () => {
    let rafSpy;
    let cancelRafSpy;

    beforeEach(() => {
        rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockReturnValue(1);
        cancelRafSpy = vi.spyOn(window, 'cancelAnimationFrame').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('gets a 2d context, sizes the canvas from its parent, and starts the animation loop on mount', () => {
        stubCanvasContext();
        // @vue/test-utils' attachTo mounts into its own intermediate wrapper
        // div (not the container element itself), so stub the size at the
        // prototype level to reach whatever element resize() reads from.
        vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(640);
        vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(480);

        const wrapper = mountLightning();

        expect(HTMLCanvasElement.prototype.getContext).toHaveBeenCalledWith('2d');
        expect(wrapper.element.width).toBe(640);
        expect(wrapper.element.height).toBe(480);
        expect(rafSpy).toHaveBeenCalled();

        wrapper.unmount();
    });

    it('registers a window resize listener on mount, and removes that exact listener and cancels the rAF on unmount', () => {
        stubCanvasContext();
        const addSpy = vi.spyOn(window, 'addEventListener');
        const removeSpy = vi.spyOn(window, 'removeEventListener');
        const wrapper = mountLightning();

        expect(addSpy).toHaveBeenCalledWith('resize', expect.any(Function));
        const registeredHandler = addSpy.mock.calls.find(([type]) => type === 'resize')[1];

        wrapper.unmount();

        expect(removeSpy).toHaveBeenCalledWith('resize', registeredHandler);
        expect(cancelRafSpy).toHaveBeenCalledWith(1);
    });

    it('draw() uses the dark palette (shadowColor/shadowBlur) when options.isDark() returns true', () => {
        const ctx = stubCanvasContext();
        const isDark = vi.fn(() => true);
        const wrapper = mountLightning({ isDark });

        expect(isDark).toHaveBeenCalled();
        expect(ctx.shadowColor).toBe('#D4AF37');
        expect(ctx.shadowBlur).toBe(16);

        wrapper.unmount();
    });

    it('draw() uses the light palette (shadowColor/shadowBlur) by default (no isDark option)', () => {
        const ctx = stubCanvasContext();
        const wrapper = mountLightning();

        expect(ctx.shadowColor).toBe('#3E582E');
        expect(ctx.shadowBlur).toBe(9);

        wrapper.unmount();
    });

    it('is a no-op (no context, no rAF) and does not throw on unmount when the canvas ref is null at mount time', () => {
        stubCanvasContext();
        const wrapper = mount(defineComponent({
            setup() {
                const canvasEl = ref(null); // deliberately never bound to a real <canvas>
                useLightningCanvas(canvasEl, {});
                return () => null;
            },
        }));

        expect(rafSpy).not.toHaveBeenCalled();
        expect(() => wrapper.unmount()).not.toThrow();
        expect(cancelRafSpy).not.toHaveBeenCalled();
    });
});
