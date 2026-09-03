import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import FaqSection from './FaqSection.vue';

// vReveal uses IntersectionObserver, unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                faq: {
                    eyebrow: 'الأسئلة الشائعة',
                    title: 'عندك سؤال؟ عنا الجواب',
                    items: [
                        { q: 'سؤال 1', a: 'جواب 1' },
                        { q: 'سؤال 2', a: 'جواب 2' },
                        { q: 'سؤال 3', a: 'جواب 3' },
                        { q: 'سؤال 4', a: 'جواب 4' },
                        { q: 'سؤال 5', a: 'جواب 5' },
                    ],
                    showMore: 'عرض {count} أسئلة أخرى',
                    showLess: 'عرض أقل',
                },
            },
        },
    },
});

function mountComponent() {
    return mount(FaqSection, { global: { plugins: [i18n] } });
}

describe('FaqSection', () => {
    it('initially renders only the first 4 questions, with a "show more" button', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('سؤال 1');
        expect(wrapper.text()).toContain('سؤال 4');
        expect(wrapper.text()).not.toContain('سؤال 5');
        expect(wrapper.text()).toContain('عرض 1 أسئلة أخرى');
    });

    it('expands to show all questions when "show more" is clicked, and label flips to "show less"', async () => {
        const wrapper = mountComponent();

        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('سؤال 5');
        expect(wrapper.text()).toContain('عرض أقل');
        expect(wrapper.text()).not.toContain('أسئلة أخرى');
    });

    it('collapses back to 4 questions on a second "show/hide" click', async () => {
        const wrapper = mountComponent();
        const button = wrapper.find('button');

        await button.trigger('click');
        await button.trigger('click');

        expect(wrapper.text()).not.toContain('سؤال 5');
        expect(wrapper.text()).toContain('عرض 1 أسئلة أخرى');
    });

    it('toggles a question open on click: aria-expanded and the collapse row both flip', async () => {
        const wrapper = mountComponent();
        const firstItem = wrapper.findAll('[role="button"]')[0];

        expect(firstItem.attributes('aria-expanded')).toBe('false');
        const collapseEl = firstItem.find('.grid');
        expect(collapseEl.attributes('style')).toContain('grid-template-rows: 0fr');

        await firstItem.trigger('click');

        expect(firstItem.attributes('aria-expanded')).toBe('true');
        expect(collapseEl.attributes('style')).toContain('grid-template-rows: 1fr');
    });

    it('closes the open question again on a second click, and Enter/Space toggle it too', async () => {
        const wrapper = mountComponent();
        const secondItem = wrapper.findAll('[role="button"]')[1];

        await secondItem.trigger('keydown.enter');
        expect(secondItem.attributes('aria-expanded')).toBe('true');

        await secondItem.trigger('keydown.space');
        expect(secondItem.attributes('aria-expanded')).toBe('false');
    });

    it('only one question is expanded at a time (opening a second closes the first)', async () => {
        const wrapper = mountComponent();
        const items = wrapper.findAll('[role="button"]');

        await items[0].trigger('click');
        expect(items[0].attributes('aria-expanded')).toBe('true');

        await items[1].trigger('click');
        expect(items[1].attributes('aria-expanded')).toBe('true');
        expect(items[0].attributes('aria-expanded')).toBe('false');
    });
});
