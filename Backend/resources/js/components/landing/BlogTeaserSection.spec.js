import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import BlogTeaserSection from './BlogTeaserSection.vue';

// vReveal uses IntersectionObserver, unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/articleService', () => ({
    default: { listPublic: vi.fn() },
}));

import articleService from '@/services/articleService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    fallbackLocale: 'ar',
    messages: {
        ar: {
            landing: {
                blog: { eyebrow: 'مدونة أمبير', title: 'مقالات ونصائح', empty: 'لا توجد مقالات منشورة حاليًا', error: 'تعذّر تحميل المقالات' },
            },
        },
        en: {
            landing: {
                blog: { eyebrow: 'Ambar Blog', title: 'Articles & tips', empty: 'No articles yet', error: 'Failed to load' },
            },
        },
    },
});

const RouterLinkStub = {
    props: ['to'],
    template: '<a :data-to="JSON.stringify(to)"><slot /></a>',
};

function mountComponent() {
    return mount(BlogTeaserSection, {
        global: {
            plugins: [i18n],
            stubs: { RouterLink: RouterLinkStub },
        },
    });
}

function article(overrides = {}) {
    return {
        id: 1, slug: 'article-1', title: 'عنوان المقال', title_en: 'Article Title',
        excerpt: 'مقتطف المقال', excerpt_en: 'Article excerpt',
        author_name: 'فريق أمبير', published_at: '1 سبتمبر 2026', cover_image_url: null,
        ...overrides,
    };
}

describe('BlogTeaserSection', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        i18n.global.locale.value = 'ar';
    });

    it('shows a loading skeleton of 3 placeholders before the request resolves', () => {
        articleService.listPublic.mockReturnValue(new Promise(() => {}));

        const wrapper = mountComponent();

        expect(wrapper.findAll('.animate-pulse')).toHaveLength(3);
    });

    it('renders up to 3 articles as links, even when the API returns more', async () => {
        const articles = [1, 2, 3, 4].map((id) => article({ id, slug: `a${id}`, title: `مقال ${id}` }));
        articleService.listPublic.mockResolvedValue({ data: { data: articles } });

        const wrapper = mountComponent();
        await flushPromises();

        const links = wrapper.findAllComponents(RouterLinkStub);
        expect(links).toHaveLength(3);
        expect(wrapper.text()).toContain('مقال 1');
        expect(wrapper.text()).toContain('مقال 3');
        expect(wrapper.text()).not.toContain('مقال 4');
    });

    it('links each article card to the article detail route by slug', async () => {
        articleService.listPublic.mockResolvedValue({ data: { data: [article({ slug: 'my-slug' })] } });

        const wrapper = mountComponent();
        await flushPromises();

        const link = wrapper.findComponent(RouterLinkStub);
        expect(link.attributes('data-to')).toBe(
            JSON.stringify({ name: 'landing.articles.show', params: { slug: 'my-slug' } }),
        );
    });

    it('shows the excerpt and author/date line when present', async () => {
        articleService.listPublic.mockResolvedValue({
            data: { data: [article({ excerpt: 'مقتطف مميز', author_name: 'أحمد', published_at: '3 سبتمبر 2026' })] },
        });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('مقتطف مميز');
        expect(wrapper.text()).toContain('أحمد');
        expect(wrapper.text()).toContain('3 سبتمبر 2026');
    });

    it('falls back to the English title/excerpt only when locale is English', async () => {
        i18n.global.locale.value = 'en';
        articleService.listPublic.mockResolvedValue({ data: { data: [article()] } });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('Article Title');
        expect(wrapper.text()).toContain('Article excerpt');
        expect(wrapper.text()).not.toContain('عنوان المقال');
    });

    it('shows the empty state when the API returns no articles', async () => {
        articleService.listPublic.mockResolvedValue({ data: { data: [] } });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('لا توجد مقالات منشورة حاليًا');
        expect(wrapper.findAllComponents(RouterLinkStub)).toHaveLength(0);
    });

    it('shows the error state when the request rejects', async () => {
        articleService.listPublic.mockRejectedValue(new Error('network down'));

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('تعذّر تحميل المقالات');
    });
});
