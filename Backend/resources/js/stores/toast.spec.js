import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const { useToastStore } = await import('./toast');

describe('useToastStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('starts with an empty toasts list', () => {
        const store = useToastStore();
        expect(store.toasts).toEqual([]);
    });

    it('show() pushes a toast with the given title/message/type and defaults type to "info"', () => {
        const store = useToastStore();
        store.show({ title: 'Saved', message: 'All good.' });

        expect(store.toasts).toHaveLength(1);
        expect(store.toasts[0]).toMatchObject({ title: 'Saved', message: 'All good.', type: 'info' });
        expect(store.toasts[0].id).toBeDefined();
    });

    it('show() honors an explicit type', () => {
        const store = useToastStore();
        store.show({ message: 'Broke.', type: 'danger' });

        expect(store.toasts[0].type).toBe('danger');
    });

    it('show() returns the new toast id, and each call gets a distinct id', () => {
        const store = useToastStore();
        const id1 = store.show({ message: 'One' });
        const id2 = store.show({ message: 'Two' });

        expect(id1).not.toBe(id2);
        expect(store.toasts.map((t) => t.id)).toEqual([id1, id2]);
    });

    it('dismiss(id) removes only the matching toast', () => {
        const store = useToastStore();
        const id1 = store.show({ message: 'One', duration: 0 });
        const id2 = store.show({ message: 'Two', duration: 0 });

        store.dismiss(id1);

        expect(store.toasts).toHaveLength(1);
        expect(store.toasts[0].id).toBe(id2);
    });

    it('dismiss() with an unknown id is a no-op', () => {
        const store = useToastStore();
        store.show({ message: 'One', duration: 0 });

        store.dismiss(999999);

        expect(store.toasts).toHaveLength(1);
    });

    describe('auto-dismiss timer', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('auto-dismisses a toast after its duration elapses', () => {
            const store = useToastStore();
            store.show({ message: 'Times out', duration: 6000 });

            expect(store.toasts).toHaveLength(1);

            vi.advanceTimersByTime(5999);
            expect(store.toasts).toHaveLength(1);

            vi.advanceTimersByTime(1);
            expect(store.toasts).toHaveLength(0);
        });

        it('uses the 6000ms default duration when none is passed', () => {
            const store = useToastStore();
            store.show({ message: 'Default duration' });

            vi.advanceTimersByTime(5999);
            expect(store.toasts).toHaveLength(1);

            vi.advanceTimersByTime(1);
            expect(store.toasts).toHaveLength(0);
        });

        it('does not schedule an auto-dismiss when duration is 0 (persistent toast)', () => {
            const store = useToastStore();
            store.show({ message: 'Stays', duration: 0 });

            vi.advanceTimersByTime(60000);
            expect(store.toasts).toHaveLength(1);
        });

        it('each toast dismisses independently on its own timer', () => {
            const store = useToastStore();
            const id1 = store.show({ message: 'First', duration: 1000 });
            const id2 = store.show({ message: 'Second', duration: 5000 });

            vi.advanceTimersByTime(1000);
            expect(store.toasts.map((t) => t.id)).toEqual([id2]);

            vi.advanceTimersByTime(4000);
            expect(store.toasts).toHaveLength(0);
            void id1;
        });
    });
});
