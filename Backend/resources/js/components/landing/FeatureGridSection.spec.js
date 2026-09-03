import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import FeatureGridSection from './FeatureGridSection.vue';

// vReveal uses IntersectionObserver, unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

const FEATURE_KEYS = [
    'generators', 'subscribers', 'readings', 'billing',
    'payments', 'fuel', 'maintenance', 'technicians', 'reports',
];

function itemMessages() {
    const items = {};
    FEATURE_KEYS.forEach((key) => {
        items[`${key}_title`] = `عنوان ${key}`;
        items[`${key}_desc`] = `وصف ${key}`;
    });
    return items;
}

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                features: {
                    eyebrow: 'المزايا',
                    title: 'كل أدوات إدارة المولدات في مكان واحد',
                    subtitle: 'منصة شاملة',
                    items: itemMessages(),
                    showMore: 'عرض {count} ميزات أخرى',
                    showLess: 'عرض أقل',
                },
            },
        },
    },
});

function mountComponent() {
    return mount(FeatureGridSection, { global: { plugins: [i18n] } });
}

describe('FeatureGridSection', () => {
    it('renders the heading and all 9 features in the desktop bento grid', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('كل أدوات إدارة المولدات في مكان واحد');

        const bentoItems = wrapper.findAll('.bento-item');
        expect(bentoItems).toHaveLength(FEATURE_KEYS.length);
        FEATURE_KEYS.forEach((key) => {
            expect(wrapper.text()).toContain(`عنوان ${key}`);
            expect(wrapper.text()).toContain(`وصف ${key}`);
        });
    });

    it('marks exactly the "generators" tile as the featured hero tile', () => {
        const wrapper = mountComponent();

        const heroTiles = wrapper.findAll('.bento-item--hero');
        expect(heroTiles).toHaveLength(1);
        expect(heroTiles[0].text()).toContain('عنوان generators');
    });

    it('starts the mobile fallback list truncated to 4 items with a "show more" button', () => {
        const wrapper = mountComponent();

        expect(wrapper.findAll('.feature-list-item')).toHaveLength(4);
        expect(wrapper.text()).toContain('عرض 5 ميزات أخرى');
    });

    it('expands the mobile list to all 9 items on "show more", then collapses back on "show less"', async () => {
        const wrapper = mountComponent();
        const button = wrapper.find('button');

        await button.trigger('click');
        expect(wrapper.findAll('.feature-list-item')).toHaveLength(9);
        expect(wrapper.text()).toContain('عرض أقل');

        await button.trigger('click');
        expect(wrapper.findAll('.feature-list-item')).toHaveLength(4);
        expect(wrapper.text()).toContain('عرض 5 ميزات أخرى');
    });
});
