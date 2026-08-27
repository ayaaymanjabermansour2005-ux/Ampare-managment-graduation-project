import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionService from "@/services/subscriptionService";
import invoiceService from "@/services/invoiceService";
import roleDashboardService from "@/services/roleDashboardService";
import notificationService from "@/services/notificationService";

export function useSubscriberDashboard() {
  const { t } = useI18n();
  const subscription = ref(null);
  const recentInvoices = ref([]);
  const recentNotifications = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  const activeSubscription = computed(() =>
    subscription.value?.status === "active" ? subscription.value : null,
  );

  const latestInvoice = computed(() => recentInvoices.value[0] ?? null);

  const outstandingTotal = computed(() =>
    recentInvoices.value
      .filter((i) =>
        ["pending", "partially_paid", "overdue"].includes(i.status),
      )
      .reduce((sum, i) => sum + i.remaining_balance_ils, 0),
  );

  async function load() {
    isLoading.value = true;
    error.value = null;

    try {
      const [subsRes, invoicesRes, notificationsRes] = await Promise.all([
        subscriptionService.list({ per_page: 1 }),
        invoiceService.list({ per_page: 5 }),
        notificationService.getAll({ per_page: 5 }),
      ]);

      const subsPayload = subsRes.data.data;
      subscription.value = (subsPayload.data ?? subsPayload)[0] ?? null;

      const invoicesPayload = invoicesRes.data.data;
      recentInvoices.value = invoicesPayload.data ?? invoicesPayload;

      const notificationsPayload = notificationsRes.data;
      recentNotifications.value = notificationsPayload.data ?? notificationsPayload ?? [];
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("owner_dashboard.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const extraStats = ref(null);
  const isLoadingExtraStats = ref(true);
  const extraStatsError = ref(null);

  const generatorStatus = computed(() => extraStats.value?.generator_status ?? null);

  const upcomingSchedule = computed(() => {
    const slot = extraStats.value?.upcoming_schedule;
    if (!slot) return [];
    return [
      {
        label: slot.note || t("subscriber_dashboard.scheduled_period_label"),
        starts_at: slot.starts_at,
        ends_at: slot.ends_at,
      },
    ];
  });

  async function loadExtraStats() {
    isLoadingExtraStats.value = true;
    extraStatsError.value = null;
    try {
      const { data } = await roleDashboardService.subscriberStats();
      extraStats.value = data.data;
    } catch (err) {
      extraStatsError.value =
        err.response?.data?.message ?? t("owner_dashboard.load_extra_stats_error");
    } finally {
      isLoadingExtraStats.value = false;
    }
  }

  return {
    subscription,
    activeSubscription,
    recentInvoices,
    recentNotifications,
    latestInvoice,
    outstandingTotal,
    isLoading,
    error,
    load,
    extraStats,
    isLoadingExtraStats,
    extraStatsError,
    loadExtraStats,
    generatorStatus,
    upcomingSchedule,
  };
}
