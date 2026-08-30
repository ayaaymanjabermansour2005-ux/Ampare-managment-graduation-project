import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import ownerApplicationService from "@/services/ownerApplicationService";
import activityLogService from "@/services/activityLogService";

/**
 * منطق تبويب "طلبات الانضمام" (owner applications) — مساعدات العرض، بطاقات
 * KPI، تنبيهات اللوحة، اعتماد/رفض (فردي وجماعي)، اختيار جماعي، عارض
 * المستندات، لوحة التفاصيل + سجل المراجعة، الملاحظات الداخلية، ورابط
 * التصدير — منقولة من GeneratorOwnersView.vue (FRONT-004b slice 3،
 * God-component breakdown، أكبر شريحة نظرًا لحجم هذا التبويب).
 */
export function useOwnerApplicationsUI({
  applications,
  statusCounts,
  reviewingId,
  isBulkProcessing,
  approveApplication,
  rejectApplication,
  bulkApproveApplications,
  bulkRejectApplications,
  updateInternalNote,
  searchQuery,
  applicationStatusFilter,
  applicationsSortBy,
  dateFrom,
  dateTo,
  confirm,
  toast,
  router,
}) {
  const { t, locale } = useI18n();

  /* ---------------- مساعدات عرض ---------------- */
  const APPLICATION_STATUS_PILLS = computed(() => [
    { value: "pending", label: t("owner_applications_page.status_pending"), count: statusCounts.value?.pending ?? 0 },
    { value: "approved", label: t("owner_applications_page.status_approved"), count: statusCounts.value?.approved ?? 0 },
    { value: "rejected", label: t("owner_applications_page.status_rejected"), count: statusCounts.value?.rejected ?? 0 },
    { value: "", label: t("common.all"), count: statusCounts.value?.all ?? 0 },
  ]);

  const SORT_OPTIONS = computed(() => [
    { value: "created_desc", label: t("owner_applications_page.sort_newest") },
    { value: "created_asc", label: t("owner_applications_page.sort_oldest") },
    { value: "name_asc", label: t("owner_applications_page.sort_name_az") },
  ]);

  const STATUS_CHIP = {
    pending: "chip-info",
    approved: "chip-success",
    rejected: "chip-danger",
  };
  const STATUS_ICON = {
    pending: "fa-hourglass-half",
    approved: "fa-circle-check",
    rejected: "fa-circle-xmark",
  };

  const CURRENCY_SYMBOL = { ILS: "₪", USD: "$" };
  function fmtGeneratorPrice(draft) {
    if (!draft || draft.price_per_kw === null || draft.price_per_kw === undefined) return "-";
    const symbol = CURRENCY_SYMBOL[draft.currency] ?? draft.currency ?? "";
    return `${symbol} ${Number(draft.price_per_kw).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US")}`;
  }
  function generatorLocationLabel(draft) {
    if (!draft) return "-";
    const parts = [draft.neighborhood?.name, draft.city].filter(Boolean);
    return parts.length ? parts.join(" - ") : "-";
  }

  /* ---------------- SLA / تكرار ---------------- */
  const SLA_DAYS = 3;
  function daysSince(dateStr) {
    if (!dateStr) return 0;
    const diffMs = Date.now() - new Date(dateStr.replace(" ", "T")).getTime();
    return Math.max(0, Math.floor(diffMs / 86400000));
  }
  function isOverdue(app) {
    return app.status === "pending" && daysSince(app.created_at) >= SLA_DAYS;
  }

  const overdueCountOnPage = computed(() => applications.value.filter(isOverdue).length);
  const duplicateCountOnPage = computed(
    () => applications.value.filter((a) => a.is_duplicate_email || a.is_duplicate_phone).length,
  );
  const approvalRate = computed(() => {
    const approved = statusCounts.value?.approved ?? 0;
    const rejected = statusCounts.value?.rejected ?? 0;
    const total = approved + rejected;
    if (!total) return null;
    return Math.round((approved / total) * 100);
  });

  /* ---------------- بطاقات KPI ---------------- */
  const APPLICATION_KPI_CARDS = computed(() => [
    {
      icon: "fa-inbox",
      label: t("owner_applications_page.total_requests"),
      value: statusCounts.value?.all ?? 0,
      sub: t("owner_applications_page.approved_rejected_summary", { approved: statusCounts.value?.approved ?? 0, rejected: statusCounts.value?.rejected ?? 0 }),
      c1: "#52733D",
      c2: "#3E582E",
    },
    {
      icon: "fa-hourglass-half",
      label: t("users_page.status_pending_review"),
      value: statusCounts.value?.pending ?? 0,
      sub: statusCounts.value?.all
        ? `${Math.round(((statusCounts.value?.pending ?? 0) / statusCounts.value.all) * 100)}% ${t("owner_applications_page.of_total_suffix")}`
        : t("owner_applications_page.no_requests_yet"),
      c1: "#D4AF37",
      c2: "#8A6D1F",
    },
    {
      icon: "fa-clock",
      label: t("owner_applications_page.overdue_this_page"),
      value: overdueCountOnPage.value,
      sub: t("owner_applications_page.overdue_days_desc", { days: SLA_DAYS }),
      c1: "#D9534F",
      c2: "#8A6D1F",
    },
    {
      icon: "fa-chart-simple",
      label: t("owner_applications_page.approval_rate"),
      value: approvalRate.value !== null ? `${approvalRate.value}%` : "-",
      sub: (statusCounts.value?.approved || statusCounts.value?.rejected)
        ? t("owner_applications_page.approved_of_total", { approved: statusCounts.value?.approved ?? 0, total: (statusCounts.value?.approved ?? 0) + (statusCounts.value?.rejected ?? 0) })
        : t("owner_applications_page.no_reviewed_requests_yet"),
      c1: "#17A2B8",
      c2: "#0f6c7d",
    },
  ]);

  /* ---------------- تنبيهات اللوحة ---------------- */
  const ALERT_META = {
    overdue: { icon: "fa-clock", color: "#D9534F", chip: "chip-danger" },
    duplicate: { icon: "fa-triangle-exclamation", color: "#FFC107", chip: "chip-warning" },
  };
  const alertItems = computed(() => {
    const items = [];
    if (overdueCountOnPage.value > 0) {
      items.push({
        type: "overdue",
        title: t("owner_applications_page.overdue_alert_title", { count: overdueCountOnPage.value }),
        description: t("owner_applications_page.overdue_alert_desc", { days: SLA_DAYS }),
      });
    }
    if (duplicateCountOnPage.value > 0) {
      items.push({
        type: "duplicate",
        title: t("owner_applications_page.duplicate_alert_title", { count: duplicateCountOnPage.value }),
        description: t("owner_applications_page.duplicate_alert_desc"),
      });
    }
    return items;
  });

  /* ---------------- رفض (فردي/جماعي) ---------------- */
  const isRejectOpen = ref(false);
  const rejectTarget = ref(null);
  const rejectBulkIds = ref(null);
  const rejectReason = ref("");

  function openReject(application) {
    rejectTarget.value = application;
    rejectBulkIds.value = null;
    rejectReason.value = "";
    isRejectOpen.value = true;
  }
  function openBulkReject(ids) {
    rejectTarget.value = null;
    rejectBulkIds.value = ids;
    rejectReason.value = "";
    isRejectOpen.value = true;
  }
  function closeReject() {
    if (reviewingId.value || isBulkProcessing.value) return;
    isRejectOpen.value = false;
    rejectTarget.value = null;
    rejectBulkIds.value = null;
  }

  /* ---------------- نتيجة آخر إجراء + رابط واتساب ---------------- */
  const lastResult = ref(null);

  function dismissLastResult() {
    lastResult.value = null;
  }

  function buildWhatsAppLink(phone, message) {
    if (!phone) return null;
    const digits = String(phone).replace(/[^\d]/g, "");
    if (!digits) return null;
    return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`;
  }

  const lastResultWhatsAppLink = computed(() => {
    if (!lastResult.value) return null;

    const { type, name, phone, reason } = lastResult.value;
    const loginUrl = `${window.location.origin}/login`;

    const message =
      type === "approved"
        ? t("owner_applications_page.whatsapp_approved_message", { name, loginUrl })
        : t("owner_applications_page.whatsapp_rejected_message", { name, reasonSuffix: reason ? t("owner_applications_page.whatsapp_rejected_reason_suffix", { reason }) : "" });

    return buildWhatsAppLink(phone, message);
  });

  /* ---------------- اعتماد/رفض ---------------- */
  async function handleApprove(application) {
    const ok = await confirm({
      title: t("owner_applications_page.approve_confirm_title"),
      message: t("owner_applications_page.approve_confirm_message", { name: application.name }),
      confirmLabel: t("owner_applications_page.approve_action"),
      variant: "default",
    });
    if (!ok) return;

    const result = await approveApplication(application.id);
    if (result) {
      lastResult.value = {
        type: "approved",
        name: application.name,
        phone: application.phone,
        reason: null,
      };
    }
  }

  async function submitReject() {
    if (rejectBulkIds.value?.length) {
      const ids = rejectBulkIds.value;
      const result = await bulkRejectApplications(ids, rejectReason.value || null);
      if (result) {
        toast.show({
          type: result.failed && Object.keys(result.failed).length ? "warning" : "success",
          title: t("owner_applications_page.bulk_reject_toast_title"),
          message: t("owner_applications_page.bulk_reject_toast_message", {
            rejected: result.rejected.length,
            failedSuffix: Object.keys(result.failed ?? {}).length
              ? t("owner_applications_page.bulk_reject_failed_suffix", { count: Object.keys(result.failed).length })
              : "",
          }),
        });
        clearSelection();
      }
      closeReject();
      return;
    }

    const application = rejectTarget.value;
    if (!application) return;

    const result = await rejectApplication(application.id, rejectReason.value || null);
    if (result) {
      lastResult.value = {
        type: "rejected",
        name: application.name,
        phone: application.phone,
        reason: rejectReason.value || null,
      };
    }
    closeReject();
  }

  /* ---------------- اختيار جماعي ---------------- */
  const selectedIds = ref([]);
  const selectablePendingApplications = computed(() => applications.value.filter((a) => a.status === "pending"));
  const isAllSelected = computed(
    () => selectablePendingApplications.value.length > 0
      && selectablePendingApplications.value.every((a) => selectedIds.value.includes(a.id)),
  );

  function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx === -1) selectedIds.value.push(id);
    else selectedIds.value.splice(idx, 1);
  }
  function toggleSelectAll() {
    if (isAllSelected.value) {
      selectedIds.value = [];
    } else {
      selectedIds.value = selectablePendingApplications.value.map((a) => a.id);
    }
  }
  function clearSelection() {
    selectedIds.value = [];
  }

  async function handleBulkApprove() {
    const ok = await confirm({
      title: t("owner_applications_page.approve_selected_title"),
      message: t("owner_applications_page.approve_selected_message", { count: selectedIds.value.length }),
      confirmLabel: t("owner_applications_page.approve_all_action"),
      variant: "default",
    });
    if (!ok) return;

    const result = await bulkApproveApplications([...selectedIds.value]);
    if (result) {
      toast.show({
        type: result.failed && Object.keys(result.failed).length ? "warning" : "success",
        title: t("owner_applications_page.bulk_approve_toast_title"),
        message: t("owner_applications_page.bulk_approve_toast_message", {
          approved: result.approved.length,
          failedSuffix: Object.keys(result.failed ?? {}).length
            ? t("owner_applications_page.bulk_approve_failed_suffix", { count: Object.keys(result.failed).length })
            : "",
        }),
      });
      clearSelection();
    }
  }

  function handleBulkReject() {
    if (!selectedIds.value.length) return;
    openBulkReject([...selectedIds.value]);
  }

  /* ---------------- عارض المستندات ---------------- */
  const isDocViewerOpen = ref(false);
  const activeDocApp = ref(null);
  const activeDocIndex = ref(0);

  function openDocViewer(app, index = 0) {
    if (!app.documents?.length) return;
    activeDocApp.value = app;
    activeDocIndex.value = index;
    isDocViewerOpen.value = true;
  }
  function closeDocViewer() {
    isDocViewerOpen.value = false;
    activeDocApp.value = null;
  }
  const activeDoc = computed(() => activeDocApp.value?.documents?.[activeDocIndex.value] ?? null);
  function docCount() {
    return activeDocApp.value?.documents?.length ?? 0;
  }
  function nextDoc() {
    const len = docCount();
    if (len <= 1) return;
    activeDocIndex.value = (activeDocIndex.value + 1) % len;
  }
  function prevDoc() {
    const len = docCount();
    if (len <= 1) return;
    activeDocIndex.value = (activeDocIndex.value - 1 + len) % len;
  }
  function isImageDoc(doc) {
    if (!doc) return false;
    if (doc.mime_type) return doc.mime_type.startsWith("image/");
    return /\.(jpe?g|png|webp|gif)$/i.test(doc.url ?? doc.name ?? "");
  }

  /* ---------------- لوحة التفاصيل + سجل المراجعة ---------------- */
  const isDetailsOpen = ref(false);
  const detailsApp = ref(null);
  const isLoadingDetails = ref(false);
  const reviewHistory = ref([]);
  const isLoadingHistory = ref(false);

  async function openDetails(app) {
    detailsApp.value = app;
    isDetailsOpen.value = true;
    isLoadingDetails.value = true;
    isLoadingHistory.value = true;

    try {
      const { data } = await ownerApplicationService.show(app.id);
      detailsApp.value = data.data;
    } catch {
    } finally {
      isLoadingDetails.value = false;
    }

    try {
      const { data } = await activityLogService.index({
        subject_type: "owner_application",
        subject_id: app.id,
        per_page: 20,
      });
      reviewHistory.value = data.data.data ?? data.data;
    } catch {
      reviewHistory.value = [];
    } finally {
      isLoadingHistory.value = false;
    }
  }

  function closeDetails() {
    isDetailsOpen.value = false;
    detailsApp.value = null;
    reviewHistory.value = [];
  }

  function detailsSwitchToReject() {
    const app = detailsApp.value;
    closeDetails();
    openReject(app);
  }
  async function detailsApprove() {
    const app = detailsApp.value;
    closeDetails();
    await handleApprove(app);
  }

  const HISTORY_EVENT_META = {
    created: { icon: "fa-inbox", color: "#8A6D1F" },
    updated: { icon: "fa-user-check", color: "#52733D" },
    deleted: { icon: "fa-trash", color: "#D9534F" },
  };
  function historyEventLabel(log) {
    const status = log.changes?.attributes?.status;
    if (status === "approved") return t("owner_applications_page.history_approved");
    if (status === "rejected") return t("owner_applications_page.history_rejected");
    if (log.description === "created") return t("owner_applications_page.history_submitted");
    return log.description ?? t("owner_applications_page.history_update_fallback");
  }
  function historyEventMeta(log) {
    const status = log.changes?.attributes?.status;
    if (status === "approved") return { icon: "fa-circle-check", color: "#28A745" };
    if (status === "rejected") return { icon: "fa-circle-xmark", color: "#D9534F" };
    return HISTORY_EVENT_META[log.description] ?? { icon: "fa-clock-rotate-left", color: "#8A6D1F" };
  }

  function goToDuplicateUser() {
    router.push({ name: "admin.users" });
  }

  /* ---------------- ملاحظات داخلية ---------------- */
  const internalNoteDrafts = reactive({});
  const savingInternalNoteId = ref(null);

  function internalNoteDraft(app) {
    if (internalNoteDrafts[app.id] === undefined) {
      internalNoteDrafts[app.id] = app.internal_note ?? "";
    }
    return internalNoteDrafts[app.id];
  }
  function setInternalNoteDraft(app, value) {
    internalNoteDrafts[app.id] = value;
  }
  function hasUnsavedInternalNote(app) {
    return (internalNoteDrafts[app.id] ?? (app.internal_note ?? "")) !== (app.internal_note ?? "");
  }
  async function saveInternalNote(app) {
    savingInternalNoteId.value = app.id;
    try {
      await updateInternalNote(app.id, internalNoteDrafts[app.id] ?? "");
    } finally {
      savingInternalNoteId.value = null;
    }
  }

  /* ---------------- نسخ سريع + رابط التصدير ---------------- */
  const copiedField = ref(null);
  async function copyToClipboard(text, key) {
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
      copiedField.value = key;
      setTimeout(() => {
        if (copiedField.value === key) copiedField.value = null;
      }, 1500);
    } catch {
    }
  }

  const applicationsExportUrl = computed(() =>
    ownerApplicationService.exportUrl({
      search: searchQuery.value || undefined,
      status: applicationStatusFilter.value || undefined,
      sort: applicationsSortBy.value || undefined,
      from_date: dateFrom.value || undefined,
      to_date: dateTo.value || undefined,
    }),
  );

  return {
    APPLICATION_STATUS_PILLS,
    SORT_OPTIONS,
    STATUS_CHIP,
    STATUS_ICON,
    fmtGeneratorPrice,
    generatorLocationLabel,
    SLA_DAYS,
    isOverdue,
    overdueCountOnPage,
    duplicateCountOnPage,
    approvalRate,
    APPLICATION_KPI_CARDS,
    ALERT_META,
    alertItems,
    isRejectOpen,
    rejectTarget,
    rejectBulkIds,
    rejectReason,
    openReject,
    openBulkReject,
    closeReject,
    lastResult,
    dismissLastResult,
    lastResultWhatsAppLink,
    handleApprove,
    submitReject,
    selectedIds,
    selectablePendingApplications,
    isAllSelected,
    toggleSelect,
    toggleSelectAll,
    clearSelection,
    handleBulkApprove,
    handleBulkReject,
    isDocViewerOpen,
    activeDocApp,
    activeDocIndex,
    openDocViewer,
    closeDocViewer,
    activeDoc,
    docCount,
    nextDoc,
    prevDoc,
    isImageDoc,
    isDetailsOpen,
    detailsApp,
    isLoadingDetails,
    reviewHistory,
    isLoadingHistory,
    openDetails,
    closeDetails,
    detailsSwitchToReject,
    detailsApprove,
    historyEventLabel,
    historyEventMeta,
    goToDuplicateUser,
    internalNoteDrafts,
    savingInternalNoteId,
    internalNoteDraft,
    setInternalNoteDraft,
    hasUnsavedInternalNote,
    saveInternalNote,
    copiedField,
    copyToClipboard,
    applicationsExportUrl,
  };
}
