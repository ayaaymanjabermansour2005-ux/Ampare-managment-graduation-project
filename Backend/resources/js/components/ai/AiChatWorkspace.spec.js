import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref } from 'vue';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import AiChatWorkspace from './AiChatWorkspace.vue';
import AppDropdownSelect from '@/components/ui/AppDropdownSelect.vue';

function makeChatState() {
    return {
        generators: ref([]),
        isLoadingGenerators: ref(false),
        generatorsError: ref(null),
        loadGenerators: vi.fn().mockResolvedValue(undefined),
        sessions: ref([]),
        isLoadingSessions: ref(false),
        sessionsError: ref(null),
        loadSessions: vi.fn().mockResolvedValue(undefined),
        activeSession: ref(null),
        messages: ref([]),
        isLoadingSession: ref(false),
        sessionError: ref(null),
        openSession: vi.fn().mockResolvedValue(undefined),
        isStarting: ref(false),
        startError: ref(null),
        startSession: vi.fn().mockResolvedValue(true),
        isSending: ref(false),
        sendError: ref(null),
        sendMessage: vi.fn().mockResolvedValue(true),
        isSubmittingPrediction: ref(false),
        predictionError: ref(null),
        submitAsPrediction: vi.fn().mockResolvedValue(true),
        isSubmittingFaultReport: ref(false),
        faultReportError: ref(null),
        submitAsFaultReport: vi.fn().mockResolvedValue(true),
    };
}

vi.mock('@/composables/useAiChat', () => ({ useAiChat: vi.fn() }));
const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({ useConfirm: () => ({ confirm: confirmMock }) }));

import { useAiChat } from '@/composables/useAiChat';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            subscribers_page: {
                time_now: 'الآن',
                time_mins_ago: 'قبل {mins} دقيقة',
                time_hours_ago: 'قبل {hours} ساعة',
                time_days_ago: 'قبل {days} يوم',
            },
            ai_chat_page: {
                no_generators_available: 'ما في مولدات متاحة للاستفسار عنها.',
                starting: 'جارٍ البدء...',
                start_conversation: 'ابدأ المحادثة',
                new_conversation: 'محادثة جديدة',
                type_message_placeholder: 'اكتب رسالتك...',
            },
            ai_chat_workspace: {
                default_title: 'المساعد الذكي',
                status_online: 'متصل وجاهز للمساعدة',
                new_chat_aria: 'محادثة جديدة',
                search_conversations_placeholder: 'البحث في المحادثات',
                retry: 'إعادة المحاولة',
                no_sessions_line1: 'لا توجد محادثات بعد.',
                no_sessions_line2: 'ابدأ محادثة جديدة.',
                no_matching_sessions: 'لا توجد نتائج مطابقة.',
                general_support_title: 'محادثة دعم عامة',
                default_session_title: 'محادثة دعم',
                back_to_conversations: 'العودة للمحادثات',
                composer_heading: 'كيف يمكنني مساعدتك؟',
                composer_subtitle_admin: 'ابدأ بكتابة سؤالك أو طلبك مباشرة.',
                composer_subtitle_generator: 'اختر المولد ثم ابدأ بشرح المشكلة بطريقتك.',
                issue_placeholder: 'اكتب وصف المشكلة أو سؤالك…',
                quick_prompt_generator_not_running: 'المولد لا يعمل بعد تشغيله',
                quick_prompt_abnormal_noise: 'هناك صوت غير طبيعي من المولد',
                quick_prompt_high_fuel_consumption: 'استهلاك الوقود أعلى من المعتاد',
                quick_prompt_voltage_drop: 'يوجد انخفاض في الجهد الكهربائي',
                record_prediction: 'تسجيل توقع',
                send_report: 'إرسال بلاغ',
                confirm_prediction_title: 'تسجيل كتوقع عطل؟',
                confirm_prediction_message: 'سيتم حفظ نتيجة المحادثة كتوقع عطل للمراجعة والمتابعة.',
                confirm_prediction_action: 'تسجيل',
                confirm_report_title: 'إرسال بلاغ عطل؟',
                confirm_report_message: 'سيتم تحويل هذه المحادثة إلى بلاغ عطل رسمي للمتابعة.',
                confirm_report_action: 'إرسال البلاغ',
                loading_conversation: 'جارٍ تحميل المحادثة…',
                disclaimer: 'قد يخطئ المساعد الذكي؛ تحقّق من المعلومات المهمة.',
                empty_state_heading: 'ابدأ محادثة جديدة',
                empty_state_subtitle: 'اختر محادثة سابقة أو اضغط زر الإنشاء.',
                attach_file_aria: 'إرفاق ملف',
                attach_camera_aria: 'التقاط صورة بالكاميرا',
                remove_attachment_aria: 'إزالة المرفق',
                attached_image_alt: 'معاينة الصورة المرفقة',
                attachment_too_large_error: 'حجم الملف كبير جدًا، الحد الأقصى 10 ميجابايت.',
                attachment_invalid_type_error: 'صيغة الملف غير مدعومة، يُسمح فقط بـ JPG وPNG وPDF.',
            },
        },
    },
});

