import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import UsersView from './UsersView.vue';

vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/stores/auth', () => ({
    useAuthStore: () => ({ can: () => true, hasRole: () => true }),
}));
vi.mock('@/services/userService', () => ({
    default: {
        list: vi.fn(),
        update: vi.fn(),
        show: vi.fn(),
        destroy: vi.fn(),
        unlock: vi.fn(),
        sendPasswordResetLink: vi.fn(),
        setPassword: vi.fn(),
        createGeneratorOwner: vi.fn(),
        createSubscriber: vi.fn(),
        createTechnician: vi.fn(),
        exportUrl: vi.fn(() => '/export'),
    },
}));
vi.mock('@/services/activityLogService', () => ({
    default: { index: vi.fn().mockResolvedValue({ data: { data: [] } }) },
}));
vi.mock('vue-router', () => ({
    useRoute: () => ({ query: {} }),
}));
const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                edit: 'تعديل', delete: 'حذف', close: 'إغلاق', back: 'رجوع', all: '', this_page_suffix: '',
                role_admin: '', role_owner: 'مالك مولد', role_subscriber: 'مشترك', role_technician: 'فني',
                show_password: '', hide_password: '', copy_password: '', unexpected_error_retry: 'حدث خطأ غير متوقع، حاول مرة أخرى',
                loading: '', previous_page: '', next_page: '',
            },
            menu: { roles_permissions: '' },
            roles_permissions_page: { subtitle: '' },
            dashboard: { name: '', password: '', confirm_password: '', cancel: 'إلغاء' },
            users_page: {
                title: '', subtitle: '', all_statuses: '', status_active: 'نشط', status_inactive: '', status_suspended: '', status_pending_review: '',
                total_users: '', locked_this_page: '',
                view_details: 'عرض التفاصيل', unlock_action: 'إلغاء القفل',
                delete_user_title: 'حذف المستخدم', delete_user_message: '',
                deleted_toast_title: 'تم الحذف', deleted_message: '',
                unlocked_toast_title: 'تم إلغاء القفل', unlocked_message: '',
                send_reset_link_title: '', send_reset_link_message: '', send_action: 'إرسال',
                sent_toast_title: '', sent_message: '',
                send_failed_title: 'فشل الإرسال', try_again_later: 'تعذر الإرسال، حاول لاحقًا',
                send_reset_link_hint: 'إرسال رابط إعادة التعيين', set_password_hint: 'تعيين كلمة سر',
                set_password_for: '', set_password_desc: '', temp_password_label: '', generate_new_password: '',
                setting_ellipsis: '', set_action: 'تعيين', password_set_success: '', sessions_logged_out: '',
                edit_user_title: '', email_label: 'البريد الإلكتروني', phone_label: 'الهاتف', account_status_label: '',
                saving_ellipsis: '', save_action: 'حفظ', saved_toast_title: '', user_updated_message: '',
                choose_account_type: '', option_owner_label: 'مالك مولد', option_owner_desc: '',
                option_subscriber_desc: '', option_technician_desc: '',
                add_user_title: '', add_owner_title: '', add_subscriber_title: '', add_technician_title: '',
                name_placeholder_example: '', phone_optional_label: '', address_optional_label: '',
                generate_random_password: '', create_action: 'إنشاء', creating_ellipsis: '',
                add_button: 'إضافة مستخدم',
                created_toast_title: 'تم الإنشاء', owner_created_message: '', subscriber_created_message: '', technician_created_message: '',
                owner_field_label: '', technician_name_label: 'اسم الفني', notes_optional_label: '', select_owner_placeholder: '',
                technician_form_hint: '', no_locked_users: '', locked_label: '', auto_unlocks_line: '',
                recent_activity: '', no_activity: '', whatsapp_label: '', birth_date_label: '', address_label: '',
                joined_label: '', bio_label: '', generators_label: '', outstanding_balance_label: '',
                auto_unlocks_label: '', lockout_count_label: '', system_label: '', pagination_text: '',
            },
        },
    },
});

function sampleUsers() {
    return [
        { id: 1, name: 'محمد', email: 'm@example.com', is_locked: false, status: 'active', status_label: 'نشط', roles: [{ name: 'subscriber' }] },
        { id: 2, name: 'أحمد', email: 'a@example.com', is_locked: true, locked_until: null, status: 'active', status_label: 'نشط', roles: [{ name: 'subscriber' }] },
    ];
}

async function mountComponent() {
    const userService = (await import('@/services/userService')).default;
    userService.list.mockResolvedValue({
        data: { data: { data: sampleUsers(), meta: { current_page: 1, last_page: 1, total: 2, per_page: 15 } } },
    });

    const pinia = createPinia();
    setActivePinia(pinia);

    const wrapper = mount(UsersView, {
        global: {
            plugins: [i18n, pinia],
            stubs: { AppDropdownSelect: true, AppIcon: true, Teleport: true },
        },
    });
    await flushPromises();
    return wrapper;
}

