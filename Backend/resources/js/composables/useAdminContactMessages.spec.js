import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/contactMessageService', () => ({
    default: {
        list: vi.fn(),
        updateStatus: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            contact_messages_page: {
                load_error: 'تعذر تحميل الرسائل',
                update_error: 'تعذر تحديث الرسالة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminContactMessages } = await import('./useAdminContactMessages');
const contactMessageService = (await import('@/services/contactMessageService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 15, ...meta } } },
    };
}

describe('useAdminContactMessages', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state', () => {
        const { messages, pagination, isLoading, error, searchTerm, statusFilter, isUpdating, updateError } = useAdminContactMessages();

        expect(messages.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(searchTerm.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(isUpdating.value).toBe(false);
        expect(updateError.value).toBeNull();
    });

    it('fetchMessages populates messages and pagination on success', async () => {
        contactMessageService.list.mockResolvedValue(samplePage([{ id: 1 }, { id: 2 }], { total: 2 }));

        const { fetchMessages, messages, pagination, isLoading } = useAdminContactMessages();
        const promise = fetchMessages(1);
        expect(isLoading.value).toBe(true);
        await promise;

        expect(messages.value).toEqual([{ id: 1 }, { id: 2 }]);
        expect(pagination.value.total).toBe(2);
        expect(isLoading.value).toBe(false);
    });

    it('fetchMessages handles an empty result list', async () => {
        contactMessageService.list.mockResolvedValue(samplePage([], { total: 0 }));
        const { fetchMessages, messages } = useAdminContactMessages();

        await fetchMessages();

        expect(messages.value).toEqual([]);
    });

    it('fetchMessages sends search/status only when set', async () => {
        contactMessageService.list.mockResolvedValue(samplePage([]));
        const { fetchMessages, searchTerm, statusFilter } = useAdminContactMessages();

        await fetchMessages(3);
        expect(contactMessageService.list).toHaveBeenCalledWith({ page: 3, search: undefined, status: undefined });

        searchTerm.value = 'hello';
        statusFilter.value = 'new';
        await fetchMessages(1);
        expect(contactMessageService.list).toHaveBeenCalledWith({ page: 1, search: 'hello', status: 'new' });
    });

    it('fetchMessages sets a translated error message via normalizeApiError on network failure', async () => {
        contactMessageService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchMessages, error } = useAdminContactMessages();

        await fetchMessages();

        expect(error.value).toBe('تعذر تحميل الرسائل');
    });

    it('onFilterChange refetches page 1', () => {
        contactMessageService.list.mockResolvedValue(samplePage([]));
        const { onFilterChange } = useAdminContactMessages();

        onFilterChange();

        expect(contactMessageService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
    });

    it('updateStatus replaces the message in the list and returns the updated record on success', async () => {
        contactMessageService.updateStatus.mockResolvedValue({ data: { data: { id: 1, status: 'read', admin_note: 'ok' } } });
        const { updateStatus, messages } = useAdminContactMessages();
        messages.value = [{ id: 1, status: 'new' }];

        const result = await updateStatus(1, 'read', 'ok');

        expect(contactMessageService.updateStatus).toHaveBeenCalledWith(1, { status: 'read', admin_note: 'ok' });
        expect(result).toEqual({ id: 1, status: 'read', admin_note: 'ok' });
        expect(messages.value[0]).toEqual({ id: 1, status: 'read', admin_note: 'ok' });
    });

    it('updateStatus sends admin_note as null when not provided (falsy admin note)', async () => {
        contactMessageService.updateStatus.mockResolvedValue({ data: { data: { id: 1, status: 'read' } } });
        const { updateStatus } = useAdminContactMessages();

        await updateStatus(1, 'read');

        expect(contactMessageService.updateStatus).toHaveBeenCalledWith(1, { status: 'read', admin_note: null });
    });

    it('updateStatus sets updateError via normalizeApiError and returns null on failure', async () => {
        contactMessageService.updateStatus.mockRejectedValue({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: {} } },
        });
        const { updateStatus, updateError, isUpdating } = useAdminContactMessages();

        const result = await updateStatus(1, 'read');

        expect(result).toBeNull();
        expect(updateError.value).toBe('بيانات غير صالحة');
        expect(isUpdating.value).toBe(false);
    });

    it('updateStatus falls back to the translated message on a network error', async () => {
        contactMessageService.updateStatus.mockRejectedValue({ message: 'Network Error' });
        const { updateStatus, updateError } = useAdminContactMessages();

        await updateStatus(1, 'read');

        expect(updateError.value).toBe('تعذر تحديث الرسالة');
    });
});