let chat;

function mountWorkspace(props = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    return mount(AiChatWorkspace, {
        props: { mode: 'owner', ...props },
        global: { plugins: [i18n, pinia] },
    });
}

describe('AiChatWorkspace', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        chat = makeChatState();
        useAiChat.mockReturnValue(chat);
        if (!global.URL.createObjectURL) global.URL.createObjectURL = () => 'blob:fake';
        if (!global.URL.revokeObjectURL) global.URL.revokeObjectURL = () => {};
        vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:fake-preview');
        vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('shows the empty state when there is no active session and the composer is closed', () => {
        const wrapper = mountWorkspace();

        expect(wrapper.text()).toContain('ابدأ محادثة جديدة');
        expect(wrapper.text()).toContain('اختر محادثة سابقة أو اضغط زر الإنشاء.');
    });

    it('clicking "new conversation" from the empty state opens the composer', async () => {
        const wrapper = mountWorkspace();

        await wrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');

        expect(wrapper.text()).toContain('كيف يمكنني مساعدتك؟');
    });

    it('renders each session with its generator name and a relative timestamp', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-01-01T08:05:00'));
        chat.sessions.value = [
            { id: 1, generator: { name: 'مولد النور' }, created_at: '2024-01-01 08:00:00' },
        ];
        const wrapper = mountWorkspace();

        expect(wrapper.text()).toContain('مولد النور');
        expect(wrapper.text()).toContain('قبل 5 دقيقة');
    });

    it('filters the session list by generator name via the search box', async () => {
        chat.sessions.value = [
            { id: 1, generator: { name: 'مولد النور' }, created_at: '2024-01-01 08:00:00' },
            { id: 2, generator: { name: 'مولد الشمس' }, created_at: '2024-01-01 08:00:00' },
        ];
        const wrapper = mountWorkspace();

        await wrapper.find('input[placeholder="البحث في المحادثات"]').setValue('النور');

        const sessionButtons = wrapper.findAll('aside button.w-full.p-3\\.5');
        expect(sessionButtons).toHaveLength(1);
        expect(sessionButtons[0].text()).toContain('مولد النور');
    });

    it('shows "no sessions" copy when there are none, and "no matches" copy when a search filters all of them out', async () => {
        const empty = mountWorkspace();
        expect(empty.text()).toContain('لا توجد محادثات بعد.');

        chat.sessions.value = [{ id: 1, generator: { name: 'مولد النور' }, created_at: '2024-01-01 08:00:00' }];
        const wrapper = mountWorkspace();
        await wrapper.find('input[placeholder="البحث في المحادثات"]').setValue('غير موجود');

        expect(wrapper.text()).toContain('لا توجد نتائج مطابقة.');
    });

    it('shows the sessions error state with a retry button that calls loadSessions again', async () => {
        chat.sessionsError.value = 'تعذّر تحميل المحادثات.';
        const wrapper = mountWorkspace();

        expect(wrapper.text()).toContain('تعذّر تحميل المحادثات.');

        await wrapper.findAll('button').find((b) => b.text() === 'إعادة المحاولة').trigger('click');
        expect(chat.loadSessions).toHaveBeenCalled();
    });

    it('selecting a session calls openSession with its id and swallows a load failure with no visible error (documents a real gap: sessionError is never rendered)', async () => {
        chat.sessions.value = [{ id: 3, generator: { name: 'مولد النور' }, created_at: '2024-01-01 08:00:00' }];
        chat.openSession.mockImplementation(async () => {
            chat.sessionError.value = 'تعذّر فتح المحادثة.';
            // openSession does NOT set activeSession on failure, matching the real composable.
        });
        const wrapper = mountWorkspace();

        await wrapper.find('aside button.w-full.p-3\\.5').trigger('click');
        await flushPromises();

        expect(chat.openSession).toHaveBeenCalledWith(3);
        // activeSession stayed null, so the workspace falls back to the empty state
        // instead of surfacing 'تعذّر فتح المحادثة.' anywhere.
        expect(wrapper.text()).toContain('ابدأ محادثة جديدة');
        expect(wrapper.text()).not.toContain('تعذّر فتح المحادثة.');
    });

    it('composer: shows the generic subtitle and no generator dropdown for the admin (general support) mode', async () => {
        const wrapper = mountWorkspace({ mode: 'admin' });
        await wrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');

        expect(wrapper.text()).toContain('ابدأ بكتابة سؤالك أو طلبك مباشرة.');
        expect(wrapper.findComponent(AppDropdownSelect).exists()).toBe(false);
    });

    it('composer: shows the generators error / empty / loaded states for a generator-scoped mode', async () => {
        chat.generatorsError.value = 'تعذّر تحميل المولدات.';
        const errorWrapper = mountWorkspace({ mode: 'owner' });
        await errorWrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');
        expect(errorWrapper.text()).toContain('تعذّر تحميل المولدات.');

        chat.generatorsError.value = null;
        const emptyWrapper = mountWorkspace({ mode: 'owner' });
        await emptyWrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');
        expect(emptyWrapper.text()).toContain('ما في مولدات متاحة للاستفسار عنها.');

        chat.generators.value = [{ id: 5, name: 'مولد أ' }, { id: 6, name: 'مولد ب' }];
        const loadedWrapper = mountWorkspace({ mode: 'owner' });
        await loadedWrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');
        expect(loadedWrapper.findComponent(AppDropdownSelect).props('modelValue')).toBe(5);
    });

    it('rejects an over-sized attachment and an unsupported file type with the matching translated error', async () => {
        const wrapper = mountWorkspace({ mode: 'admin' });
        await wrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');

        const bigFile = new File(['x'], 'big.png', { type: 'image/png' });
        Object.defineProperty(bigFile, 'size', { value: 11 * 1024 * 1024 });
        const fileInput = wrapper.find('input[type="file"]:not([capture])');
        Object.defineProperty(fileInput.element, 'files', { value: [bigFile], configurable: true });
        await fileInput.trigger('change');
        expect(wrapper.text()).toContain('حجم الملف كبير جدًا');

        const badExt = new File(['x'], 'notes.txt', { type: 'text/plain' });
        Object.defineProperty(fileInput.element, 'files', { value: [badExt], configurable: true });
        await fileInput.trigger('change');
        expect(wrapper.text()).toContain('صيغة الملف غير مدعومة');
    });

    it('accepts a valid image attachment, previews it, and clears it on remove', async () => {
        const wrapper = mountWorkspace({ mode: 'admin' });
        await wrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');

        const goodFile = new File(['x'], 'photo.png', { type: 'image/png' });
        const fileInput = wrapper.find('input[type="file"]:not([capture])');
        Object.defineProperty(fileInput.element, 'files', { value: [goodFile], configurable: true });
        await fileInput.trigger('change');

        expect(wrapper.find('img[alt="معاينة الصورة المرفقة"]').attributes('src')).toBe('blob:fake-preview');
        expect(wrapper.text()).toContain('photo.png');

        await wrapper.find('[aria-label="إزالة المرفق"]').trigger('click');
        expect(wrapper.find('img[alt="معاينة الصورة المرفقة"]').exists()).toBe(false);
        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:fake-preview');
    });

    it('clicking a quick prompt appends it to the opening message in the composer', async () => {
        const wrapper = mountWorkspace({ mode: 'admin' });
        await wrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');

        await wrapper.findAll('button').find((b) => b.text() === 'هناك صوت غير طبيعي من المولد').trigger('click');

        expect(wrapper.find('textarea').element.value).toBe('هناك صوت غير طبيعي من المولد');
    });

    it('submits the composer form via chat.startSession and closes the composer on success', async () => {
        chat.generators.value = [{ id: 5, name: 'مولد أ' }];
        const wrapper = mountWorkspace({ mode: 'owner' });
        await wrapper.findAll('button').find((b) => b.text() === 'محادثة جديدة').trigger('click');
        await wrapper.find('textarea').setValue('المولد لا يعمل');

        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(chat.startSession).toHaveBeenCalledWith(5, 'المولد لا يعمل', null);
        expect(wrapper.text()).not.toContain('كيف يمكنني مساعدتك؟');
    });

    it('renders active-session messages with role-based styling and formatted content', () => {
        chat.activeSession.value = { id: 9, generator: { name: 'مولد النور' } };
        chat.messages.value = [
            { id: 1, role: 'user', content: 'المولد متوقف', created_at: '2024-01-01 09:00:00' },
            { id: 2, role: 'assistant', content: 'هل تحقّقت من الوقود؟', created_at: '2024-01-01 09:01:00' },
        ];
        const wrapper = mountWorkspace({ mode: 'owner' });

        expect(wrapper.text()).toContain('المولد متوقف');
        expect(wrapper.text()).toContain('هل تحقّقت من الوقود؟');
        const bubbles = wrapper.findAll('.rounded-2xl.px-4.py-2\\.5');
        expect(bubbles[0].classes()).toContain('bg-gradient-to-l');
        expect(bubbles[1].classes()).not.toContain('bg-gradient-to-l');
    });

    it('recording a prediction asks for confirmation and only calls submitAsPrediction when confirmed', async () => {
        chat.activeSession.value = { id: 9, generator: { name: 'مولد النور' } };
        const wrapper = mountWorkspace({ mode: 'owner' });
        confirmMock.mockResolvedValue(false);

        await wrapper.findAll('button').find((b) => b.text() === 'تسجيل توقع').trigger('click');
        await flushPromises();
        expect(chat.submitAsPrediction).not.toHaveBeenCalled();
        expect(wrapper.findAll('button').find((b) => b.text() === 'تسجيل توقع')).toBeTruthy();

        confirmMock.mockResolvedValue(true);
        await wrapper.findAll('button').find((b) => b.text() === 'تسجيل توقع').trigger('click');
        await flushPromises();

        expect(chat.submitAsPrediction).toHaveBeenCalledTimes(1);
        expect(wrapper.findAll('button').find((b) => b.text() === 'تسجيل توقع')).toBeUndefined();
    });

    it('sending a reply calls chat.sendMessage with the draft text, and skips whitespace-only drafts', async () => {
        chat.activeSession.value = { id: 9, generator: { name: 'مولد النور' } };
        const wrapper = mountWorkspace({ mode: 'owner' });

        await wrapper.find('footer form').trigger('submit');
        expect(chat.sendMessage).not.toHaveBeenCalled();

        await wrapper.find('footer textarea').setValue('هل من تحديث؟');
        await wrapper.find('footer form').trigger('submit');
        await flushPromises();

        expect(chat.sendMessage).toHaveBeenCalledWith('هل من تحديث؟', null);
        expect(wrapper.find('footer textarea').element.value).toBe('');
    });
});
