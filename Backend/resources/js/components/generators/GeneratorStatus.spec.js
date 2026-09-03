import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import GeneratorStatus from './GeneratorStatus.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_generators: {
                status: { active: 'يعمل الآن', maintenance: 'قيد الصيانة', inactive: 'متوقف' },
            },
        },
    },
});

function mountStatus(status) {
    return mount(GeneratorStatus, { props: { status }, global: { plugins: [i18n] } });
}

describe('GeneratorStatus', () => {
    it('renders the translated label and success chip class for "active"', () => {
        const wrapper = mountStatus('active');
        expect(wrapper.text()).toBe('يعمل الآن');
        expect(wrapper.find('span').classes()).toContain('chip-success');
    });

    it('renders the translated label and warning chip class for "maintenance"', () => {
        const wrapper = mountStatus('maintenance');
        expect(wrapper.text()).toBe('قيد الصيانة');
        expect(wrapper.find('span').classes()).toContain('chip-warning');
    });

    it('renders the translated label and danger chip class for "inactive"', () => {
        const wrapper = mountStatus('inactive');
        expect(wrapper.text()).toBe('متوقف');
        expect(wrapper.find('span').classes()).toContain('chip-danger');
    });

    it('falls back to the danger chip class and the raw status string for an unknown status', () => {
        const wrapper = mountStatus('some_unknown_status');
        expect(wrapper.text()).toBe('some_unknown_status');
        expect(wrapper.find('span').classes()).toContain('chip-danger');
    });
});
