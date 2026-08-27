import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionService from "@/services/subscriptionService";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";

const ALLOWED_TRANSITIONS = {
  pending: ["active", "rejected"],
  active: ["suspended", "cancelled"],
  suspended: ["active", "cancelled"],
  cancelled: [],
  rejected: [],
};

export function useOwnerSubscribers() {
  const { t } = useI18n();
  const subscriptions = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");
  const generatorFilter = ref("");

  const pendingCount = computed(
    () => subscriptions.value.filter((s) => s.status === "pending").length,
  );

  async function fetchSubscriptions(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await subscriptionService.list({
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        generator_id: generatorFilter.value || undefined,
      });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      subscriptions.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? subscriptions.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("owner_subscribers.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchSubscriptions(1), 300);
  }

  function onFilterChange() {
    fetchSubscriptions(1);
  }

  function onGeneratorFilterChange() {
    fetchSubscriptions(1);
  }

  function availableActions(currentStatus) {
    return ALLOWED_TRANSITIONS[currentStatus] ?? [];
  }

  const updatingId = ref(null);
  const updateError = ref(null);

  async function updateStatus(id, newStatus) {
    updatingId.value = id;
    updateError.value = null;

    try {
      const { data } = await subscriptionService.updateStatus(id, newStatus);
      const index = subscriptions.value.findIndex((s) => s.id === id);
      if (index !== -1) subscriptions.value[index] = data.data;
      return true;
    } catch (err) {
      updateError.value =
        err.response?.data?.message ?? t("owner_subscribers.update_status_error");
      return false;
    } finally {
      updatingId.value = null;
    }
  }

  const myGenerators = ref([]);
  const isLoadingGenerators = ref(false);
  async function fetchMyGenerators() {
    isLoadingGenerators.value = true;
    try {
      const { data } = await generatorService.list();
      const payload = data.data;
      myGenerators.value = payload.data ?? payload;
    } catch {
      myGenerators.value = [];
    } finally {
      isLoadingGenerators.value = false;
    }
  }

  /* ---------------- نقل اشتراك لمولد تاني (owner-scoped) ---------------- */
  // ملاحظة: subscriptionService.transfer()/userService.sendBulkPaymentReminder()
  // القديمتان تستدعيان مسارات أدمن-فقط/مشترك-فقط وترجعان 403 دائمًا لمالك
  // المولد — لذلك تستخدم هذه الدوال الآن المسارات المخصَّصة للمالك
  // (transferByOwner / sendOwnerBulkPaymentReminder).
  const transferringId = ref(null);
  const transferError = ref(null);
  async function transferSubscription(id, generatorId) {
    transferringId.value = id;
    transferError.value = null;
    try {
      const { data } = await subscriptionService.transferByOwner(id, generatorId);
      const index = subscriptions.value.findIndex((s) => s.id === id);
      if (index !== -1) subscriptions.value[index] = data.data;
      return true;
    } catch (err) {
      transferError.value =
        err.response?.data?.message ?? t("owner_subscribers.transfer_error");
      return false;
    } finally {
      transferringId.value = null;
    }
  }

  /* ---------------- تذكير دفع جماعي للمتأخرين (owner-scoped) ---------------- */
  const isSendingBulkReminder = ref(false);
  const bulkReminderError = ref(null);
  async function sendBulkReminder(subscriberIds) {
    isSendingBulkReminder.value = true;
    bulkReminderError.value = null;
    try {
      await userService.sendOwnerBulkPaymentReminder(subscriberIds);
      return true;
    } catch (err) {
      bulkReminderError.value =
        err.response?.data?.message ?? t("owner_subscribers.bulk_reminder_error");
      return false;
    } finally {
      isSendingBulkReminder.value = false;
    }
  }

  /* ---------------- إضافة اشتراك جديد لمشترك موجود (owner-scoped) ---------------- */
  const subscriberLookupResults = ref([]);
  const isSearchingSubscribers = ref(false);
  async function searchOwnerSubscribers(query) {
    if (!query || !query.trim()) {
      subscriberLookupResults.value = [];
      return;
    }
    isSearchingSubscribers.value = true;
    try {
      const { data } = await userService.ownerSubscriberLookup(query.trim());
      subscriberLookupResults.value = data.data ?? [];
    } catch {
      subscriberLookupResults.value = [];
    } finally {
      isSearchingSubscribers.value = false;
    }
  }

  const subscriberMeters = ref([]);
  const isLoadingSubscriberMeters = ref(false);
  async function fetchSubscriberMeters(userId) {
    isLoadingSubscriberMeters.value = true;
    subscriberMeters.value = [];
    try {
      const { data } = await userService.ownerSubscriberMeters(userId);
      subscriberMeters.value = data.data ?? [];
    } catch {
      subscriberMeters.value = [];
    } finally {
      isLoadingSubscriberMeters.value = false;
    }
  }

  const isCreatingSubscription = ref(false);
  const createSubscriptionError = ref(null);
  async function createSubscriptionForOwner(payload) {
    isCreatingSubscription.value = true;
    createSubscriptionError.value = null;
    try {
      const { data } = await subscriptionService.createByOwner(payload);
      subscriptions.value.unshift(data.data);
      pagination.value.total += 1;
      return data.data;
    } catch (err) {
      createSubscriptionError.value =
        err.response?.data?.message ?? t("owner_subscribers.subscription_create_failed");
      return null;
    } finally {
      isCreatingSubscription.value = false;
    }
  }

  const savingNotesId = ref(null);
  const notesError = ref(null);
  async function updateSubscriptionNotes(id, notes) {
    savingNotesId.value = id;
    notesError.value = null;
    try {
      const { data } = await subscriptionService.updateNotes(id, notes);
      const index = subscriptions.value.findIndex((s) => s.id === id);
      if (index !== -1) subscriptions.value[index] = data.data;
      return true;
    } catch (err) {
      notesError.value =
        err.response?.data?.message ?? t("owner_subscribers.notes_error");
      return false;
    } finally {
      savingNotesId.value = null;
    }
  }

  return {
    subscriptions,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    generatorFilter,
    pendingCount,
    fetchSubscriptions,
    onSearchInput,
    onFilterChange,
    onGeneratorFilterChange,
    availableActions,
    updatingId,
    updateError,
    updateStatus,
    myGenerators,
    isLoadingGenerators,
    fetchMyGenerators,
    transferringId,
    transferError,
    transferSubscription,
    isSendingBulkReminder,
    bulkReminderError,
    sendBulkReminder,
    savingNotesId,
    notesError,
    updateSubscriptionNotes,
    subscriberLookupResults,
    isSearchingSubscribers,
    searchOwnerSubscribers,
    subscriberMeters,
    isLoadingSubscriberMeters,
    fetchSubscriberMeters,
    isCreatingSubscription,
    createSubscriptionError,
    createSubscriptionForOwner,
  };
}