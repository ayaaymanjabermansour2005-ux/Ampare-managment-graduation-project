import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import ArticleRatingWidget from './ArticleRatingWidget.vue';
import { useToastStore } from '@/stores/toast';

vi.mock('@/services/articleCommentService', () => ({
    default: { ratingShow: vi.fn(), ratingStore: vi.fn() },
}));

import articleCommentService from '@/services/articleCommentService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                articles_page: {
                    rate_star: 'قيّم بـ {n} نجوم',
                    rate_error: 'تعذّر إرسال تقييمك، يرجى المحاولة مرة أخرى.',
                    ratings_count: 'تقييم',
                    no_ratings_yet: 'لا يوجد تقييمات بعد',
                    you_rated: 'قيّمتها بـ {n} نجوم',
                },
            },
        },
    },
});

function mountComponent(slug = 'my-article') {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(ArticleRatingWidget, {
        props: { slug },
        global: { plugins: [i18n, pinia] },
    });
    return { wrapper, pinia };
}

describe('ArticleRatingWidget', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('always renders 5 star buttons, even before the rating loads', () => {
        articleCommentService.ratingShow.mockReturnValue(new Promise(() => {}));

        const { wrapper } = mountComponent();

        expect(wrapper.findAll('button')).toHaveLength(5);
        expect(wrapper.text()).not.toContain('تقييم');
    });

    it('renders the average and count once loaded, and requests the given slug', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 4.5, count: 12, my_rating: null } } });

        const { wrapper } = mountComponent('the-slug');
        await flushPromises();

        expect(articleCommentService.ratingShow).toHaveBeenCalledWith('the-slug');
        expect(wrapper.text()).toContain('4.5');
        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('تقييم');
    });

    it('shows the "no ratings yet" message when count is 0', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 0, count: 0, my_rating: null } } });

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('لا يوجد تقييمات بعد');
    });

    it('shows the "you rated" badge only when my_rating is set', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 5, count: 1, my_rating: 5 } } });

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('قيّمتها بـ 5 نجوم');
    });

    it('does not blow up the widget when the initial load fails (silent failure, defaults kept)', async () => {
        articleCommentService.ratingShow.mockRejectedValue(new Error('network down'));

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.findAll('button')).toHaveLength(5);
        expect(wrapper.text()).toContain('لا يوجد تقييمات بعد');
    });

    it('clicking a star submits that rating and updates the displayed average/count', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 0, count: 0, my_rating: null } } });
        articleCommentService.ratingStore.mockResolvedValue({ data: { data: { average: 4, count: 1, my_rating: 4 } } });

        const { wrapper } = mountComponent('slug-x');
        await flushPromises();

        await wrapper.findAll('button')[3].trigger('click'); // 4th star => star=4
        await flushPromises();

        expect(articleCommentService.ratingStore).toHaveBeenCalledWith('slug-x', 4);
        expect(wrapper.text()).toContain('قيّمتها بـ 4 نجوم');
    });

    it('shows a danger toast with the API error message when submitting fails', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 0, count: 0, my_rating: null } } });
        articleCommentService.ratingStore.mockRejectedValue({
            response: { status: 429, data: { message: 'حاول لاحقًا.' } },
        });

        const { wrapper, pinia } = mountComponent();
        await flushPromises();

        await wrapper.findAll('button')[0].trigger('click');
        await flushPromises();

        const toastStore = useToastStore(pinia);
        expect(toastStore.toasts).toHaveLength(1);
        expect(toastStore.toasts[0].type).toBe('danger');
        expect(toastStore.toasts[0].message).toBe('حاول لاحقًا.');
    });

    it('falls back to the translated rate_error message when submitting fails with no server message (network error)', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 0, count: 0, my_rating: null } } });
        articleCommentService.ratingStore.mockRejectedValue({ message: 'Network Error' });

        const { wrapper, pinia } = mountComponent();
        await flushPromises();

        await wrapper.findAll('button')[0].trigger('click');
        await flushPromises();

        const toastStore = useToastStore(pinia);
        expect(toastStore.toasts[0].message).toBe('تعذّر إرسال تقييمك، يرجى المحاولة مرة أخرى.');
    });

    it('ignores extra clicks while a submission is already in flight', async () => {
        articleCommentService.ratingShow.mockResolvedValue({ data: { data: { average: 0, count: 0, my_rating: null } } });
        let resolveStore;
        articleCommentService.ratingStore.mockReturnValue(new Promise((resolve) => { resolveStore = resolve; }));

        const { wrapper } = mountComponent();
        await flushPromises();

        const stars = wrapper.findAll('button');
        await stars[2].trigger('click');
        await stars[4].trigger('click'); // should be ignored — isSubmitting guard

        expect(articleCommentService.ratingStore).toHaveBeenCalledTimes(1);
        expect(stars[0].attributes('disabled')).toBeDefined();

        resolveStore({ data: { data: { average: 3, count: 1, my_rating: 3 } } });
        await flushPromises();
    });
});
