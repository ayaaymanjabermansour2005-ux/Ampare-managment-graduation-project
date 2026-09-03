import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/preferenceService', () => ({
    default: {
        index: vi.fn(),
        update: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_settings: {
                notifications: {
                    load_error: 'تعذر تحميل التفضيلات',
                    save_error: 'تعذر حفظ التفضيلات',
                },
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useNotificationSettings, NOTIFICATION_ICONS } = await import('./useNotificationSettings');
const preferenceService = (await import('@/services/preferenceService')).default;

describe('useNotificationSettings', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with empty preferences, idle flags, and no error', () => {
        const { preferences, isLoading, isSaving, error } = useNotificationSettings();

        expect(preferences.value).toEqual({});
        expect(isLoading.value).toBe(false);
        expect(isSaving.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('exports an icon for every notification preference key used elsewhere in the app', () => {
        expect(NOTIFICATION_ICONS.notify_new_message).toBe('fa-comment-dots');
        expect(NOTIFICATION_ICONS.notify_fault_reported).toBe('fa-triangle-exclamation');
        expect(Object.keys(NOTIFICATION_ICONS).length).toBeGreaterThan(0);
    });

    it('fetchPreferences populates preferences on success and clears isLoading', async () => {
        preferenceService.index.mockResolvedValue({ data: { data: { notify_new_message: true, notify_fault_reported: false } } });

        const { fetchPreferences, preferences, isLoading, error } = useNotificationSettings();
        await fetchPreferences();

        expect(preferences.value).toEqual({ notify_new_message: true, notify_fault_reported: false });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('reshapes a fetchPreferences failure into the translated fallback message', async () => {
        preferenceService.index.mockRejectedValue({ message: 'Network Error' });

        const { fetchPreferences, error, isLoading } = useNotificationSettings();
        await fetchPreferences();

        expect(error.value).toBe('تعذر تحميل التفضيلات');
        expect(isLoading.value).toBe(false);
    });

    it('savePreferences forwards the patch, replaces preferences with the server response, and returns true', async () => {
        preferenceService.update.mockResolvedValue({ data: { data: { notify_new_message: false } } });

        const { savePreferences, preferences, isSaving, error } = useNotificationSettings();
        const result = await savePreferences({ notify_new_message: false });

        expect(preferenceService.update).toHaveBeenCalledWith({ notify_new_message: false });
        expect(result).toBe(true);
        expect(preferences.value).toEqual({ notify_new_message: false });
        expect(isSaving.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('reshapes a savePreferences failure into the translated fallback message and returns false', async () => {
        preferenceService.update.mockRejectedValue({ message: 'Network Error' });

        const { savePreferences, error, isSaving } = useNotificationSettings();
        const result = await savePreferences({});

        expect(result).toBe(false);
        expect(error.value).toBe('تعذر حفظ التفضيلات');
        expect(isSaving.value).toBe(false);
    });

    it('notificationKeys() returns only keys prefixed with notify_', async () => {
        preferenceService.index.mockResolvedValue({
            data: { data: { notify_new_message: true, notify_fault_reported: false, some_other_field: 'x' } },
        });

        const { fetchPreferences, notificationKeys } = useNotificationSettings();
        await fetchPreferences();

        expect(notificationKeys()).toEqual(['notify_new_message', 'notify_fault_reported']);
    });

    it('notificationKeys() returns an empty array when preferences are empty', () => {
        const { notificationKeys } = useNotificationSettings();

        expect(notificationKeys()).toEqual([]);
    });
});
