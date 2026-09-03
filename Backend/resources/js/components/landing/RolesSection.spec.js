import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import RolesSection from './RolesSection.vue';

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
                roles: {
                    eyebrow: 'الأدوار وآلية العمل',
                    title: 'كل دور إله صلاحياته ومساره الخاص',
                    subtitle: 'اضغط على أي كارد',
                    tap_for_details: 'اضغط لعرض التفاصيل',
                    tap_to_go_back: 'اضغط للرجوع',
                    items: {
                        admin: {
                            title: 'المدير', summary: 'إشراف كامل على المنصة',
                            how_title: 'كيف بيتعامل المدير',
                            points: ['يراجع طلبات الانضمام', 'يدير الحسابات'],
                        },
                        owner: {
                            title: 'مالك المولد', summary: 'إدارة مولده ومشتركيه',
                            how_title: 'كيف بيتعامل مالك المولد',
                            points: ['ينضم بفورم واحد', 'يدير القدرة والسعر'],
                        },
                        technician: {
                            title: 'الفني', summary: 'تنفيذ الصيانة ميدانيًا',
                            how_title: 'كيف بيتعامل الفني',
                            points: ['يُنشأ من قبل مالك المولد', 'يسجّل قراءات العداد'],
                        },
                        subscriber: {
                            title: 'المشترك', summary: 'إدارة اشتراكه الشخصي',
                            how_title: 'كيف بيتعامل المشترك',
                            points: ['يسجّل مباشرة', 'يتصفح المولدات المتاحة'],
                        },
                    },
                },
            },
        },
    },
});

function mountComponent() {
    return mount(RolesSection, { global: { plugins: [i18n] } });
}

describe('RolesSection', () => {
    it('renders all four role cards, each starting unflipped', () => {
        const wrapper = mountComponent();

        const cards = wrapper.findAll('.flip-card');
        expect(cards).toHaveLength(4);
        cards.forEach((card) => {
            expect(card.classes()).not.toContain('flipped');
            expect(card.attributes('aria-pressed')).toBe('false');
        });

        expect(wrapper.text()).toContain('المدير');
        expect(wrapper.text()).toContain('مالك المولد');
        expect(wrapper.text()).toContain('الفني');
        expect(wrapper.text()).toContain('المشترك');
    });

    it('renders the back-face detail points for every role up front (always in the DOM)', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('يراجع طلبات الانضمام');
        expect(wrapper.text()).toContain('ينضم بفورم واحد');
        expect(wrapper.text()).toContain('يُنشأ من قبل مالك المولد');
        expect(wrapper.text()).toContain('يسجّل مباشرة');
    });

    it('flips only the clicked card on click, independently of the others', async () => {
        const wrapper = mountComponent();
        const cards = wrapper.findAll('.flip-card');

        await cards[0].trigger('click');

        expect(cards[0].classes()).toContain('flipped');
        expect(cards[0].attributes('aria-pressed')).toBe('true');
        expect(cards[1].classes()).not.toContain('flipped');
        expect(cards[2].classes()).not.toContain('flipped');
        expect(cards[3].classes()).not.toContain('flipped');
    });

    it('toggles back to unflipped on a second click', async () => {
        const wrapper = mountComponent();
        const card = wrapper.findAll('.flip-card')[1];

        await card.trigger('click');
        expect(card.classes()).toContain('flipped');

        await card.trigger('click');
        expect(card.classes()).not.toContain('flipped');
        expect(card.attributes('aria-pressed')).toBe('false');
    });

    it('flips the card on Enter and Space keydown as well', async () => {
        const wrapper = mountComponent();
        const card = wrapper.findAll('.flip-card')[2];

        await card.trigger('keydown.enter');
        expect(card.classes()).toContain('flipped');

        await card.trigger('keydown.space');
        expect(card.classes()).not.toContain('flipped');
    });
});