describe('UsersView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        confirmMock.mockResolvedValue(true);
    });

    describe('handleDelete — field-level "user" error takes priority', () => {
        it('does nothing when the confirmation dialog is dismissed', async () => {
            confirmMock.mockResolvedValue(false);
            const userService = (await import('@/services/userService')).default;

            const wrapper = await mountComponent();
            await wrapper.find('[aria-label="حذف"]').trigger('click');
            await flushPromises();

            expect(userService.destroy).not.toHaveBeenCalled();
        });

        it('prioritizes errors.user over the generic message in the danger toast', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.destroy.mockRejectedValue({
                response: {
                    status: 422,
                    data: { message: 'بيانات غير صالحة', errors: { user: ['لا يمكن حذف مستخدم لديه بيانات مرتبطة.'] } },
                },
            });

            const wrapper = await mountComponent();
            const { useToastStore } = await import('@/stores/toast');
            await wrapper.find('[aria-label="حذف"]').trigger('click');
            await flushPromises();

            const toasts = useToastStore().toasts;
            expect(toasts).toHaveLength(1);
            expect(toasts[0]).toMatchObject({ type: 'danger', message: 'لا يمكن حذف مستخدم لديه بيانات مرتبطة.' });
        });

        it('falls back to the translated generic message on a network error', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.destroy.mockRejectedValue({ message: 'Network Error' });

            const wrapper = await mountComponent();
            const { useToastStore } = await import('@/stores/toast');
            await wrapper.find('[aria-label="حذف"]').trigger('click');
            await flushPromises();

            expect(useToastStore().toasts[0].message).toBe('حدث خطأ غير متوقع، حاول مرة أخرى');
        });
    });

    describe('handleUnlock', () => {
        it('shows a danger toast with the normalized message on failure', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.unlock.mockRejectedValue({
                response: { status: 500, data: { message: 'تعذر إلغاء القفل فعليًا.' } },
            });

            const wrapper = await mountComponent();
            const { useToastStore } = await import('@/stores/toast');
            // Only the locked user (id 2) renders an unlock button.
            await wrapper.find('[aria-label="إلغاء القفل"]').trigger('click');
            await flushPromises();

            expect(useToastStore().toasts[0]).toMatchObject({ type: 'danger', message: 'تعذر إلغاء القفل فعليًا.' });
        });
    });

    describe('handleSendResetLink (from the user-details modal)', () => {
        async function openViewModal(wrapper) {
            const userService = (await import('@/services/userService')).default;
            userService.show.mockResolvedValue({ data: { data: { id: 1, name: 'محمد', email: 'm@example.com' } } });
            await wrapper.find('[aria-label="عرض التفاصيل"]').trigger('click');
            await flushPromises();
        }

        it('does nothing when the confirmation dialog is dismissed', async () => {
            const userService = (await import('@/services/userService')).default;
            const wrapper = await mountComponent();
            await openViewModal(wrapper);
            confirmMock.mockResolvedValue(false);

            await wrapper.find('[title="إرسال رابط إعادة التعيين"]').trigger('click');
            await flushPromises();

            expect(userService.sendPasswordResetLink).not.toHaveBeenCalled();
        });

        it('shows a danger toast with the normalized message on failure', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.sendPasswordResetLink.mockRejectedValue({
                response: { status: 500, data: { message: 'فشل الإرسال الفعلي.' } },
            });

            const wrapper = await mountComponent();
            await openViewModal(wrapper);
            const { useToastStore } = await import('@/stores/toast');
            await wrapper.find('[title="إرسال رابط إعادة التعيين"]').trigger('click');
            await flushPromises();

            expect(useToastStore().toasts[0]).toMatchObject({ type: 'danger', message: 'فشل الإرسال الفعلي.' });
        });

        it('falls back to the translated generic message on a network error', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.sendPasswordResetLink.mockRejectedValue({ message: 'Network Error' });

            const wrapper = await mountComponent();
            await openViewModal(wrapper);
            const { useToastStore } = await import('@/stores/toast');
            await wrapper.find('[title="إرسال رابط إعادة التعيين"]').trigger('click');
            await flushPromises();

            expect(useToastStore().toasts[0].message).toBe('تعذر الإرسال، حاول لاحقًا');
        });
    });

    describe('handleSetPassword — null-vs-{} distinction: only a field-level "password" error is ever rendered', () => {
        async function openSetPasswordModal(wrapper) {
            const userService = (await import('@/services/userService')).default;
            userService.show.mockResolvedValue({ data: { data: { id: 1, name: 'محمد', email: 'm@example.com' } } });
            await wrapper.find('[aria-label="عرض التفاصيل"]').trigger('click');
            await flushPromises();
            await wrapper.find('[title="تعيين كلمة سر"]').trigger('click');
            await flushPromises();
        }

        it('shows the field-level password error when present', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.setPassword.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { password: ['كلمة السر ضعيفة جدًا.'] } } },
            });

            const wrapper = await mountComponent();
            await openSetPasswordModal(wrapper);
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(wrapper.text()).toContain('كلمة السر ضعيفة جدًا.');
        });

        it('shows no error text for a non-field failure, and re-enables the submit button', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.setPassword.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ داخلي في الخادم.' } },
            });

            const wrapper = await mountComponent();
            await openSetPasswordModal(wrapper);
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            // This view only ever reads setPasswordErrors?.password — a
            // non-field error (500/network) is normalized but has nowhere to
            // render, by design (current, real behavior, not a bug this
            // migration was scoped to change).
            expect(wrapper.text()).not.toContain('خطأ داخلي في الخادم.');
            const submitBtn = wrapper.find('button[type="submit"]');
            expect(submitBtn.attributes('disabled')).toBeUndefined();
        });
    });

    describe('handleSave (edit user) — per-field errors', () => {
        async function openEditModal(wrapper) {
            await wrapper.find('[aria-label="تعديل"]').trigger('click');
        }

        it('shows the field-level email error under the email field', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { email: ['البريد مستخدم مسبقًا.'] } } },
            });

            const wrapper = await mountComponent();
            await openEditModal(wrapper);
            const saveBtn = wrapper.findAll('button').find((b) => b.text() === 'حفظ');
            await saveBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('البريد مستخدم مسبقًا.');
        });

        it('shows no field error text for a non-field failure', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.update.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم أثناء الحفظ.' } },
            });

            const wrapper = await mountComponent();
            await openEditModal(wrapper);
            const saveBtn = wrapper.findAll('button').find((b) => b.text() === 'حفظ');
            await saveBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).not.toContain('خطأ في الخادم أثناء الحفظ.');
        });
    });

    describe('handleCreateOwner/Subscriber/Technician — per-field errors', () => {
        async function openAddUserModal(wrapper, optionLabel) {
            const addBtn = wrapper.findAll('button').find((b) => b.text() === 'إضافة مستخدم');
            await addBtn.trigger('click');
            await flushPromises();
            // Scoped to the choice-step option buttons specifically: the
            // source reuses common.role_subscriber/role_technician for both
            // these options AND the unrelated role-filter pills elsewhere on
            // the page, so matching by text alone across the whole wrapper
            // would hit the wrong button.
            const option = wrapper.findAll('.add-user-option-btn').find((b) => b.text().includes(optionLabel));
            await option.trigger('click');
            await flushPromises();
        }

        it('handleCreateOwner shows the field-level email error', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.createGeneratorOwner.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } } },
            });

            const wrapper = await mountComponent();
            await openAddUserModal(wrapper, 'مالك مولد');

            await wrapper.find('input[type="email"]').setValue('taken@example.com');
            await wrapper.find('input[type="text"]').setValue('Owner Name');
            const passwordInputs = wrapper.findAll('input[type="password"]');
            await passwordInputs[0].setValue('SuperSecret123');
            await passwordInputs[1].setValue('SuperSecret123');
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(wrapper.text()).toContain('البريد مستخدم.');
        });

        it('handleCreateOwner reshapes a network error with no field errors (form stays open, no field text shown)', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.createGeneratorOwner.mockRejectedValue({ message: 'Network Error' });

            const wrapper = await mountComponent();
            await openAddUserModal(wrapper, 'مالك مولد');

            await wrapper.find('input[type="email"]').setValue('owner@example.com');
            await wrapper.find('input[type="text"]').setValue('Owner Name');
            const passwordInputs = wrapper.findAll('input[type="password"]');
            await passwordInputs[0].setValue('SuperSecret123');
            await passwordInputs[1].setValue('SuperSecret123');
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(userService.createGeneratorOwner).toHaveBeenCalledTimes(1);
            // Still on the owner form (modal not closed by closeAddUserForms()).
            expect(wrapper.find('input[type="email"]').exists()).toBe(true);
        });

        it('handleCreateSubscriber shows the field-level phone error', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.createSubscriber.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { phone: ['رقم الهاتف غير صالح.'] } } },
            });

            const wrapper = await mountComponent();
            await openAddUserModal(wrapper, 'مشترك');

            await wrapper.find('input[type="email"]').setValue('sub@example.com');
            await wrapper.find('input[type="text"]').setValue('Subscriber Name');
            const passwordInputs = wrapper.findAll('input[type="password"]');
            await passwordInputs[0].setValue('SuperSecret123');
            await passwordInputs[1].setValue('SuperSecret123');
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(wrapper.text()).toContain('رقم الهاتف غير صالح.');
        });

        it('handleCreateTechnician shows the field-level name error', async () => {
            const userService = (await import('@/services/userService')).default;
            userService.createTechnician.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { name: ['الاسم مطلوب.'] } } },
            });

            const wrapper = await mountComponent();
            await openAddUserModal(wrapper, 'فني');

            await wrapper.find('input[type="email"]').setValue('tech@example.com');
            await wrapper.find('input[type="text"]').setValue('Tech Name');
            const passwordInputs = wrapper.findAll('input[type="password"]');
            await passwordInputs[0].setValue('SuperSecret123');
            await passwordInputs[1].setValue('SuperSecret123');
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(wrapper.text()).toContain('الاسم مطلوب.');
        });
    });
});
