import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@/i18n', () => ({
    default: {
        global: {
            t: (key) => key,
        },
    },
}));

const { useConfirm, useConfirmDialogState } = await import('./useConfirm');

describe('useConfirm / useConfirmDialogState', () => {
    beforeEach(() => {
        // state is a module-level singleton shared by every caller — reset it
        // between tests so assertions don't leak across specs.
        const { state } = useConfirmDialogState();
        state.isOpen = false;
        state.title = '';
        state.message = '';
        state.confirmLabel = '';
        state.cancelLabel = '';
        state.variant = 'default';
        state.hideCancel = false;
    });

    it('confirm() opens the dialog and fills in translated defaults for omitted options', () => {
        const { confirm } = useConfirm();
        const { state } = useConfirmDialogState();

        confirm({ message: 'هل أنت متأكد؟' });

        expect(state.isOpen).toBe(true);
        expect(state.message).toBe('هل أنت متأكد؟');
        expect(state.title).toBe('common.confirm_action_title');
        expect(state.confirmLabel).toBe('common.confirm');
        expect(state.cancelLabel).toBe('common.cancel');
        expect(state.variant).toBe('default');
        expect(state.hideCancel).toBe(false);
    });

    it('confirm() uses explicitly provided title/labels/variant/hideCancel instead of the translated defaults', () => {
        const { confirm } = useConfirm();
        const { state } = useConfirmDialogState();

        confirm({
            title: 'حذف الفني',
            message: 'لن يمكن التراجع.',
            confirmLabel: 'حذف',
            cancelLabel: 'تراجع',
            variant: 'danger',
            hideCancel: true,
        });

        expect(state.title).toBe('حذف الفني');
        expect(state.confirmLabel).toBe('حذف');
        expect(state.cancelLabel).toBe('تراجع');
        expect(state.variant).toBe('danger');
        expect(state.hideCancel).toBe(true);
    });

    it('handleConfirm() closes the dialog and resolves the pending promise with true', async () => {
        const { confirm } = useConfirm();
        const { state, handleConfirm } = useConfirmDialogState();

        const pending = confirm({ message: 'متابعة؟' });
        handleConfirm();

        expect(state.isOpen).toBe(false);
        await expect(pending).resolves.toBe(true);
    });

    it('handleCancel() closes the dialog and resolves the pending promise with false', async () => {
        const { confirm } = useConfirm();
        const { state, handleCancel } = useConfirmDialogState();

        const pending = confirm({ message: 'متابعة؟' });
        handleCancel();

        expect(state.isOpen).toBe(false);
        await expect(pending).resolves.toBe(false);
    });

    it('a second confirm() call replaces the pending resolver — only the latest handleConfirm/handleCancel call settles it', async () => {
        const { confirm } = useConfirm();
        const { handleConfirm } = useConfirmDialogState();

        const first = confirm({ message: 'الأول' });
        const second = confirm({ message: 'الثاني' });
        handleConfirm();

        await expect(second).resolves.toBe(true);
        // the first promise is simply never settled once superseded — document that, don't invent a rejection.
        const race = await Promise.race([first, Promise.resolve('pending')]);
        expect(race).toBe('pending');
    });

    it('handleConfirm()/handleCancel() do not throw when there is no pending confirm() (resolvePromise is null)', () => {
        const { handleConfirm, handleCancel } = useConfirmDialogState();
        handleCancel(); // ensure any leftover resolver from a previous test is drained first

        expect(() => handleConfirm()).not.toThrow();
        expect(() => handleCancel()).not.toThrow();
    });
});
