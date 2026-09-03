import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import HumanitarianSection from './HumanitarianSection.vue';

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
                human: {
                    eyebrow: 'بُعدنا الإنساني',
                    title: 'أكثر من مجرد منصة إدارة',
                    subtitle: 'بنينا أمبير عشان نخفف عن غزة',
                    items: {
                        displaced_title: 'دعم العائلات النازحة',
                        displaced_desc: 'نسهّل ربط مراكز الإيواء بمولدات قريبة',
                        critical_title: 'أولوية للحالات الحرجة',
                        critical_desc: 'مرضى الأجهزة الطبية المنزلية بيقدروا يطلبوا أولوية',
                        transparency_title: 'شفافية بدون استغلال',
                        transparency_desc: 'كل سعر وكل فاتورة واضحة من أول يوم',
                    },
                },
            },
        },
    },
});

function mountComponent() {
    return mount(HumanitarianSection, { global: { plugins: [i18n] } });
}

describe('HumanitarianSection', () => {
    it('renders the section heading text', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('بُعدنا الإنساني');
        expect(wrapper.text()).toContain('أكثر من مجرد منصة إدارة');
        expect(wrapper.text()).toContain('بنينا أمبير عشان نخفف عن غزة');
    });

    it('renders exactly the three humanitarian items with their titles and descriptions', () => {
        const wrapper = mountComponent();

        const cards = wrapper.findAll('.human-card');
        expect(cards).toHaveLength(3);

        expect(wrapper.text()).toContain('دعم العائلات النازحة');
        expect(wrapper.text()).toContain('نسهّل ربط مراكز الإيواء بمولدات قريبة');
        expect(wrapper.text()).toContain('أولوية للحالات الحرجة');
        expect(wrapper.text()).toContain('مرضى الأجهزة الطبية المنزلية بيقدروا يطلبوا أولوية');
        expect(wrapper.text()).toContain('شفافية بدون استغلال');
        expect(wrapper.text()).toContain('كل سعر وكل فاتورة واضحة من أول يوم');
    });
});
