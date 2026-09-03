import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/aiChatService', () => ({
    default: {
        availableGenerators: vi.fn(),
        list: vi.fn(),
        show: vi.fn(),
        startSession: vi.fn(),
        sendMessage: vi.fn(),
        submitAsPrediction: vi.fn(),
        submitAsFaultReport: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_ai_chat: {
                generators_load_error: 'تعذر تحميل المولدات',
                sessions_load_error: 'تعذر تحميل المحادثات',
                session_load_error: 'تعذر تحميل هذه المحادثة',
                start_error: 'تعذر بدء المحادثة',
                send_error: 'تعذر إرسال الرسالة',
                prediction_error: 'تعذر إرسال التوقع',
                fault_report_error: 'تعذر إرسال بلاغ العطل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAiChat } = await import('./useAiChat');
const aiChatService = (await import('@/services/aiChatService')).default;

describe('useAiChat', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with isLoadingGenerators true, everything else empty/idle', () => {
        const api = useAiChat();

        expect(api.generators.value).toEqual([]);
        expect(api.isLoadingGenerators.value).toBe(true);
        expect(api.generatorsError.value).toBeNull();
        expect(api.sessions.value).toEqual([]);
        expect(api.isLoadingSessions.value).toBe(false);
        expect(api.sessionsError.value).toBeNull();
        expect(api.activeSession.value).toBeNull();
        expect(api.messages.value).toEqual([]);
        expect(api.isLoadingSession.value).toBe(false);
        expect(api.isStarting.value).toBe(false);
        expect(api.startError.value).toBeNull();
        expect(api.isSending.value).toBe(false);
        expect(api.sendError.value).toBeNull();
        expect(api.isSubmittingPrediction.value).toBe(false);
        expect(api.predictionError.value).toBeNull();
        expect(api.isSubmittingFaultReport.value).toBe(false);
        expect(api.faultReportError.value).toBeNull();
    });

    it('loadGenerators populates generators and clears isLoadingGenerators on success', async () => {
        aiChatService.availableGenerators.mockResolvedValue({ data: { data: [{ id: 1, name: 'Gen A' }] } });

        const { loadGenerators, generators, isLoadingGenerators, generatorsError } = useAiChat();
        await loadGenerators();

        expect(generators.value).toEqual([{ id: 1, name: 'Gen A' }]);
        expect(isLoadingGenerators.value).toBe(false);
        expect(generatorsError.value).toBeNull();
    });

    it('reshapes a loadGenerators failure into the translated fallback message', async () => {
        aiChatService.availableGenerators.mockRejectedValue({ message: 'Network Error' });

        const { loadGenerators, generatorsError, isLoadingGenerators } = useAiChat();
        await loadGenerators();

        expect(generatorsError.value).toBe('تعذر تحميل المولدات');
        expect(isLoadingGenerators.value).toBe(false);
    });

    it('loadSessions unwraps a paginated payload on success', async () => {
        aiChatService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, title: 'محادثة' }], meta: { total: 1 } } },
        });

        const { loadSessions, sessions, isLoadingSessions } = useAiChat();
        await loadSessions();

        expect(aiChatService.list).toHaveBeenCalledWith({ per_page: 20 });
        expect(sessions.value).toEqual([{ id: 1, title: 'محادثة' }]);
        expect(isLoadingSessions.value).toBe(false);
    });

    it('reshapes a loadSessions failure into the translated fallback message', async () => {
        aiChatService.list.mockRejectedValue({ message: 'Network Error' });

        const { loadSessions, sessionsError } = useAiChat();
        await loadSessions();

        expect(sessionsError.value).toBe('تعذر تحميل المحادثات');
    });

    it('openSession sets activeSession and messages on success', async () => {
        aiChatService.show.mockResolvedValue({
            data: { data: { id: 5, messages: [{ id: 1, role: 'user', content: 'مرحبا' }] } },
        });

        const { openSession, activeSession, messages, isLoadingSession } = useAiChat();
        await openSession(5);

        expect(aiChatService.show).toHaveBeenCalledWith(5);
        expect(activeSession.value).toEqual({ id: 5, messages: [{ id: 1, role: 'user', content: 'مرحبا' }] });
        expect(messages.value).toEqual([{ id: 1, role: 'user', content: 'مرحبا' }]);
        expect(isLoadingSession.value).toBe(false);
    });

    it('openSession defaults messages to [] when the session payload has none', async () => {
        aiChatService.show.mockResolvedValue({ data: { data: { id: 5 } } });

        const { openSession, messages } = useAiChat();
        await openSession(5);

        expect(messages.value).toEqual([]);
    });

    it('openSession sets a translated sessionError via normalizeApiError and clears isLoadingSession on failure', async () => {
        aiChatService.show.mockRejectedValue({ message: 'Network Error' });

        const { openSession, sessionError, isLoadingSession } = useAiChat();

        await expect(openSession(5)).resolves.toBeUndefined();
        expect(sessionError.value).toBe('تعذر تحميل هذه المحادثة');
        expect(isLoadingSession.value).toBe(false);
    });

    it('clears a previous sessionError at the start of a new openSession attempt', async () => {
        aiChatService.show.mockRejectedValueOnce({ message: 'Network Error' });
        const { openSession, sessionError } = useAiChat();
        await openSession(5);
        expect(sessionError.value).toBe('تعذر تحميل هذه المحادثة');

        aiChatService.show.mockResolvedValueOnce({ data: { data: { id: 5 } } });
        await openSession(5);

        expect(sessionError.value).toBeNull();
    });

    it('startSession sets the active session, prepends it to sessions, and returns true on success', async () => {
        aiChatService.startSession.mockResolvedValue({
            data: { data: { id: 9, messages: [{ id: 1, role: 'assistant', content: 'أهلاً' }] } },
        });

        const { startSession, activeSession, messages, sessions, isStarting } = useAiChat();
        const result = await startSession(3, 'المولد لا يعمل', null);

        expect(aiChatService.startSession).toHaveBeenCalledWith(3, 'المولد لا يعمل', null);
        expect(result).toBe(true);
        expect(activeSession.value.id).toBe(9);
        expect(messages.value).toEqual([{ id: 1, role: 'assistant', content: 'أهلاً' }]);
        expect(sessions.value[0].id).toBe(9);
        expect(isStarting.value).toBe(false);
    });

    it('reshapes a startSession failure into startError and returns false', async () => {
        aiChatService.startSession.mockRejectedValue({ message: 'Network Error' });

        const { startSession, startError, isStarting } = useAiChat();
        const result = await startSession(3, 'نص', null);

        expect(result).toBe(false);
        expect(startError.value).toBe('تعذر بدء المحادثة');
        expect(isStarting.value).toBe(false);
    });

    it('sendMessage is a no-op returning false when there is no active session', async () => {
        const { sendMessage, isSending } = useAiChat();
        const result = await sendMessage('نص', null);

        expect(result).toBe(false);
        expect(aiChatService.sendMessage).not.toHaveBeenCalled();
        expect(isSending.value).toBe(false);
    });

    it('sendMessage optimistically pushes the user message, then appends the server response on success', async () => {
        aiChatService.startSession.mockResolvedValue({ data: { data: { id: 9, messages: [] } } });
        aiChatService.sendMessage.mockResolvedValue({ data: { data: { id: 2, role: 'assistant', content: 'رد الذكاء الاصطناعي' } } });

        const { startSession, sendMessage, messages } = useAiChat();
        await startSession(3, 'نص أولي', null);

        const result = await sendMessage('سؤال المستخدم', null);

        expect(result).toBe(true);
        expect(messages.value).toHaveLength(2);
        expect(messages.value[0]).toMatchObject({ role: 'user', content: 'سؤال المستخدم' });
        expect(messages.value[1]).toEqual({ id: 2, role: 'assistant', content: 'رد الذكاء الاصطناعي' });
    });

    it('sendMessage removes the optimistic message and sets sendError when the request fails', async () => {
        aiChatService.startSession.mockResolvedValue({ data: { data: { id: 9, messages: [] } } });
        aiChatService.sendMessage.mockRejectedValue({ message: 'Network Error' });

        const { startSession, sendMessage, messages, sendError } = useAiChat();
        await startSession(3, 'نص أولي', null);

        const result = await sendMessage('سؤال المستخدم', null);

        expect(result).toBe(false);
        expect(messages.value).toEqual([]); // optimistic message rolled back
        expect(sendError.value).toBe('تعذر إرسال الرسالة');
    });

    it('submitAsPrediction is a no-op returning false without an active session', async () => {
        const { submitAsPrediction } = useAiChat();
        const result = await submitAsPrediction();

        expect(result).toBe(false);
        expect(aiChatService.submitAsPrediction).not.toHaveBeenCalled();
    });

    it('submitAsPrediction returns true on success and reshapes a failure into predictionError', async () => {
        aiChatService.startSession.mockResolvedValue({ data: { data: { id: 9 } } });
        aiChatService.submitAsPrediction.mockResolvedValueOnce({ data: {} }).mockRejectedValueOnce({ message: 'Network Error' });

        const { startSession, submitAsPrediction, predictionError } = useAiChat();
        await startSession(3, 'نص', null);

        expect(await submitAsPrediction()).toBe(true);
        expect(aiChatService.submitAsPrediction).toHaveBeenCalledWith(9);

        expect(await submitAsPrediction()).toBe(false);
        expect(predictionError.value).toBe('تعذر إرسال التوقع');
    });

    it('submitAsFaultReport is a no-op returning false without an active session', async () => {
        const { submitAsFaultReport } = useAiChat();
        const result = await submitAsFaultReport();

        expect(result).toBe(false);
        expect(aiChatService.submitAsFaultReport).not.toHaveBeenCalled();
    });

    it('submitAsFaultReport reshapes a failure into faultReportError when an active session exists', async () => {
        aiChatService.startSession.mockResolvedValue({ data: { data: { id: 9 } } });
        aiChatService.submitAsFaultReport.mockRejectedValue({ message: 'Network Error' });

        const { startSession, submitAsFaultReport, faultReportError, isSubmittingFaultReport } = useAiChat();
        await startSession(3, 'نص', null);
        const result = await submitAsFaultReport();

        expect(aiChatService.submitAsFaultReport).toHaveBeenCalledWith(9);
        expect(result).toBe(false);
        expect(faultReportError.value).toBe('تعذر إرسال بلاغ العطل');
        expect(isSubmittingFaultReport.value).toBe(false);
    });
});
