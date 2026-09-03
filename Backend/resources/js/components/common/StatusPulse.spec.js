import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import StatusPulse from './StatusPulse.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                status_pulse: {
                    active: 'يعمل الآن',
                    maintenance: 'قيد الصيانة',
                    inactive: 'متوقف',
                },
            },
        },
    },
});

function mountStatus(props) {
    return mount(StatusPulse, { props, global: { plugins: [i18n] } });
}

describe('StatusPulse', () => {
    it('renders the translated status text and a live pulse dot for "active"', () => {
        const wrapper = mountStatus({ status: 'active' });
        expect(wrapper.text()).toBe('يعمل الآن');
        expect(wrapper.find('.status-dot-live').exists()).toBe(true);
        expect(wrapper.classes()).toContain('text-success');
    });

    it('renders the translated status text with no pulse dot for "inactive"', () => {
        const wrapper = mountStatus({ status: 'inactive' });
        expect(wrapper.text()).toBe('متوقف');
        expect(wrapper.find('.status-dot-live').exists()).toBe(false);
    });

    it('renders a pulse dot and the translated text for "maintenance"', () => {
        const wrapper = mountStatus({ status: 'maintenance' });
        expect(wrapper.text()).toBe('قيد الصيانة');
        expect(wrapper.find('.status-dot-live').exists()).toBe(true);
        expect(wrapper.classes()).toContain('text-warning');
    });

    it('shows an explicit label instead of the translated status text when provided', () => {
        const wrapper = mountStatus({ status: 'active', label: 'يعمل بكامل الطاقة' });
        expect(wrapper.text()).toBe('يعمل بكامل الطاقة');
    });

    it('hides the label span entirely when label is set to an empty string', () => {
        const wrapper = mountStatus({ status: 'active', label: '' });
        expect(wrapper.text()).toBe('');
        expect(wrapper.find('.text-xs').exists()).toBe(false);
        // the dot itself must still render — only the text label is suppressed
        expect(wrapper.find('.status-dot-live').exists()).toBe(true);
    });

    it('applies the "sm" dot size class (defaulting to "md" when size is omitted)', () => {
        // findAll('span')[0] is the component's own root <span>; the size-classed dot
        // wrapper is the next one in, findAll('span')[1].
        const small = mountStatus({ status: 'active', size: 'sm' });
        const smallDot = small.findAll('span')[1];
        expect(smallDot.classes()).toEqual(expect.arrayContaining(['w-1.5', 'h-1.5']));

        const defaultSize = mountStatus({ status: 'active' });
        const defaultDot = defaultSize.findAll('span')[1];
        expect(defaultDot.classes()).toEqual(expect.arrayContaining(['w-2', 'h-2']));
    });

    it('applies the "lg" dot size class', () => {
        const wrapper = mountStatus({ status: 'active', size: 'lg' });
        const dot = wrapper.findAll('span')[1];
        expect(dot.classes()).toEqual(expect.arrayContaining(['w-2.5', 'h-2.5']));
    });
});
