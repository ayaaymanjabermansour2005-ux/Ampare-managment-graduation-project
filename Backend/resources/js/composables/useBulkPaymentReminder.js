import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import { useConfirm } from "@/composables/useConfirm";

/**
 * تذكير جماعي بالدفع للمشتركين المتأخرين — منقولة من SubscribersView.vue
 * (FRONT-004a slice 4، God-component breakdown).
 *
 * متّصلة فعليًا بـ POST /users/bulk-payment-reminder (تعيد استخدام نفس بنية
 * الإشعارات الموجودة: حدث InvoiceDueSoon + بوابة تفضيلات الإشعارات) — راجع
 * SendBulkPaymentReminderAction بالباك اند للتفاصيل الكاملة.
 */
export function useBulkPaymentReminder({ subscribers, subscriberStatus }) {
  const { t } = useI18n();
  const { confirm } = useConfirm();

  const isSendingBulkReminder = ref(false);

  async function handleBulkReminder() {
    const dueSubscriberIds = subscribers.value
      .filter((s) => subscriberStatus(s) === "overdue")
      .map((s) => s.id);

    if (dueSubscriberIds.length === 0) {
      await confirm({
        title: t("subscribers_page.no_overdue_subscribers_title"),
        message: t("subscribers_page.no_overdue_subscribers_message"),
        confirmLabel: t("subscribers_page.ok_action"),
        hideCancel: true,
      });
      return;
    }

    const confirmed = await confirm({
      title: t("subscribers_page.bulk_reminder_title"),
      message: t("subscribers_page.bulk_reminder_message", { count: dueSubscriberIds.length }),
      confirmLabel: t("users_page.send_action"),
    });
    if (!confirmed) return;

    isSendingBulkReminder.value = true;
    try {
      const { data } = await userService.sendBulkPaymentReminder(dueSubscriberIds);
      await confirm({
        title: t("users_page.sent_toast_title"),
        message: data?.message ?? t("subscribers_page.reminders_sent_message"),
        confirmLabel: t("subscribers_page.ok_action"),
        hideCancel: true,
      });
    } catch (err) {
      await confirm({
        title: t("users_page.send_failed_title"),
        message: normalizeApiError(err, t("subscribers_page.reminder_send_failed_message")).message,
        confirmLabel: t("subscribers_page.ok_action"),
        hideCancel: true,
      });
    } finally {
      isSendingBulkReminder.value = false;
    }
  }

  return {
    isSendingBulkReminder,
    handleBulkReminder,
  };
}
