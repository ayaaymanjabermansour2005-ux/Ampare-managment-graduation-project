import { ref } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionMeterTransferService from "@/services/subscriptionMeterTransferService";

/**
 * useOwnerSubscriptionMeterTransfers — لائحة طلبات نقل عدادات الاشتراكات
 * المرتبطة بمولدات المالك (أو كل الطلبات للأدمن)، مع إجراءات الموافقة/الرفض.
 * الموافقة تُنفّذ النقل الفعلي على الاشتراك من طرف السيرفر (transaction واحدة).
 */
export function useOwnerSubscriptionMeterTransfers() {
  const { t } = useI18n();
  const requests = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const statusFilter = ref("pending");

  const isApproving = ref(false);
  const approveError = ref(null);

  const isRejecting = ref(false);
  const rejectError = ref(null);

  async function fetchRequests(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await subscriptionMeterTransferService.list({
        page,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      requests.value = payload.data ?? payload;
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
        total: payload.total ?? requests.value.length,
        per_page: payload.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_subscribers.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  function onFilterChange() {
    fetchRequests(1);
  }

  function replaceInList(updated) {
    const index = requests.value.findIndex((r) => r.id === updated.id);
    if (index === -1) return;
    // لو انتقلت من "pending" لحالة أخرى وقائمتنا مفلترة على pending، بلشها.
    if (statusFilter.value === "pending" && updated.status !== "pending") {
      requests.value.splice(index, 1);
    } else {
      requests.value[index] = updated;
    }
  }

  async function approveRequest(id) {
    isApproving.value = true;
    approveError.value = null;
    try {
      const { data } = await subscriptionMeterTransferService.approve(id);
      replaceInList(data.data);
      return data.data;
    } catch (err) {
      approveError.value = err.response?.data?.message ?? t("owner_subscribers.transfer_error");
      return null;
    } finally {
      isApproving.value = false;
    }
  }

  async function rejectRequest(id, rejectionReason) {
    isRejecting.value = true;
    rejectError.value = null;
    try {
      const { data } = await subscriptionMeterTransferService.reject(id, rejectionReason);
      replaceInList(data.data);
      return data.data;
    } catch (err) {
      rejectError.value = err.response?.data?.message ?? t("owner_subscribers.transfer_error");
      return null;
    } finally {
      isRejecting.value = false;
    }
  }

  return {
    requests,
    pagination,
    isLoading,
    error,
    statusFilter,
    isApproving,
    approveError,
    isRejecting,
    rejectError,
    fetchRequests,
    onFilterChange,
    approveRequest,
    rejectRequest,
  };
}
