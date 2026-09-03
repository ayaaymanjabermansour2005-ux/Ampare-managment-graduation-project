import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import AboutSection from './AboutSection.vue';
import AppIcon from '@/components/ui/AppIcon.vue';

// vReveal (used as `v-reveal`) is a local <script setup> import — IntersectionObserver
// is unavailable in jsdom by default.
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
                story: {
                    eyebrow: 'قصتنا',
                    title: 'كهرباء موثوقة، بإدارة شفّافة',
                    description: 'أمبير منصّة بتربط أصحاب مولدات الكهرباء بالمشتركين بطريقة منظّمة وشفّافة.',
                    pillar1_title: 'قياس دقيق',
                    pillar1_desc: 'قراءات عداد موثّقة وفواتير محسوبة تلقائيًا حسب الاستهلاك الفعلي.',
                    pillar2_title: 'شفافية كاملة',
                    pillar2_desc: 'كل دفعة موثّقة، كل فاتورة واضحة، وسجل كامل لأي عملية صيانة.',
                    pillar3_title: 'قريبين من المجتمع',
                    pillar3_desc: 'مبنية لخدمة الاحتياج اليومي الفعلي، مو مجرد أداة تقنية.',
                },
            },
        },
    },
});

function mountSection() {
    return mount(AboutSection, { global: { plugins: [i18n] } });
}

describe('AboutSection', () => {
    it('renders the eyebrow, title and description', () => {
        const wrapper = mountSection();

        expect(wrapper.text()).toContain('قصتنا');
        expect(wrapper.text()).toContain('كهرباء موثوقة، بإدارة شفّافة');
        expect(wrapper.text()).toContain('أمبير منصّة بتربط أصحاب مولدات الكهرباء بالمشتركين بطريقة منظّمة وشفّافة.');
    });

    it('renders exactly 3 pillars, each numbered and with its translated title/description', () => {
        const wrapper = mountSection();
        const pillars = wrapper.findAll('.story-pillar');
        expect(pillars).toHaveLength(3);

        expect(pillars[0].text()).toContain('01');
        expect(pillars[0].text()).toContain('قياس دقيق');
        expect(pillars[0].text()).toContain('قراءات عداد موثّقة وفواتير محسوبة تلقائيًا حسب الاستهلاك الفعلي.');

        expect(pillars[1].text()).toContain('02');
        expect(pillars[1].text()).toContain('شفافية كاملة');

        expect(pillars[2].text()).toContain('03');
        expect(pillars[2].text()).toContain('قريبين من المجتمع');
    });

    it('resolves the correct Lucide icon for each pillar via AppIcon', () => {
        const wrapper = mountSection();
        const icons = wrapper.findAllComponents(AppIcon);

        expect(icons.map((i) => i.props('name'))).toEqual([
            'fa-solid fa-gauge',
            'fa-solid fa-shield-halved',
            'fa-solid fa-people-group',
        ]);
    });

    it('applies the reveal directive class to the heading block and each pillar', () => {
        const wrapper = mountSection();

        expect(wrapper.find('.max-w-3xl').classes()).toContain('reveal');
        wrapper.findAll('.story-pillar').forEach((pillar) => {
            expect(pillar.classes()).toContain('reveal');
        });
    });
});
