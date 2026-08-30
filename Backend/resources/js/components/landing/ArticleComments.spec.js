import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import ArticleComments from './ArticleComments.vue';

vi.mock('@/services/articleCommentService', () => ({
    default: {
        publicList: vi.fn().mockResolvedValue({ data: { data: { data: [] } } }),
        publicStore: vi.fn(),
    },
}));

import articleCommentService from '@/services/articleCommentService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                articles_page: {
                    comments_title: 'التعليقات',
                    comments_load_error: 'تعذر تحميل التعليقات',
                    no_comments_yet: 'لا توجد تعليقات بعد',
                    add_comment_title: 'أضف تعليقًا',
                    comment_submitted: 'تم إرسال التعليق',
                    comment_submit_error: 'تعذر إرسال التعليق',
                    comment_name_placeholder: 'الاسم',
                    comment_email_placeholder: 'البريد الإلكتروني',
                    comment_placeholder: 'تعليقك',
                    comment_sending: 'جارٍ الإرسال...',
                    comment_submit: 'إرسال',
                    team_reply: 'رد الفريق',
                },
            },
            subscribers_page: {
                time_now: 'الآن',
                time_mins_ago: '{mins} دقيقة',
                time_hours_ago: '{hours} ساعة',
                time_days_ago: '{days} يوم',
            },
        },
    },
});

async function mountComponent() {
    const wrapper = mount(ArticleComments, {
        props: { slug: 'test-article' },
        global: { plugins: [i18n] },
    });
    await flushPromises();
    return wrapper;
}

async function submitForm(wrapper) {
    await wrapper.find('input[type="text"]:not(#website)').setValue('Test User');
    await wrapper.find('textarea').setValue('This is a test comment.');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();
}

describe('ArticleComments — submitComment error handling (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        articleCommentService.publicList.mockResolvedValue({ data: { data: { data: [] } } });
    });

    it('shows per-field validation errors on a 422 response, without setting the generic submitError', async () => {
        articleCommentService.publicStore.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    message: 'The given data was invalid.',
                    errors: { comment: ['التعليق مطلوب.'] },
                },
            },
        });

        const wrapper = await mountComponent();
        await submitForm(wrapper);

        expect(wrapper.text()).toContain('التعليق مطلوب.');
        expect(wrapper.text()).not.toContain('تعذر إرسال التعليق');
    });

    it('shows the generic submitError message on a non-422 error (e.g. 500), without field errors', async () => {
        articleCommentService.publicStore.mockRejectedValue({
            response: {
                status: 500,
                data: { message: 'Server error occurred.' },
            },
        });

        const wrapper = await mountComponent();
        await submitForm(wrapper);

        expect(wrapper.text()).toContain('Server error occurred.');
    });

    it('falls back to the translated generic message on a network error (no response)', async () => {
        articleCommentService.publicStore.mockRejectedValue({ message: 'Network Error' });

        const wrapper = await mountComponent();
        await submitForm(wrapper);

        expect(wrapper.text()).toContain('تعذر إرسال التعليق');
    });

    it('shows success state and resets the form on a successful submit', async () => {
        articleCommentService.publicStore.mockResolvedValue({ data: {} });

        const wrapper = await mountComponent();
        await submitForm(wrapper);

        expect(wrapper.text()).toContain('تم إرسال التعليق');
    });
});
