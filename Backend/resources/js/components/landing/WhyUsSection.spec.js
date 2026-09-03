import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import WhyUsSection from './WhyUsSection.vue';

// useCardStack() reads window.matchMedia synchronously and unconditionally (unlike
// useScrollGlow, it does not guard on its existence first) — jsdom has no implementation.
beforeEach(() => {
    vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: false })));
    // v-reveal and useCardStack's observeVisibility() both create a real
    // IntersectionObserver — unavailable in jsdom by default. Its observe() is a no-op,
    // so isInView stays at its default (true) for the whole test.
    vi.stubGlobal('IntersectionObserver', class {
        observe() {}
        unobserve() {}
        disconnect() {}
    });
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { previous: 'السابق', next: 'التالي' },
            landing: {
                why: {
                    eyebrow: 'لماذا أمبير',
                    title: 'صُممت لتوفر وقتك وتقلل أخطاءك',
                    items: {
                        time: 'توفير الوقت', time_desc: 'قراءات وفواتير تتحسب تلقائيًا.',
                        secure: 'وصول آمن', secure_desc: 'تسجيل دخول محمي وصلاحيات دقيقة.',
                        billing: 'فوترة تلقائية', billing_desc: 'فاتورة تُصدر لحظة اعتماد القراءة.',
                        central: 'إدارة مركزية', central_desc: 'كل المولدات من لوحة تحكم واحدة.',
                        analytics: 'تحليلات قوية', analytics_desc: 'رؤية واضحة للإيرادات والاستهلاك.',
                        roles: 'صلاحيات حسب الدور', roles_desc: 'كل دور يشوف ويتحكم فقط بما يخصه.',
                        cloud: 'منصة سحابية', cloud_desc: 'بياناتك محفوظة ومتاحة من أي جهاز.',
                        errors: 'تقليل الأخطاء', errors_desc: 'التحقق التلقائي يمنع أخطاء الحساب اليدوي.',
                    },
                },
            },
        },
    },
});

function mountComponent() {
    return mount(WhyUsSection, { global: { plugins: [i18n] } });
}

function activeDotIndex(wrapper) {
    return wrapper.findAll('.why-dot').findIndex((d) => d.classes().includes('why-dot--active'));
}

describe('WhyUsSection', () => {
    it('renders the eyebrow/title and one stack card + one dot per item, starting on card 0', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('لماذا أمبير');
        expect(wrapper.text()).toContain('صُممت لتوفر وقتك وتقلل أخطاءك');
        expect(wrapper.findAll('.stack-card')).toHaveLength(8);
        expect(wrapper.findAll('.why-dot')).toHaveLength(8);
        expect(activeDotIndex(wrapper)).toBe(0);
        expect(wrapper.findAll('.stack-card')[0].classes()).toContain('stack-card--active');
        expect(wrapper.findAll('.stack-card')[0].attributes('aria-hidden')).toBe('false');
        expect(wrapper.find('[role="tab"][aria-selected="true"]').exists()).toBe(true);
    });

    it('renders each item\'s translated title and description', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('توفير الوقت');
        expect(wrapper.text()).toContain('قراءات وفواتير تتحسب تلقائيًا.');
        expect(wrapper.text()).toContain('تقليل الأخطاء');
        expect(wrapper.text()).toContain('التحقق التلقائي يمنع أخطاء الحساب اليدوي.');
    });

    it('advances to the next card when the "next" nav button is clicked', async () => {
        const wrapper = mountComponent();

        await wrapper.find('.why-nav--next').trigger('click');

        expect(activeDotIndex(wrapper)).toBe(1);
        expect(wrapper.findAll('.stack-card')[1].classes()).toContain('stack-card--active');
        expect(wrapper.findAll('.stack-card')[0].classes()).not.toContain('stack-card--active');
    });

    it('wraps around to the last card when "previous" is clicked from card 0', async () => {
        const wrapper = mountComponent();

        await wrapper.find('.why-nav--prev').trigger('click');

        expect(activeDotIndex(wrapper)).toBe(7);
    });

    it('jumps directly to the clicked dot', async () => {
        const wrapper = mountComponent();

        await wrapper.findAll('.why-dot')[4].trigger('click');

        expect(activeDotIndex(wrapper)).toBe(4);
        expect(wrapper.findAll('.stack-card')[4].attributes('aria-hidden')).toBe('false');
    });

    it('ArrowLeft/ArrowRight on the stack region navigate in the RTL-correct direction (Arabic locale)', async () => {
        const wrapper = mountComponent();
        const region = wrapper.find('[role="region"]');

        // In RTL, ArrowRight visually points "back" -> prev(); wraps 0 -> 7.
        await region.trigger('keydown', { key: 'ArrowRight' });
        expect(activeDotIndex(wrapper)).toBe(7);

        // ArrowLeft visually points "forward" -> next(); 7 -> 0.
        await region.trigger('keydown', { key: 'ArrowLeft' });
        expect(activeDotIndex(wrapper)).toBe(0);
    });

    it('pauses autoplay on mouseenter and resumes it on mouseleave', async () => {
        vi.useFakeTimers();
        const wrapper = mountComponent();
        const region = wrapper.find('[role="region"]');

        await vi.advanceTimersByTimeAsync(4200);
        expect(activeDotIndex(wrapper)).toBe(1);

        await region.trigger('mouseenter');
        await vi.advanceTimersByTimeAsync(4200);
        expect(activeDotIndex(wrapper)).toBe(1); // still paused, did not advance

        await region.trigger('mouseleave');
        await vi.advanceTimersByTimeAsync(4200);
        expect(activeDotIndex(wrapper)).toBe(2); // resumed
    });
});
