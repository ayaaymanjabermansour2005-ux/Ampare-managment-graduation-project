import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import LightningCanvas from './LightningCanvas.vue';

// jsdom has no real 2D canvas backend (the `canvas` npm package isn't installed),
// so getContext('2d') returns null by default — stub it with just enough of the
// CanvasRenderingContext2D surface this component actually calls.
function stubCanvasContext() {
    const ctx = {
        save: vi.fn(), restore: vi.fn(), beginPath: vi.fn(),
        moveTo: vi.fn(), lineTo: vi.fn(), stroke: vi.fn(), clearRect: vi.fn(),
    };
    vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(ctx);
    return ctx;
}

describe('LightningCanvas', () => {
    let rafSpy;
    let cancelRafSpy;

    beforeEach(() => {
        stubCanvasContext();
        // Prevent an actual animation loop from running during the test.
        rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockReturnValue(1);
        cancelRafSpy = vi.spyOn(window, 'cancelAnimationFrame').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('mounts a canvas and starts the animation loop without throwing', () => {
        const wrapper = mount(LightningCanvas, { attachTo: document.body });

        expect(wrapper.find('canvas').exists()).toBe(true);
        expect(rafSpy).toHaveBeenCalled();

        wrapper.unmount();
    });

    it('removes the resize listener and cancels the animation frame on unmount', () => {
        const removeSpy = vi.spyOn(window, 'removeEventListener');
        const wrapper = mount(LightningCanvas, { attachTo: document.body });

        wrapper.unmount();

        expect(removeSpy).toHaveBeenCalledWith('resize', expect.any(Function));
        expect(cancelRafSpy).toHaveBeenCalledWith(1);
    });
});
