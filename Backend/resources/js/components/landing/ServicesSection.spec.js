import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import ServicesSection from './ServicesSection.vue';

// v-reveal (and useScrollGlow's own IntersectionObserver check) needs this — unavailable in jsdom by default.
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
                services: {
                    eyebrow: 'خدماتنا',
                    title: 'كل شي بتحتاجه بمكان واحد',
                    subtitle: 'من الاشتراك الأول لحد المتابعة اليومية، صمّمنا كل خدمة عشان تكون بسيطة وواضحة.',
                    items: {
                        subscribe_title: 'اشتراك بمولد كهرباء',
                        subscribe_desc: 'اختر مولد قريب منك، حدد فترة التشغيل والسعة المناسبة، وابدأ اشتراكك خلال دقائق.',
                        reading_title: 'قراءة عداد وفوترة تلقائية',
                        reading_desc: 'قراءات دورية موثّقة، وفاتورة تُحسب تلقائيًا حسب استهلاكك الفعلي.',
                        payment_title: 'دفع مرن وآمن',
                        payment_desc: 'ادفع نقدًا، بمحفظة إلكترونية، أو تحويل بنكي — مع إثبات دفع موثّق لكل عملية.',
                        maintenance_title: 'صيانة ودعم فني',
                        maintenance_desc: 'فريق فنيين مختص يتابع أعطال المولدات ويحافظ على استقرار الخدمة.',
                        complaints_title: 'شكاوى ومتابعة',
                        complaints_desc: 'قدّم شكوى بخطوة وحدة، وتابع حالتها لحد الحل.',
                        notifications_title: 'إشعارات لحظية',
                        notifications_desc: 'إشعار فوري بأي تحديث على فاتورتك، دفعتك، أو اشتراكك.',
                    },
                },
            },
        },
    },
});

function mountComponent() {
    return mount(ServicesSection, { global: { plugins: [i18n] } });
}

describe('ServicesSection', () => {
    it('renders the eyebrow, title and subtitle', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('خدماتنا');
        expect(wrapper.text()).toContain('كل شي بتحتاجه بمكان واحد');
        expect(wrapper.text()).toContain('من الاشتراك الأول لحد المتابعة اليومية');
    });

    it('renders exactly one card per service, in the declared order, each with its own title and description', () => {
        const wrapper = mountComponent();

        const titles = wrapper.findAll('h3').map((h) => h.text());
        expect(titles).toEqual([
            'اشتراك بمولد كهرباء',
            'قراءة عداد وفوترة تلقائية',
            'دفع مرن وآمن',
            'صيانة ودعم فني',
            'شكاوى ومتابعة',
            'إشعارات لحظية',
        ]);

        expect(wrapper.text()).toContain('اختر مولد قريب منك، حدد فترة التشغيل والسعة المناسبة، وابدأ اشتراكك خلال دقائق.');
        expect(wrapper.text()).toContain('إشعار فوري بأي تحديث على فاتورتك، دفعتك، أو اشتراكك.');
    });
});
