import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AuthSelect from './AuthSelect.vue';

const OPTIONS = [
    { value: 'usd', label: 'دولار أمريكي' },
    { value: 'ils', label: 'شيقل' },
];

function mountSelect(props = {}) {
    return mount(AuthSelect, {
        props: { options: OPTIONS, ...props },
    });
}

describe('AuthSelect', () => {
    it('shows the placeholder when modelValue matches no option', () => {
        const wrapper = mountSelect({ modelValue: '', placeholder: 'اختر العملة' });

        expect(wrapper.text()).toContain('اختر العملة');
        expect(wrapper.find('.auth-select-label').classes()).toContain('is-placeholder');
    });

    it('shows the matching option label when modelValue matches an option value', () => {
        const wrapper = mountSelect({ modelValue: 'ils' });

        expect(wrapper.find('.auth-select-label').text()).toBe('شيقل');
        expect(wrapper.find('.auth-select-label').classes()).not.toContain('is-placeholder');
    });

    it('clicking the trigger opens the options panel', async () => {
        const wrapper = mountSelect({ modelValue: '' });

        expect(wrapper.find('.auth-select-panel').exists()).toBe(false);

        await wrapper.find('.auth-select-trigger').trigger('click');

        expect(wrapper.find('.auth-select-panel').exists()).toBe(true);
        expect(wrapper.findAll('.auth-select-option')).toHaveLength(2);
        expect(wrapper.find('.auth-select-trigger').classes()).toContain('is-open');
    });

    it('clicking an option emits update:modelValue with its value and closes the panel', async () => {
        const wrapper = mountSelect({ modelValue: '' });
        await wrapper.find('.auth-select-trigger').trigger('click');

        await wrapper.findAll('.auth-select-option')[1].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([['ils']]);
        expect(wrapper.find('.auth-select-panel').exists()).toBe(false);
    });

    it('marks the currently selected option with is-selected', async () => {
        const wrapper = mountSelect({ modelValue: 'usd' });
        await wrapper.find('.auth-select-trigger').trigger('click');

        const options = wrapper.findAll('.auth-select-option');
        expect(options[0].classes()).toContain('is-selected');
        expect(options[1].classes()).not.toContain('is-selected');
    });

    it('does not open the panel when disabled', async () => {
        const wrapper = mountSelect({ disabled: true });

        await wrapper.find('.auth-select-trigger').trigger('click');

        expect(wrapper.find('.auth-select-panel').exists()).toBe(false);
        expect(wrapper.find('.auth-select-trigger').classes()).toContain('is-disabled');
        expect(wrapper.find('.auth-select-trigger').attributes('disabled')).toBeDefined();
    });

    it('applies the is-invalid class when invalid is true', () => {
        const wrapper = mountSelect({ invalid: true });

        expect(wrapper.find('.auth-select-trigger').classes()).toContain('is-invalid');
    });

    it('closes the open panel when a click happens outside the component', async () => {
        const wrapper = mountSelect({ modelValue: '' });
        await wrapper.find('.auth-select-trigger').trigger('click');
        expect(wrapper.find('.auth-select-panel').exists()).toBe(true);

        document.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.auth-select-panel').exists()).toBe(false);
    });
});
