import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import CtaSection from './CtaSection.vue';

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
                cta: {
                    title: 'جاهز لتطوّر أعمالك مع أمبير؟',
                    description: 'انضم لمالكي المولدات والمشتركين',
                    primary: 'ابدأ الآن',
                    secondary: 'تواصل معنا',
                },
            },
        },
    },
});

const RouterLinkStub = {
    props: ['to'],
    template: '<a :data-to="JSON.stringify(to)"><slot /></a>',
};

function mountComponent() {
    return mount(CtaSection, {
        global: {
            plugins: [i18n],
            stubs: { RouterLink: RouterLinkStub },
        },
    });
}

describe('CtaSection', () => {
    it('renders the title, description and both call-to-action labels', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('جاهز لتطوّر أعمالك مع أمبير؟');
        expect(wrapper.text()).toContain('انضم لمالكي المولدات والمشتركين');
        expect(wrapper.text()).toContain('ابدأ الآن');
        expect(wrapper.text()).toContain('تواصل معنا');
    });

    it('points the primary action at the register route', () => {
        const wrapper = mountComponent();

        const primary = wrapper.find('a[data-to]');
        expect(primary.attributes('data-to')).toBe(JSON.stringify({ name: 'register' }));
        expect(primary.text()).toBe('ابدأ الآن');
    });

    it('points the secondary action at the in-page #contact anchor', () => {
        const wrapper = mountComponent();

        const links = wrapper.findAll('a');
        const secondary = links.find((a) => a.text() === 'تواصل معنا');
        expect(secondary.attributes('href')).toBe('#contact');
    });
});
