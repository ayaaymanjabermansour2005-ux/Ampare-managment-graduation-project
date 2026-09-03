import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

const mockRoute = { name: 'landing.home' };
const pushMock = vi.fn().mockResolvedValue(undefined);
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    useRoute: () => mockRoute,
}));

const { useScrollToSection } = await import('./useScrollToSection');

describe('useScrollToSection', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockRoute.name = 'landing.home';
        document.body.innerHTML = '';
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('when already on landing.home, scrolls to the element immediately without touching the router', async () => {
        const section = document.createElement('div');
        section.id = 'pricing';
        section.scrollIntoView = vi.fn();
        document.body.appendChild(section);

        const { scrollToSection } = useScrollToSection();
        await scrollToSection('pricing');

        expect(section.scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'start' });
        expect(pushMock).not.toHaveBeenCalled();
    });

    it('does not throw when the target section element does not exist in the DOM', async () => {
        const { scrollToSection } = useScrollToSection();

        await expect(scrollToSection('does-not-exist')).resolves.toBeUndefined();
    });

    it('when on another route, navigates to landing.home first, then scrolls after a 150ms delay', async () => {
        vi.useFakeTimers();
        mockRoute.name = 'admin.dashboard';
        const section = document.createElement('div');
        section.id = 'contact';
        section.scrollIntoView = vi.fn();
        document.body.appendChild(section);

        const { scrollToSection } = useScrollToSection();
        const done = scrollToSection('contact');
        await vi.advanceTimersByTimeAsync(0); // let the router.push()/nextTick() awaits settle

        expect(pushMock).toHaveBeenCalledWith({ name: 'landing.home' });
        expect(section.scrollIntoView).not.toHaveBeenCalled(); // still waiting on the 150ms setTimeout

        await vi.advanceTimersByTimeAsync(150);
        await done;

        expect(section.scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'start' });
    });

    it('on another route, does not scroll before the 150ms grace period elapses', async () => {
        vi.useFakeTimers();
        mockRoute.name = 'admin.dashboard';
        const section = document.createElement('div');
        section.id = 'contact';
        section.scrollIntoView = vi.fn();
        document.body.appendChild(section);

        const { scrollToSection } = useScrollToSection();
        scrollToSection('contact');
        await vi.advanceTimersByTimeAsync(149);

        expect(section.scrollIntoView).not.toHaveBeenCalled();
    });
});
