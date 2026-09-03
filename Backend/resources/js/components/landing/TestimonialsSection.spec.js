import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import TestimonialsSection from './TestimonialsSection.vue';

// v-reveal creates a real IntersectionObserver on mount — unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

const TESTIMONIAL_ITEMS = [
    { name: 'أم أحمد', role: 'مشتركة', text: 'أصبحتُ أعرف قيمة فاتورتي بدقة كل شهر.', stars: 5 },
    { name: 'أبو محمد الشوا', role: 'مالك مولد', text: 'وفّرت الفوترة التلقائية عليّ وقتًا كبيرًا.', stars: 5 },
    { name: 'إياد النجار', role: 'مالك مولد', text: 'أصبحت متابعة الفنيين والمهام أسهل بكثير.', stars: 4 },
    { name: 'سارة خليل', role: 'مشتركة', text: 'تمكنتُ من الإبلاغ عن عطل، وتم إصلاحه في اليوم ذاته.', stars: 5 },
    { name: 'محمود أبو عودة', role: 'مالك مولد', text: 'أسهمت الشفافية مع المشتركين في زيادة الثقة.', stars: 5 },
    { name: 'خالد أبو شمالة', role: 'مالك مولد', text: 'تساعدني التقارير في فهم استهلاك الوقود.', stars: 4 },
];

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { rate_n_stars: 'تقييم {n} من 5 نجوم' },
            landing: {
                testimonials: {
                    eyebrow: 'آراء المستخدمين',
                    title: 'شهادات مستخدمينا هي أصدق تعبير عن تجربتهم معنا',
                    items: TESTIMONIAL_ITEMS,
                    showMore: 'عرض {count} آراء أخرى',
                    showLess: 'عرض أقل',
                },
            },
        },
    },
});

function mountComponent() {
    return mount(TestimonialsSection, { global: { plugins: [i18n] } });
}

describe('TestimonialsSection', () => {
    it('renders the eyebrow and title', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('آراء المستخدمين');
        expect(wrapper.text()).toContain('شهادات مستخدمينا هي أصدق تعبير عن تجربتهم معنا');
    });

    it('shows only the first 3 testimonials initially, with a "show more" button for the rest', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('أم أحمد');
        expect(wrapper.text()).toContain('أبو محمد الشوا');
        expect(wrapper.text()).toContain('إياد النجار');
        expect(wrapper.text()).not.toContain('سارة خليل');
        expect(wrapper.text()).not.toContain('محمود أبو عودة');

        // 6 items total, 3 shown initially -> "عرض 3 آراء أخرى"
        expect(wrapper.text()).toContain('عرض 3 آراء أخرى');
        expect(wrapper.text()).not.toContain('عرض أقل');
    });

    it('renders a star-row aria-label reflecting each item\'s own rating', () => {
        const wrapper = mountComponent();
        const labels = wrapper.findAll('.star-row').map((el) => el.attributes('aria-label'));

        expect(labels).toEqual([
            'تقييم 5 من 5 نجوم', // أم أحمد
            'تقييم 5 من 5 نجوم', // أبو محمد الشوا
            'تقييم 4 من 5 نجوم', // إياد النجار
        ]);
    });

    it('reveals the remaining testimonials and flips the button to "show less" on click', async () => {
        const wrapper = mountComponent();

        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toContain('سارة خليل');
        expect(wrapper.text()).toContain('محمود أبو عودة');
        expect(wrapper.text()).toContain('خالد أبو شمالة');
        expect(wrapper.text()).toContain('عرض أقل');
        expect(wrapper.text()).not.toContain('عرض 3 آراء أخرى');
    });

    it('collapses back to 3 items when "show less" is clicked again', async () => {
        const wrapper = mountComponent();

        await wrapper.find('button').trigger('click'); // expand
        await wrapper.find('button').trigger('click'); // collapse

        expect(wrapper.text()).not.toContain('سارة خليل');
        expect(wrapper.text()).toContain('عرض 3 آراء أخرى');
    });
});
