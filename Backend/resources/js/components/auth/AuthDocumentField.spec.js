import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AuthDocumentField from './AuthDocumentField.vue';
import AppIcon from '@/components/ui/AppIcon.vue';

function mountField(props = {}) {
    return mount(AuthDocumentField, {
        props: { label: 'الهوية الشخصية', ...props },
    });
}

function fileInputChange(wrapper, file) {
    const input = wrapper.find('input[type="file"]');
    Object.defineProperty(input.element, 'files', { value: file ? [file] : [], configurable: true });
    return input.trigger('change');
}

describe('AuthDocumentField', () => {
    it('renders the label, hint and default icon when no file is attached', () => {
        const wrapper = mountField({ hint: 'PDF أو صورة', icon: 'fa-id-card' });

        expect(wrapper.find('.auth-doc-card-label').text()).toBe('الهوية الشخصية');
        expect(wrapper.find('.auth-doc-card-hint').text()).toBe('PDF أو صورة');
        expect(wrapper.findComponent(AppIcon).props('name')).toBe('fa-id-card');
        expect(wrapper.find('.auth-doc-card-remove').exists()).toBe(false);
    });

    it('shows a required asterisk only when required is true', () => {
        const withRequired = mountField({ required: true });
        const withoutRequired = mountField({ required: false });

        expect(withRequired.text()).toContain('*');
        expect(withoutRequired.find('.text-danger').exists()).toBe(false);
    });

    it('emits select with the chosen file when a file is picked', async () => {
        const wrapper = mountField();
        const file = new File(['content'], 'id-card.png', { type: 'image/png' });

        await fileInputChange(wrapper, file);

        expect(wrapper.emitted('select')).toHaveLength(1);
        expect(wrapper.emitted('select')[0][0]).toBe(file);
    });

    it('does not emit select when the change event carries no file', async () => {
        const wrapper = mountField();

        await fileInputChange(wrapper, null);

        expect(wrapper.emitted('select')).toBeUndefined();
    });

    it('switches to the filename view and a PDF icon when a .pdf file is attached', () => {
        const file = new File(['content'], 'contract.pdf', { type: 'application/pdf' });
        const wrapper = mountField({ file });

        expect(wrapper.find('.auth-doc-card-filename').text()).toBe('contract.pdf');
        expect(wrapper.find('.auth-doc-card-label').exists()).toBe(false);
        expect(wrapper.findComponent(AppIcon).props('name')).toBe('fa-file-pdf');
        expect(wrapper.find('.auth-doc-card-remove').exists()).toBe(true);
    });

    it('uses a generic image icon for a non-pdf attached file', () => {
        const file = new File(['content'], 'selfie.jpg', { type: 'image/jpeg' });
        const wrapper = mountField({ file });

        expect(wrapper.findComponent(AppIcon).props('name')).toBe('fa-file-image');
    });

    it('emits remove when the remove button is clicked', async () => {
        const file = new File(['content'], 'id.png', { type: 'image/png' });
        const wrapper = mountField({ file, removeLabel: 'إزالة الملف' });

        await wrapper.find('.auth-doc-card-remove').trigger('click');

        expect(wrapper.emitted('remove')).toHaveLength(1);
        expect(wrapper.find('.auth-doc-card-remove').attributes('aria-label')).toBe('إزالة الملف');
    });

    it('shows the error message and is-error class when error is set', () => {
        const wrapper = mountField({ error: 'الملف مطلوب' });

        expect(wrapper.find('.auth-field-error').text()).toBe('الملف مطلوب');
        expect(wrapper.find('.auth-doc-card').classes()).toContain('is-error');
    });
});
