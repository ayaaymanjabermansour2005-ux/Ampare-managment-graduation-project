import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import preferenceService from "@/services/preferenceService";

// الأيقونات فقط — النصوص تُترجم عبر owner_settings.notifications.<key> بالمكوّن المستخدِم.
export const NOTIFICATION_ICONS = {
  notify_new_message: "fa-comment-dots",
  notify_fault_reported: "fa-triangle-exclamation",
  notify_fuel_stock_low: "fa-gas-pump",
  notify_generator_health_report: "fa-heart-pulse",
  notify_payment_submitted: "fa-wallet",
  notify_technician_task_submitted: "fa-list-check",
  notify_invoice_paid: "fa-file-invoice",
  notify_generator_verified: "fa-circle-check",
  notify_generator_rejected: "fa-circle-xmark",
  notify_complaint_resolved: "fa-check-double",
  notify_generator_schedule_announced: "fa-clock",
  notify_subscription_approved: "fa-file-circle-check",
  notify_invoice_due_soon: "fa-hourglass-half",
  notify_technician_task_assigned: "fa-clipboard-list",
  notify_technician_task_approved: "fa-thumbs-up",
  notify_technician_task_rejected: "fa-thumbs-down",
};

export function useNotificationSettings() {
  const { t } = useI18n();
  const preferences = ref({});
  const isLoading = ref(false);
  const isSaving = ref(false);
  const error = ref(null);

  async function fetchPreferences() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await preferenceService.index();
      preferences.value = data.data;
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_settings.notifications.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  async function savePreferences(patch) {
    isSaving.value = true;
    error.value = null;
    try {
      const { data } = await preferenceService.update(patch);
      preferences.value = data.data;
      return true;
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_settings.notifications.save_error")).message;
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  function notificationKeys() {
    return Object.keys(preferences.value).filter((k) => k.startsWith("notify_"));
  }

  return {
    preferences,
    isLoading,
    isSaving,
    error,
    fetchPreferences,
    savePreferences,
    notificationKeys,
  };
}