import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/services/conversationService', () => ({
    default: {
        list: vi.fn(),
        messages: vi.fn(),
        sendMessage: vi.fn(),
        start: vi.fn(),
        startSupport: vi.fn(),
        startWithOwner: vi.fn(),
        convertToIssue: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_messages: {
                load_error: 'تعذر تحميل المحادثات',
                messages_load_error: 'تعذر تحميل رسائل هذه المحادثة',
                send_error: 'تعذر إرسال الرسالة',
                start_error: 'تعذر بدء المحادثة',
            },
            messages_page: {
                convert_error: 'تعذر تحويل المحادثة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useConversations } = await import('./useConversations');
const conversationService = (await import('@/services/conversationService')).default;
const { useAuthStore } = await import('@/stores/auth');

describe('useConversations', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('starts with isLoading true, empty conversations, no active conversation, and currentUserId null when logged out', () => {
        const api = useConversations();

        expect(api.conversations.value).toEqual([]);
        expect(api.isLoading.value).toBe(true);
        expect(api.error.value).toBeNull();
        expect(api.activeConversation.value).toBeNull();
        expect(api.messages.value).toEqual([]);
        expect(api.isLoadingMessages.value).toBe(false);
        expect(api.isSending.value).toBe(false);
        expect(api.sendError.value).toBeNull();
        expect(api.isStarting.value).toBe(false);
        expect(api.isStartingSupport.value).toBe(false);
        expect(api.isStartingWithOwner.value).toBe(false);
        expect(api.isConverting.value).toBe(false);
        expect(api.convertError.value).toBeNull();
        expect(api.currentUserId).toBeUndefined();
    });

    it('exposes the current authenticated user id', () => {
        useAuthStore().user = { id: 42 };

        const { currentUserId } = useConversations();

        expect(currentUserId).toBe(42);
    });

    it('fetchConversations unwraps the paginated payload and clears isLoading on success', async () => {
        conversationService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1 }, { id: 2 }], meta: { total: 2 } } },
        });

        const { fetchConversations, conversations, isLoading } = useConversations();
        await fetchConversations();

        expect(conversationService.list).toHaveBeenCalledWith({ per_page: 30 });
        expect(conversations.value).toEqual([{ id: 1 }, { id: 2 }]);
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a fetchConversations failure into the translated fallback message', async () => {
        conversationService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchConversations, error, isLoading } = useConversations();
        await fetchConversations();

        expect(error.value).toBe('تعذر تحميل المحادثات');
        expect(isLoading.value).toBe(false);
    });

    it('openConversation sets the active conversation immediately and loads messages in reverse order', async () => {
        conversationService.messages.mockResolvedValue({
            data: { data: { data: [{ id: 3, text: 'c' }, { id: 2, text: 'b' }, { id: 1, text: 'a' }] } },
        });

        const { openConversation, activeConversation, messages, isLoadingMessages } = useConversations();
        const conversation = { id: 7 };
        await openConversation(conversation);

        expect(conversationService.messages).toHaveBeenCalledWith(7, { per_page: 50 });
        // activeConversation is a ref, so Vue auto-wraps the assigned object in a
        // reactive proxy — compare structurally (toEqual), not by reference (toBe).
        expect(activeConversation.value).toEqual(conversation);
        expect(messages.value).toEqual([{ id: 1, text: 'a' }, { id: 2, text: 'b' }, { id: 3, text: 'c' }]);
        expect(isLoadingMessages.value).toBe(false);
    });

    it('openConversation sets a translated messagesError, clears stale messages, and does not reject on failure', async () => {
        conversationService.messages.mockRejectedValue({ message: 'Network Error' });

        const { openConversation, activeConversation, messages, messagesError, isLoadingMessages } = useConversations();
        const conversation = { id: 7 };

        await expect(openConversation(conversation)).resolves.toBeUndefined();
        expect(activeConversation.value).toEqual(conversation);
        expect(messages.value).toEqual([]);
        expect(messagesError.value).toBe('تعذر تحميل رسائل هذه المحادثة');
        expect(isLoadingMessages.value).toBe(false);
    });

    it('clears a previous messagesError and stale messages on the next successful openConversation', async () => {
        conversationService.messages.mockRejectedValueOnce({ message: 'Network Error' });
        const { openConversation, messages, messagesError } = useConversations();
        await openConversation({ id: 7 });
        expect(messagesError.value).toBe('تعذر تحميل رسائل هذه المحادثة');

        conversationService.messages.mockResolvedValueOnce({ data: { data: { data: [{ id: 1, text: 'a' }] } } });
        await openConversation({ id: 8 });

        expect(messagesError.value).toBeNull();
        expect(messages.value).toEqual([{ id: 1, text: 'a' }]);
    });

    it('sendMessage is a no-op returning false when there is no active conversation', async () => {
        const { sendMessage } = useConversations();
        const result = await sendMessage('نص');

        expect(result).toBe(false);
        expect(conversationService.sendMessage).not.toHaveBeenCalled();
    });

    it('sendMessage appends the response message and returns true on success', async () => {
        conversationService.messages.mockResolvedValue({ data: { data: [] } });
        conversationService.sendMessage.mockResolvedValue({ data: { data: { id: 1, text: 'مرحبا' } } });

        const { openConversation, sendMessage, messages, isSending } = useConversations();
        await openConversation({ id: 7 });
        const result = await sendMessage('مرحبا');

        expect(conversationService.sendMessage).toHaveBeenCalledWith(7, 'مرحبا');
        expect(result).toBe(true);
        expect(messages.value).toEqual([{ id: 1, text: 'مرحبا' }]);
        expect(isSending.value).toBe(false);
    });

    it('sendMessage reshapes a failure into sendError and returns false', async () => {
        conversationService.messages.mockResolvedValue({ data: { data: [] } });
        conversationService.sendMessage.mockRejectedValue({ message: 'Network Error' });

        const { openConversation, sendMessage, sendError } = useConversations();
        await openConversation({ id: 7 });
        const result = await sendMessage('نص');

        expect(result).toBe(false);
        expect(sendError.value).toBe('تعذر إرسال الرسالة');
    });

    it('startConversation prepends the new conversation, opens it, and returns true', async () => {
        conversationService.start.mockResolvedValue({ data: { data: { id: 10 } } });
        conversationService.messages.mockResolvedValue({ data: { data: [] } });

        const { startConversation, conversations, activeConversation } = useConversations();
        const result = await startConversation(99);

        expect(conversationService.start).toHaveBeenCalledWith(99);
        expect(result).toBe(true);
        expect(conversations.value[0]).toEqual({ id: 10 });
        expect(activeConversation.value).toEqual({ id: 10 });
    });

    it('startConversation does not duplicate a conversation that is already in the list', async () => {
        conversationService.list.mockResolvedValue({ data: { data: [{ id: 10, title: 'موجودة' }] } });
        conversationService.start.mockResolvedValue({ data: { data: { id: 10 } } });
        conversationService.messages.mockResolvedValue({ data: { data: [] } });

        const { fetchConversations, startConversation, conversations } = useConversations();
        await fetchConversations();
        await startConversation(99);

        expect(conversations.value).toHaveLength(1);
        expect(conversations.value[0]).toEqual({ id: 10, title: 'موجودة' });
    });

    it('startConversation reshapes a failure into startError and returns false', async () => {
        conversationService.start.mockRejectedValue({ message: 'Network Error' });

        const { startConversation, startError, isStarting } = useConversations();
        const result = await startConversation(99);

        expect(result).toBe(false);
        expect(startError.value).toBe('تعذر بدء المحادثة');
        expect(isStarting.value).toBe(false);
    });

    it('startSupportConversation prepends/opens the support conversation and returns true', async () => {
        conversationService.startSupport.mockResolvedValue({ data: { data: { id: 11 } } });
        conversationService.messages.mockResolvedValue({ data: { data: [] } });

        const { startSupportConversation, conversations, activeConversation, isStartingSupport } = useConversations();
        const result = await startSupportConversation();

        expect(result).toBe(true);
        expect(conversations.value[0]).toEqual({ id: 11 });
        expect(activeConversation.value).toEqual({ id: 11 });
        expect(isStartingSupport.value).toBe(false);
    });

    it('startSupportConversation reshapes a failure into startSupportError', async () => {
        conversationService.startSupport.mockRejectedValue({ message: 'Network Error' });

        const { startSupportConversation, startSupportError } = useConversations();
        const result = await startSupportConversation();

        expect(result).toBe(false);
        expect(startSupportError.value).toBe('تعذر بدء المحادثة');
    });

    it('startWithOwnerConversation prepends/opens the owner conversation and returns true', async () => {
        conversationService.startWithOwner.mockResolvedValue({ data: { data: { id: 12 } } });
        conversationService.messages.mockResolvedValue({ data: { data: [] } });

        const { startWithOwnerConversation, conversations, activeConversation, isStartingWithOwner } = useConversations();
        const result = await startWithOwnerConversation();

        expect(result).toBe(true);
        expect(conversations.value[0]).toEqual({ id: 12 });
        expect(activeConversation.value).toEqual({ id: 12 });
        expect(isStartingWithOwner.value).toBe(false);
    });

    it('startWithOwnerConversation reshapes a failure into startWithOwnerError', async () => {
        conversationService.startWithOwner.mockRejectedValue({ message: 'Network Error' });

        const { startWithOwnerConversation, startWithOwnerError } = useConversations();
        const result = await startWithOwnerConversation();

        expect(result).toBe(false);
        expect(startWithOwnerError.value).toBe('تعذر بدء المحادثة');
    });

    it('convertToIssue is a no-op returning false when there is no active conversation', async () => {
        const { convertToIssue } = useConversations();
        const result = await convertToIssue('fault', 5);

        expect(result).toBe(false);
        expect(conversationService.convertToIssue).not.toHaveBeenCalled();
    });

    it('convertToIssue sends generator_id as undefined when not provided, and returns the raw response data (not a boolean) on success', async () => {
        conversationService.messages.mockResolvedValue({ data: { data: [] } });
        conversationService.convertToIssue.mockResolvedValue({ data: { data: { id: 20, category: 'fault' } } });

        const { openConversation, convertToIssue, isConverting } = useConversations();
        await openConversation({ id: 7 });
        const result = await convertToIssue('fault');

        expect(conversationService.convertToIssue).toHaveBeenCalledWith(7, { category: 'fault', generator_id: undefined });
        expect(result).toEqual({ id: 20, category: 'fault' });
        expect(isConverting.value).toBe(false);
    });

    it('convertToIssue forwards an explicit generator_id and reshapes a failure into convertError', async () => {
        conversationService.messages.mockResolvedValue({ data: { data: [] } });
        conversationService.convertToIssue.mockRejectedValue({ message: 'Network Error' });

        const { openConversation, convertToIssue, convertError } = useConversations();
        await openConversation({ id: 7 });
        const result = await convertToIssue('fault', 3);

        expect(conversationService.convertToIssue).toHaveBeenCalledWith(7, { category: 'fault', generator_id: 3 });
        expect(result).toBe(false);
        expect(convertError.value).toBe('تعذر تحويل المحادثة');
    });
});
