<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { reactive, ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { Chart as ChartJS, registerables } from "chart.js";
import { Doughnut, Bar, Line } from "vue-chartjs";
import { useAdminGeneratorOwners } from "@/composables/useAdminGeneratorOwners";
import { useAdminGenerators } from "@/composables/useAdminGenerators";
import { useAdminOwnerActions } from "@/composables/useAdminOwnerActions";
import { useOwnersAnalytics } from "@/composables/useOwnersAnalytics";
import { useConfirm } from "@/composables/useConfirm";
import { usePermissions } from "@/composables/usePermissions";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import { vCountUp } from "@/directives/countUp";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import ColumnFilterPopover from "@/components/ui/ColumnFilterPopover.vue";
import { printTable } from "@/utils/printTable";
import AdminGeneratorFormModal from "@/components/generators/AdminGeneratorFormModal.vue";
import { useAdminOwnerApplications } from "@/composables/useAdminOwnerApplications";
import { useOwnerApplicationsUI } from "@/composables/useOwnerApplicationsUI";
import { Check, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, Clock, Copy, Crown, Download, Eye, FileSpreadsheet, FileText, GripVertical, Inbox, Info, Key, LoaderCircle, LockOpen, Mail, Paperclip, Pencil, Percent, Phone, PlugZap, Plus, Printer, RotateCcwClock, Save, Search, Star, StickyNote, Table2, Trash2, TriangleAlert, User, UserCheck, UserPlus, UserRound, X, ZoomOut } from "@lucide/vue";
import { useOwnerRatings } from "@/composables/useOwnerRatings";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";
import LightningCanvas from "@/components/ui/LightningCanvas.vue";


ChartJS.register(...registerables);

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();

const {
  owners,
  pagination,
  isLoading,
  error,
  searchTerm,
  statusFilter,
  generatorsCountFilter,
  commissionRateFilter,
  stats,
  isLoadingStats,
  fetchOwners,
  isSaving,
  saveError,
  updateOwner,
  deletingId,
  deleteError,
  deleteOwner,
  unlockOwner,
  isCreating,
  createError,
  createOwner,
  loadAll,
} = useAdminGeneratorOwners();

const {
  createGenerator,
  isSaving: isSavingGenerator,
  saveError: generatorSaveError,
} = useAdminGenerators();

const {
  isSavingCommission,
  commissionError,
  saveCommissionSettings,
  isSendingResetLink,
  sendPasswordResetLink,
  plans,
  isLoadingPlans,
  isAssigningPlan,
  planError,
  fetchPlans,
  assignPlan: assignPlanAction,
  transferGenForm,
  isTransferringGenerator,
  transferGenError,
  allGeneratorsForTransfer,
  isLoadingGeneratorsForTransfer,
  fetchGeneratorsForTransfer,
  submitTransferGenerator: submitTransferGeneratorAction,
  revenueDistData,
  availableYears,
  isLoadingRevenue,
  fetchRevenueDistribution: fetchRevenueDistributionAction,
  ownersExportUrl: buildOwnersExportUrl,
  timeline,
  isLoadingTimeline,
  fetchTimeline,
} = useAdminOwnerActions({ owners, loadAll });

/* ---------------- FRONT-004b (slice 2): تحليلات/عرض لوحة أصحاب المولدات —
   منقولة لـ useOwnersAnalytics composable (God-component breakdown). ---------------- */
const {
  STATUS_META,
  statusLabel,
  statusChip,
  STATUS_PILLS,
  fmtMoney,
  initialsOf,
  avatarColor,
  commissionLabel,
  viewMode,
  sortBy,
  sortedOwners,
  sortIconClass,
  toggleSort,
  KPI_CARDS,
  ALERT_ICONS,
  ALERT_CHIPS,
  alertTag,
  handleAlertClick,
  scrollToOwnersTable,
  revenuePeriod,
  revenueYear,
  fetchRevenueDistribution,
  setRevenuePeriod,
  setRevenueYear,
  revenueYearOptions,
  revenueDistChartData,
  lineOptions,
  planChartData,
  doughnutOptions,
  growthChartData,
  barOptions,
} = useOwnersAnalytics({
  owners,
  stats,
  statusFilter,
  onFilterChange,
  router,
  revenueDistData,
  availableYears,
  fetchRevenueDistributionAction,
  openView: (owner) => openView(owner),
});

const { confirm } = useConfirm();
const { can } = usePermissions();
const toast = useToastStore();

const TABS = computed(() => [
  { key: "owners", label: t("owners_page.breadcrumb"), icon: "fa-user-tie" },
  { key: "applications", label: t("owner_applications_page.breadcrumb"), icon: "fa-inbox", badge: statusCounts.value?.pending ?? 0 },
]);
const activeTab = ref("owners");

let searchTimeout = null;
function handleSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchOwners(1), 400);
}

function onFilterChange() {
  fetchOwners(1);
}

const exportUrl = computed(() =>
  buildOwnersExportUrl({ search: searchTerm.value || undefined, status: statusFilter.value || undefined }),
);
function printPage() {
  printTable({
    title: t("owners_page.list_title"),
    locale: locale.value,
    columns: [
      { label: t("dashboard.owner"), value: (o) => o.name },
      { label: t("owners_page.phone_col"), value: (o) => o.phone ?? "-" },
      { label: t("owners_page.generators_count_col"), value: (o) => o.generators_count ?? 0 },
      { label: t("owners_page.total_subscribers_col"), value: (o) => o.active_subscriptions_count ?? 0 },
      { label: t("dashboard.monthly_revenue_col"), value: (o) => fmtMoney(o.monthly_revenue_ils) },
      { label: t("owners_page.commission_col"), value: (o) => commissionLabel(o.commission_settings?.rate, o.commission_settings?.mode) },
      { label: t("owners_page.plan_col"), value: (o) => o.plan?.name ?? t("owners_page.no_plan") },
      { label: t("dashboard.status_col"), value: (o) => (o.is_locked ? t("owners_page.locked") : statusLabel(o.status)) },
    ],
    rows: sortedOwners.value,
  });
}

const commissionModal = ref(null);
const commissionForm = reactive({ commission_mode: "fixed", commission_rate: "" });
const commissionModeOptions = computed(() => [
  { value: "fixed", label: t("owners_page.fixed_rate") },
  { value: "tiered", label: t("owners_page.tiered_rate") },
]);

function openCommissionModal(owner) {
  commissionModal.value = owner;
  commissionForm.commission_mode = owner.commission_settings?.mode ?? "fixed";
  commissionForm.commission_rate = owner.commission_settings?.rate ?? owner.commission_rate ?? "";
  commissionError.value = null;
}

async function handleSaveCommission() {
  const payload = { commission_mode: commissionForm.commission_mode };
  if (commissionForm.commission_mode === "fixed") payload.commission_rate = Number(commissionForm.commission_rate);

  const updated = await saveCommissionSettings(commissionModal.value.id, payload);
  if (!updated) return;

  if (viewingOwner.value?.id === updated.id) viewingOwner.value = updated;
  toast.show({
    type: "success",
    title: t("owners_page.saved_toast_title"),
    message: t("owners_page.commission_saved_msg"),
  });
  commissionModal.value = null;
}

async function handleSendResetLink(owner) {
  const confirmed = await confirm({
    title: t("owners_page.send_reset_link_toast_title"),
    message: t("owners_page.send_reset_link_confirm_msg", { email: owner.email }),
    confirmLabel: t("owners_page.send_label"),
    variant: "default",
  });
  if (!confirmed) return;

  const result = await sendPasswordResetLink(owner.id);
  if (result.success) {
    toast.show({ type: "success", title: t("owners_page.sent_toast_title"), message: t("owners_page.reset_link_sent_msg", { email: owner.email }) });
  } else {
    toast.show({ type: "danger", title: t("owners_page.send_failed_toast_title"), message: result.message ?? "" });
  }
}

function timeAgo(str) {
  if (!str) return "-";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}

const showCreateForm = ref(false);
const createForm = reactive({ name: "", email: "", phone: "", password: "", password_confirmation: "" });

function openCreateForm() {
  showCreateForm.value = true;
  createError.value = null;
  Object.assign(createForm, { name: "", email: "", phone: "", password: "", password_confirmation: "" });
}

async function handleCreate() {
  const ok = await createOwner({ ...createForm });
  if (ok) {
    showCreateForm.value = false;
    toast.show({
      type: "success",
      title: t("owners_page.created_toast_title"),
      message: t("owners_page.owner_created_msg"),
    });
  }
}

const isAddGeneratorModalOpen = ref(false);

function openAddGeneratorModal() {
  generatorSaveError.value = null;
  isAddGeneratorModalOpen.value = true;
}

function closeAddGeneratorModal() {
  if (isSavingGenerator.value) return;
  isAddGeneratorModalOpen.value = false;
  generatorSaveError.value = null;
}

async function handleCreateGenerator(payload) {
  const name = payload.name;
  const ok = await createGenerator(payload);
  if (ok) {
    isAddGeneratorModalOpen.value = false;
    await loadAll();
    toast.show({
      type: "success",
      title: t("owners_page.created_toast_title"),
      message: t("owners_page.generator_created_msg", { name }),
    });
  }
}

const isViewOpen = ref(false);
const viewingOwner = ref(null);

// FIX (تدقيق شامل — F1): GET /owners/{id}/ratings كان جاهزًا بالكامل
// بالباك اند (Controller + Policy + Resource) وموجودًا حتى بالـComposable
// الجاهز useOwnerRatings.js، لكن الأدمن لم يكن يملك أي شاشة لعرض تقييمات
// أي مالك رغم امتلاكه الصلاحية owner-ratings.view.
const {
  ratings: ownerRatingsList,
  averageRating: ownerRatingsAverage,
  ratingsCount: ownerRatingsCount,
  isLoading: isLoadingOwnerRatings,
  error: ownerRatingsError,
  fetchRatings: fetchOwnerRatingsFor,
} = useOwnerRatings();

function openView(owner) {
  viewingOwner.value = owner;
  isViewOpen.value = true;
  fetchOwnerRatingsFor(owner.id);
}
function switchToEditFromView() {
  isViewOpen.value = false;
  openEdit(viewingOwner.value);
}

const editingOwner = ref(null);
const editForm = reactive({ name: "", email: "", phone: "", status: "active" });
const editOwnerStatusOptions = computed(() =>
  ["active", "inactive", "suspended", "pending_review"].map((value) => ({ value, label: statusLabel(value) })),
);

function openEdit(owner) {
  editingOwner.value = owner;
  editForm.name = owner.name;
  editForm.email = owner.email;
  editForm.phone = owner.phone ?? "";
  editForm.status = owner.status ?? "active";
  saveError.value = null;
}

async function handleSave() {
  const name = editingOwner.value.name;
  const ok = await updateOwner(editingOwner.value.id, { ...editForm });
  if (ok) {
    editingOwner.value = null;
    toast.show({
      type: "success",
      title: t("owners_page.saved_toast_title"),
      message: t("owners_page.updated_msg", { name }),
    });
  }
}

const isDeleteModalOpen = ref(false);
const deletingOwner = ref(null);

function openDeleteModal(owner) {
  deletingOwner.value = owner;
  deleteError.value = null;
  isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
  if (deletingId.value) return;
  isDeleteModalOpen.value = false;
  deletingOwner.value = null;
}

async function confirmDeleteOwner() {
  if (!deletingOwner.value) return;
  const name = deletingOwner.value.name;
  const ok = await deleteOwner(deletingOwner.value.id);
  if (ok) {
    isDeleteModalOpen.value = false;
    deletingOwner.value = null;
    toast.show({
      type: "success",
      title: t("owners_page.deleted_toast_title"),
      message: t("owners_page.owner_deleted_msg", { name }),
    });
  }
}

async function handleUnlock(owner) {
  // FIX: (item 13) نفس النمط المطبَّق سابقًا بـ UsersView.vue (FIX-015) —
  // كان بدون try/catch، فأي فشل بالفتح (شبكة/صلاحيات) كان unhandled
  // rejection صامت بدون أي رسالة للأدمن.
  try {
    await unlockOwner(owner.id);
    toast.show({
      type: "success",
      title: t("owners_page.unlocked_toast_title"),
      message: t("owners_page.owner_unlocked_msg", { name: owner.name }),
    });
  } catch (err) {
    toast.show({
      type: "danger",
      title: t("owners_page.unlocked_toast_title"),
      message: normalizeApiError(err, t("common.unexpected_error_retry")).message,
    });
  }
}

const planModal = ref({ open: false, owner: null, planId: null });

async function openPlanModal(owner) {
  planModal.value = { open: true, owner, planId: owner.plan?.id ?? null };
  planError.value = null;
  await fetchPlans();
}

async function assignPlan() {
  const ok = await assignPlanAction(planModal.value.owner.id, planModal.value.planId);
  if (!ok) return;

  planModal.value.open = false;
  toast.show({
    type: "success",
    title: t("owners_page.assigned_toast_title"),
    message: t("owners_page.plan_assigned_msg"),
  });
}

const transferGenModal = ref({ open: false });

const transferGeneratorOptions = computed(() =>
  allGeneratorsForTransfer.value.map((g) => ({
    value: g.id,
    label: `${g.name} — ${t("owners_page.current_owner_label")}: ${g.owner?.name ?? "—"}`,
  })),
);
const transferOwnerOptions = computed(() =>
  owners.value
    .filter((o) => o.id !== allGeneratorsForTransfer.value.find((g) => g.id === transferGenForm.generator_id)?.owner?.id)
    .map((o) => ({ value: o.id, label: o.name })),
);

async function openTransferGeneratorModal() {
  transferGenForm.generator_id = "";
  transferGenForm.owner_id = "";
  transferGenError.value = null;
  transferGenModal.value.open = true;
  await fetchGeneratorsForTransfer();
}

async function submitTransferGenerator() {
  const ok = await submitTransferGeneratorAction();
  if (!ok) return;

  transferGenModal.value.open = false;
  toast.show({
    type: "success",
    title: t("owners_page.transferred_toast_title"),
    message: t("owners_page.ownership_transferred_msg"),
  });
}

onMounted(async () => {
  if (route.query.q) searchTerm.value = String(route.query.q);
  await loadAll();
  fetchTimeline();
});

const {
  applications,
  pagination: applicationsPagination,
  isLoading: isLoadingApplications,
  error: applicationsError,
  statusFilter: applicationStatusFilter,
  reviewingId,
  reviewError,
  fetchApplications,
  onFilterChange: onApplicationFilterChange,
  approveApplication,
  rejectApplication,
  statusCounts,
  searchQuery,
  onSearchInput,
  sortBy: applicationsSortBy,
  onSortChange,
  dateFrom,
  dateTo,
  dateRangeError,
  onDateFilterChange,
  isBulkProcessing,
  bulkError,
  bulkApproveApplications,
  bulkRejectApplications,
  updateInternalNote,
} = useAdminOwnerApplications();

/* ---------------- FRONT-004b (slice 3): منطق تبويب طلبات الانضمام —
   منقولة لـ useOwnerApplicationsUI composable (God-component breakdown). ---------------- */
const {
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
} = useOwnerApplicationsUI({
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
});

onMounted(() => fetchApplications());
</script>

<template>

  <div class="space-y-6">
    <section v-reveal class="glass-card relative overflow-hidden p-6 lg:p-8">
      <LightningCanvas />
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="relative flex flex-col gap-3">
        <nav class="flex items-center gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          <span>{{ t("common.home") }}</span>
          <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
          <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
          <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">
            {{ activeTab === "owners" ? t("owners_page.breadcrumb") : t("owner_applications_page.breadcrumb") }}
          </span>
        </nav>

        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="min-w-0">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold text-[#52733D] dark:text-[#8cc35a] bg-[#EBF1E7] dark:bg-white/5 border border-[#D4AF37]/30 rounded-full px-3 py-1.5 w-fit">
              <UserRound class="text-[#8A6D1F] dark:text-[#D4AF37]" aria-hidden="true" v-if="activeTab === 'owners'" /><Inbox class="text-[#8A6D1F] dark:text-[#D4AF37]" aria-hidden="true" v-else />
              {{ activeTab === "owners" ? t("owners_page.eyebrow") : t("owner_applications_page.title") }}
            </span>

            <h1 class="text-2xl lg:text-[28px] font-extrabold mt-3">
              {{ activeTab === "owners" ? t("owners_page.breadcrumb") : t("owner_applications_page.breadcrumb") }}
            </h1>

            <p class="text-[13.5px] text-[#6B6B6B] dark:text-[#aeb1ab] leading-relaxed max-w-2xl mt-1.5">
              <template v-if="activeTab === 'owners'">
                {{ t("owners_page.subtitle_before") }}
                <b class="text-[#3E582E] dark:text-[#8cc35a]">{{ stats?.total ?? 0 }}</b>
                {{ t("owners_page.subtitle_middle") }}
                <b class="text-[#3E582E] dark:text-[#8cc35a]">{{ stats?.total_generators ?? 0 }}</b>
                {{ t("owners_page.subtitle_after") }}
              </template>
              <template v-else>
                {{ t("owner_applications_page.subtitle") }}
              </template>
            </p>
          </div>

          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 shrink-0 print-hidden">
            <button
              v-for="tab in TABS"
              :key="tab.key"
              type="button"
              @click="activeTab = tab.key"
              class="relative flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
              :class="
                activeTab === tab.key
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                  : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
              "
            >
              <AppIcon :name="tab.icon" />
              {{ tab.label }}
              <span
                v-if="tab.badge"
                class="text-[10px] font-extrabold rounded-full px-1.5 min-w-[1.1rem] text-center"
                :class="activeTab === tab.key ? 'bg-white/25 text-white' : 'bg-[#D9534F] text-white'"
              >{{ tab.badge }}</span>
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-2.5 mt-1">
          <template v-if="activeTab === 'owners'">
            <button v-if="can('users.create')" type="button" @click="openCreateForm" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2">
              <UserPlus aria-hidden="true" /> {{ t("owners_page.add_owner") }}
            </button>
            <button v-if="can('generators.update')" type="button" @click="openTransferGeneratorModal" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
              <PlugZap aria-hidden="true" /> {{ t("owners_page.link_generator_to_owner") }}
            </button>
            <button v-if="can('generators.create')" type="button" @click="openAddGeneratorModal" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
              <Plus aria-hidden="true" /> {{ t("owners_page.add_new_generator") }}
            </button>
          </template>
          <template v-else>
            <button
              type="button"
              class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2 print-hidden"
              :title="$t('owner_applications_page.print')"
              @click="printPage"
            >
              <Printer aria-hidden="true" />
              {{ $t("owner_applications_page.print") }}
            </button>
            <a
              :href="applicationsExportUrl"
              target="_blank"
              rel="noopener"
              class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2 print-hidden"
              :title="$t('owner_applications_page.export_excel_title')"
            >
              <FileSpreadsheet aria-hidden="true" />
              {{ $t("owner_applications_page.export_action") }}
            </a>
          </template>
        </div>
      </div>
    </section>

    <template v-if="activeTab === 'owners'">
    <section v-reveal>
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h2 class="text-[15px] font-extrabold">{{ t("owners_page.quick_overview") }}</h2>
      </div>
      <div v-if="isLoadingStats" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5">
        <div v-for="i in 5" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.raw" :icon="c.icon" :tone="c.tone"
          :prefix="c.prefix ?? ''" :suffix="c.suffix ?? ''" :decimals="c.decimals ?? 0"
        />
      </div>
    </section>

    <section class="grid md:grid-cols-3 gap-4 items-stretch">
      <div v-reveal class="glass-card p-4 md:col-span-2 flex flex-col">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ t("owners_page.distributed_revenue_title") }}</h3>

          <div class="flex items-center gap-2 flex-wrap">
            <AppDropdownSelect
              v-if="revenuePeriod === 'year'"
              :model-value="revenueYear"
              @update:model-value="setRevenueYear"
              :options="revenueYearOptions"
              width-class="w-24"
              :panel-width="96"
            />
            <div class="flex items-center gap-1 bg-[#f4efe5]/60 dark:bg-white/5 rounded-full p-1">
              <button
                type="button"
                @click="setRevenuePeriod('6')"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors"
                :class="revenuePeriod === '6' ? 'bg-[#8A6D1F] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
              >
                {{ t("dashboard.period_6m") }}
              </button>
              <button
                type="button"
                @click="setRevenuePeriod('12')"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors"
                :class="revenuePeriod === '12' ? 'bg-[#8A6D1F] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
              >
                {{ t("dashboard.period_12m") }}
              </button>
              <button
                type="button"
                @click="setRevenuePeriod('year')"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors"
                :class="revenuePeriod === 'year' ? 'bg-[#8A6D1F] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
              >
                {{ t("dashboard.period_year") }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="isLoadingRevenue" class="flex-1 min-h-[16rem] thumb-loading rounded-lg"></div>
        <div v-else class="flex-1 min-h-[16rem]"><Line :data="revenueDistChartData" :options="lineOptions" /></div>
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("owners_page.plan_distribution_title") }}</h3>
        <div v-if="isLoadingStats" class="flex-1 min-h-[16rem] thumb-loading rounded-lg"></div>
        <div v-else-if="stats.plan_distribution.labels.length === 0" class="flex-1 min-h-[16rem] flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.not_enough_data") }}</div>
        <div v-else class="flex-1 min-h-[16rem]"><Doughnut :data="planChartData" :options="doughnutOptions" /></div>
      </div>
    </section>

    <section class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
      <div v-reveal class="glass-card p-4 md:col-span-2 lg:col-span-1 flex flex-col">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-[13.5px] font-bold">{{ t("owners_page.top_earners_title") }}</h3>
          <span class="text-[10px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] bg-[#D4AF37]/15 rounded-full px-2 py-0.5">{{ t("owners_page.top_5_badge") }}</span>
        </div>
        <div v-if="isLoadingStats" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-12 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="!stats?.top_owners_by_revenue?.length" class="text-center py-8 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.not_enough_data") }}</div>
        <div v-else class="space-y-2.5">
          <div v-for="(o, i) in stats.top_owners_by_revenue" :key="o.id" class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10.5px] font-extrabold shrink-0" :class="i === 0 ? 'bg-[#D4AF37] text-white' : 'bg-[#f4efe5] dark:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'">{{ i + 1 }}</span>
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[10.5px] font-bold shrink-0" :style="{ background: `linear-gradient(135deg, ${avatarColor(i)[0]}, ${avatarColor(i)[1]})` }">
              {{ initialsOf(o.name) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[12px] font-bold truncate">{{ o.name }}</p>
              <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ o.generators_count }} {{ t("owners_page.generators_word") }} · {{ o.subscribers_count }} {{ t("owners_page.subs_word") }}</p>
            </div>
            <span class="text-[12px] font-extrabold text-[#8A6D1F] dark:text-[#D4AF37] shrink-0">{{ fmtMoney(o.monthly_revenue_ils) }}</span>
          </div>
        </div>
      </div>

      <div v-reveal class="glass-card p-4 lg:col-span-2 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("owners_page.owner_growth_title") }}</h3>
        <div v-if="isLoadingStats" class="flex-1 min-h-[10rem] thumb-loading rounded-lg"></div>
        <div v-else class="flex-1 min-h-[10rem]"><Bar :data="growthChartData" :options="barOptions" /></div>
      </div>
    </section>

    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:flex-1 sm:max-w-md">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="searchTerm" type="text" @input="handleSearchInput"
            :placeholder="t('owners_page.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
              @click="statusFilter = pill.value; onFilterChange()"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button :aria-label="$t('common.view_as_table')" type="button" @click="viewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'table' }"><Table2 class="text-[11px]" aria-hidden="true" /></button>
            <button :aria-label="$t('common.view_as_grid')" type="button" @click="viewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'grid' }"><GripVertical class="text-[11px]" aria-hidden="true" /></button>
          </div>
          <a
            :href="exportUrl"
            target="_blank"
            rel="noopener"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="t('dashboard.export_excel_title')"
          >
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </a>
          <button
            type="button"
            @click="printPage"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="t('dashboard.print_title')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <section id="owners-table-section" v-reveal class="glass-card p-4 overflow-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ t("owners_page.list_title") }}</h3>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
      </div>
      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>
      <div v-else-if="owners.length === 0" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.no_matching_results") }}</p>
      </div>

      <template v-else>
      <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[980px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('name')">
                  {{ t("dashboard.owner") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('name')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">{{ t("owners_page.phone_col") }}</th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('generators')">
                  {{ t("owners_page.generators_count_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('generators')]" aria-hidden="true" />
                </span>
                <ColumnFilterPopover v-model="generatorsCountFilter" type="number" @update:modelValue="onFilterChange" @click.stop class="ms-1" />
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('subs')">
                  {{ t("owners_page.total_subscribers_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('subs')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('rev')">
                  {{ t("dashboard.monthly_revenue_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('rev')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1">
                  {{ t("owners_page.commission_col") }}
                  <ColumnFilterPopover v-model="commissionRateFilter" type="number" @update:modelValue="onFilterChange" @click.stop />
                </span>
              </th>
              <th class="py-2.5 px-3">{{ t("owners_page.plan_col") }}</th>
              <th class="py-2.5 px-3">{{ t("dashboard.status_col") }}</th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ t("dashboard.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(owner, idx) in sortedOwners" :key="owner.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
              :class="{ 'opacity-50 pointer-events-none': deletingId === owner.id }"
            >
              <td class="py-2.5 px-3">
                <div class="flex items-center justify-center gap-2.5">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] font-bold shrink-0" :style="{ background: `linear-gradient(135deg, ${avatarColor(idx)[0]}, ${avatarColor(idx)[1]})` }">
                    {{ initialsOf(owner.name) }}
                  </div>
                  <div class="min-w-0 text-start">
                    <div class="font-bold truncate max-w-[8rem]">{{ owner.name }}</div>
                    <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] truncate max-w-[8rem]">{{ owner.email }}</div>
                  </div>
                </div>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ owner.phone ?? "-" }}</td>
              <td class="py-2.5 px-3">{{ owner.generators_count ?? 0 }}</td>
              <td class="py-2.5 px-3">{{ owner.active_subscriptions_count ?? 0 }}</td>
              <td class="py-2.5 px-3 font-bold">{{ fmtMoney(owner.monthly_revenue_ils) }}</td>
              <td class="py-2.5 px-3">{{ commissionLabel(owner.commission_settings?.rate, owner.commission_settings?.mode) }}</td>
              <td class="py-2.5 px-3">
                <span v-if="owner.plan" class="status-chip chip-info">{{ owner.plan.name }}</span>
                <span v-else class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.no_plan") }}</span>
              </td>
              <td class="py-2.5 px-3">
                <span class="status-chip" :class="owner.is_locked ? 'chip-danger' : statusChip(owner.status)">
                  {{ owner.is_locked ? t("owners_page.locked") : statusLabel(owner.status) }}
                </span>
              </td>
              <td class="py-2.5 px-3">
                <div class="row-actions">
                  <button type="button" @click="openView(owner)" class="action-btn action-btn--view" :title="t('common.view')">
                    <Eye aria-hidden="true" />
                  </button>
                  <button v-if="owner.is_locked && can('users.unlock')" type="button" @click="handleUnlock(owner)" class="action-btn action-btn--unlock" :title="t('common.unlock')">
                    <LockOpen aria-hidden="true" />
                  </button>
                  <button type="button" @click="openPlanModal(owner)" class="action-btn action-btn--plan" :title="t('owners_page.plan_title')">
                    <Crown aria-hidden="true" />
                  </button>
                  <span class="row-actions-divider"></span>
                  <button v-if="can('users.update')" type="button" @click="openEdit(owner)" class="action-btn action-btn--edit" :title="t('common.edit')">
                    <Pencil aria-hidden="true" />
                  </button>
                  <button v-if="can('users.delete')" type="button" @click="openDeleteModal(owner)" :disabled="deletingId === owner.id" class="action-btn action-btn--delete" :title="t('common.delete')">
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === owner.id" /><Trash2 aria-hidden="true" v-else />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="(owner, idx) in sortedOwners" :key="owner.id" class="glass-card p-3.5">
          <div class="flex items-start justify-between mb-2.5">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-[11px] font-bold shrink-0" :style="{ background: `linear-gradient(135deg, ${avatarColor(idx)[0]}, ${avatarColor(idx)[1]})` }">
                {{ initialsOf(owner.name) }}
              </div>
              <div class="min-w-0">
                <div class="font-bold text-[12.5px] truncate">{{ owner.name }}</div>
                <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] text-start" dir="ltr">{{ owner.phone ?? "-" }}</div>
              </div>
            </div>
            <span class="status-chip shrink-0" :class="owner.is_locked ? 'chip-danger' : statusChip(owner.status)">
              {{ owner.is_locked ? t("owners_page.locked") : statusLabel(owner.status) }}
            </span>
          </div>
          <div class="flex items-center gap-2 mb-2.5 flex-wrap">
            <span v-if="owner.plan" class="status-chip chip-info">{{ owner.plan.name }}</span>
            <span class="text-[11px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5]">{{ fmtMoney(owner.monthly_revenue_ils) }}</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-[11px] mb-3">
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.generators_word_short") }}:</span> <b>{{ owner.generators_count ?? 0 }}</b></div>
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.subs_word") }}:</span> <b>{{ owner.active_subscriptions_count ?? 0 }}</b></div>
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.commission_col") }}:</span> <b>{{ commissionLabel(owner.commission_settings?.rate, owner.commission_settings?.mode) }}</b></div>
          </div>
          <div class="flex items-center justify-end">
            <div class="row-actions">
              <button type="button" @click="openView(owner)" class="action-btn action-btn--view" :title="t('common.view')"><Eye aria-hidden="true" /></button>
              <button v-if="owner.is_locked && can('users.unlock')" type="button" @click="handleUnlock(owner)" class="action-btn action-btn--unlock" :title="t('common.unlock')"><LockOpen aria-hidden="true" /></button>
              <button type="button" @click="openPlanModal(owner)" class="action-btn action-btn--plan" :title="t('owners_page.plan_title')"><Crown aria-hidden="true" /></button>
              <span class="row-actions-divider"></span>
              <button v-if="can('users.update')" type="button" @click="openEdit(owner)" class="action-btn action-btn--edit" :title="t('common.edit')"><Pencil aria-hidden="true" /></button>
              <button v-if="can('users.delete')" type="button" @click="openDeleteModal(owner)" :disabled="deletingId === owner.id" class="action-btn action-btn--delete" :title="t('common.delete')">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === owner.id" /><Trash2 aria-hidden="true" v-else />
              </button>
            </div>
          </div>
        </div>
      </div>
      </template>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
        <span>{{ t("owners_page.page_of_owners", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchOwners(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
          <button :aria-label="$t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchOwners(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <section class="grid md:grid-cols-2 gap-4">
      <div v-reveal class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ t("owners_page.alerts_title") }}</h3>
          <span v-if="stats?.alerts?.length" class="text-[10px] font-bold text-white bg-[#D9534F] rounded-full px-2 py-0.5">{{ stats.alerts.length }} {{ t("owners_page.new_word") }}</span>
        </div>
        <div v-if="isLoadingStats" class="space-y-2">
          <div v-for="i in 2" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="!stats?.alerts?.length" class="text-center py-8 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.no_alerts") }}</div>
        <div v-else class="space-y-2.5">
          <button
            v-for="(a, i) in stats.alerts" :key="i" type="button"
            @click="handleAlertClick(a)"
            class="w-full flex items-start gap-2.5 p-2.5 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors text-start"
          >
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0" :style="{ background: ALERT_ICONS[a.type]?.color ?? '#52733D' }">
              <AppIcon :name="ALERT_ICONS[a.type]?.icon ?? 'fa-bell'" class="text-[11px]" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[12px] font-bold truncate">{{ a.title }}</span>
                <span class="status-chip shrink-0" :class="ALERT_CHIPS[a.severity] ?? 'chip-info'">{{ alertTag(a.severity) }}</span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ a.description }}</p>
            </div>
            <ChevronRight class="rtl:block ltr:hidden text-[9px] text-[#c9cdc2] dark:text-[#565952] shrink-0 self-center" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[9px] text-[#c9cdc2] dark:text-[#565952] shrink-0 self-center" aria-hidden="true" />
          </button>
        </div>
      </div>

      <div v-reveal class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("owners_page.recent_activity_title") }}</h3>
        <div v-if="isLoadingTimeline" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-10 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="timeline.length === 0" class="text-center py-8 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.no_activity") }}</div>
        <div v-else class="mt-1">
          <div v-for="log in timeline" :key="log.id" class="timeline-item" style="--dot-color:#52733D">
            <p class="text-[12px] font-semibold">
              {{ log.causer?.name ?? t("owners_page.system_word") }}
              <span class="font-normal text-[#6B6B6B] dark:text-[#a8aaa5]">— {{ log.description }}</span>
            </p>
            <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(log.created_at) }}</span>
          </div>
        </div>
      </div>
    </section>

    <footer class="text-center text-[11px] text-[#9a9d97] dark:text-[#8f938a] py-4">
      {{ t("owners_page.footer_text") }}
    </footer>
    </template>

    <template v-else>
    <section v-reveal>
      <div v-if="isLoadingApplications" class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <div v-for="i in 4" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard
          v-for="c in APPLICATION_KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone" :suffix="c.suffix ?? ''"
        />
      </div>
    </section>

    <section v-if="alertItems.length" v-reveal class="glass-card p-4">
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h3 class="text-[13.5px] font-bold">{{ $t("owner_applications_page.alerts_on_page") }}</h3>
        <span class="text-[10px] font-bold text-white bg-[#D9534F] rounded-full px-2 py-0.5">{{ alertItems.length }}</span>
      </div>
      <div class="space-y-2.5">
        <div
          v-for="(a, i) in alertItems"
          :key="i"
          class="flex items-start gap-2.5 p-2.5 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors"
        >
          <div
            class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0"
            :style="{ background: ALERT_META[a.type].color }"
          >
            <AppIcon :name="ALERT_META[a.type].icon" class="text-[11px]" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="text-[12px] font-bold">{{ a.title }}</span>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ a.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <div
      v-if="lastResult"
      v-reveal
      class="glass-card p-4 flex items-center justify-between gap-3 flex-wrap border"
      :class="lastResult.type === 'approved' ? 'border-[#28A745]/30' : 'border-[#D9534F]/30'"
    >
      <div class="flex items-center gap-3">
        <div
          class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
          :class="lastResult.type === 'approved' ? 'bg-[#28A745]/10 text-[#28A745]' : 'bg-[#D9534F]/10 text-[#D9534F]'"
        >
          <Check aria-hidden="true" v-if="lastResult.type === 'approved'" /><X aria-hidden="true" v-else />
        </div>
        <p class="text-[12.5px] font-semibold">
          {{
            lastResult.type === "approved"
              ? $t("owner_applications_page.approved_notified_message", { name: lastResult.name })
              : $t("owner_applications_page.rejected_notified_message", { name: lastResult.name })
          }}
        </p>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <a
          v-if="lastResultWhatsAppLink"
          :href="lastResultWhatsAppLink"
          target="_blank"
          rel="noopener noreferrer"
          class="btn-fill relative bg-[#25D366] text-white text-[12px] font-bold px-4 py-2 rounded-full shadow-md flex items-center gap-2"
        >
          <i class="fa-brands fa-whatsapp"></i>
          {{ $t("owner_applications_page.whatsapp_message") }}
        </a>
        <span v-else class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("owner_applications_page.no_phone_for_applicant") }}
        </span>
        <button :aria-label="$t('common.close')" type="button" class="icon-btn !w-8 !h-8" @click="dismissLastResult">
          <X aria-hidden="true" />
        </button>
      </div>
    </div>

    <div v-reveal class="glass-card p-3.5 space-y-3 print-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 flex-wrap">
        <div class="relative flex-1 min-w-[180px]">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[11px]" aria-hidden="true" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('owner_applications_page.search_placeholder')"
            :aria-label="$t('owner_applications_page.search_aria')"
            @input="onSearchInput"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-9 pe-3 text-[12px] outline-none focus:border-[#8A6D1F]"
          />
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
          <input
            v-model="dateFrom"
            type="date"
            :aria-label="$t('owner_applications_page.from_date')"
            :max="dateTo || undefined"
            @change="onDateFilterChange"
            class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full px-3 py-2 text-[11px] outline-none focus:border-[#8A6D1F] w-[132px]"
          />
          <span class="text-[#9a9d97] dark:text-[#8f938a] text-[10px]">{{ $t("owner_applications_page.to_label") }}</span>
          <input
            v-model="dateTo"
            type="date"
            :aria-label="$t('owner_applications_page.to_date')"
            :min="dateFrom || undefined"
            @change="onDateFilterChange"
            class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full px-3 py-2 text-[11px] outline-none focus:border-[#8A6D1F] w-[132px]"
          />
          <button
            v-if="dateFrom || dateTo"
            type="button"
            class="icon-btn !w-8 !h-8"
            :aria-label="$t('owner_applications_page.clear_date_range')"
            :title="$t('owner_applications_page.clear_date_range')"
            @click="dateFrom = ''; dateTo = ''; onDateFilterChange()"
          >
            <X class="text-[11px]" aria-hidden="true" />
          </button>
        </div>

        <AppDropdownSelect
          :model-value="applicationsSortBy"
          @update:model-value="(val) => { applicationsSortBy = val; onSortChange(); }"
          :options="SORT_OPTIONS"
          width-class="w-44"
          :panel-width="176"
        />
      </div>

      <p v-if="dateRangeError" class="text-[11px] text-[#D9534F]">
        <CircleAlert class="me-1" aria-hidden="true" />{{ dateRangeError }}
      </p>

      <div class="flex items-center gap-2 flex-wrap">
        <button
          v-for="pill in APPLICATION_STATUS_PILLS"
          :key="pill.value"
          type="button"
          class="text-[11.5px] font-bold px-3.5 py-1.5 rounded-full border transition-colors whitespace-nowrap"
          :class="
            applicationStatusFilter === pill.value
              ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white border-transparent'
              : 'border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5'
          "
          @click="applicationStatusFilter = pill.value; onApplicationFilterChange()"
        >
          {{ pill.label }}
          <span class="ms-1 opacity-80 font-extrabold">({{ pill.count }})</span>
        </button>
      </div>
    </div>

    <div
      v-if="selectedIds.length"
      v-reveal
      class="glass-card p-3.5 flex items-center justify-between gap-3 flex-wrap border border-[#8A6D1F]/30 print-hidden"
      role="toolbar"
      :aria-label="$t('owner_applications_page.bulk_actions_aria')"
    >
      <div class="flex items-center gap-2">
        <span class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] bg-[#D4AF37]/15 rounded-full px-3 py-1.5">
          {{ $t("owner_applications_page.n_selected", { count: selectedIds.length }) }}
        </span>
        <button type="button" class="text-[11px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5] hover:underline" @click="clearSelection">
          {{ $t("owner_applications_page.clear_selection") }}
        </button>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="text-[12px] font-bold px-3.5 py-2 rounded-full border border-[#D9534F]/30 text-[#D9534F] hover:bg-[#D9534F]/10 transition-colors disabled:opacity-50"
          :disabled="isBulkProcessing"
          @click="handleBulkReject"
        >
          <X class="me-1" aria-hidden="true" />{{ $t("owner_applications_page.reject_selected") }}
        </button>
        <button
          type="button"
          class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12px] font-bold px-4 py-2 rounded-full shadow-md flex items-center gap-1.5 disabled:opacity-60"
          :disabled="isBulkProcessing"
          @click="handleBulkApprove"
        >
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isBulkProcessing" /><Check aria-hidden="true" v-else />
          {{ $t("owner_applications_page.approve_selected") }}
        </button>
      </div>
    </div>

    <div v-if="reviewError" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3">
      <CircleAlert class="me-1.5" aria-hidden="true" />{{ reviewError }}
    </div>
    <div v-if="bulkError" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3">
      <CircleAlert class="me-1.5" aria-hidden="true" />{{ bulkError }}
    </div>

    <div v-if="isLoadingApplications" class="grid gap-3" aria-hidden="true">
      <div v-for="i in 4" :key="i" class="glass-card p-4">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div class="flex items-start gap-3 min-w-0 flex-1">
            <div class="w-11 h-11 shrink-0 rounded-xl thumb-loading"></div>
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-3.5 w-40 rounded thumb-loading"></div>
              <div class="h-3 w-56 rounded thumb-loading"></div>
              <div class="h-14 w-full max-w-md rounded-lg thumb-loading mt-2"></div>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <div class="h-9 w-20 rounded-full thumb-loading"></div>
            <div class="h-9 w-20 rounded-full thumb-loading"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="applicationsError" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3">
      <CircleAlert class="me-1.5" aria-hidden="true" />{{ applicationsError }}
    </div>

    <div v-else-if="applications.length === 0" class="glass-card p-10 text-center">
      <Inbox class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2 block" aria-hidden="true" />
      <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">
        {{ $t("owner_applications_page.no_requests_this_status") }}
      </p>
    </div>

    <div v-else v-reveal class="grid gap-3">
      <div v-for="app in applications" :key="app.id" class="glass-card p-4" :class="{ 'ring-1 ring-[#D9534F]/40': isOverdue(app) }">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div class="flex items-start gap-3 min-w-0 flex-1">
            <input
              v-if="app.status === 'pending'"
              type="checkbox"
              class="mt-1.5 w-4 h-4 shrink-0 accent-[#52733D] cursor-pointer print-hidden"
              :checked="selectedIds.includes(app.id)"
              :aria-label="$t('owner_applications_page.select_request_aria', { name: app.name })"
              @change="toggleSelect(app.id)"
            />
            <div
              class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center text-white text-base shadow-md"
            >
              <PlugZap aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-[13.5px] font-extrabold">{{ app.name }}</h3>
                <span
                  class="status-chip"
                  :class="STATUS_CHIP[app.status]"
                  :title="app.status === 'rejected' && app.review_note ? app.review_note : undefined"
                >
                  <AppIcon :name="STATUS_ICON[app.status]" class="me-1" />{{ app.status_label }}
                </span>
                <span v-if="isOverdue(app)" class="status-chip chip-danger">
                  <Clock class="me-1" aria-hidden="true" />
                  {{ $t("owner_applications_page.overdue_days_badge", { days: daysSince(app.created_at) }) }}
                </span>
                <button
                  v-if="app.is_duplicate_email || app.is_duplicate_phone"
                  type="button"
                  class="status-chip chip-warning hover:opacity-80 transition-opacity"
                  :title="$t('owner_applications_page.duplicate_match_title', { name: app.duplicate_user_name ?? '', email: app.duplicate_user_email ?? '' })"
                  @click="goToDuplicateUser(app)"
                >
                  <TriangleAlert class="me-1" aria-hidden="true" />
                  {{ $t("owner_applications_page.duplicate_data_badge") }}
                </button>
              </div>

              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 flex items-center flex-wrap gap-x-3 gap-y-1">
                <span class="inline-flex items-center gap-1">
                  <Mail class="opacity-60" aria-hidden="true" />{{ app.email }}
                  <button type="button" class="opacity-50 hover:opacity-100 transition-opacity" @click="copyToClipboard(app.email, `email-${app.id}`)" :title="$t('users_page.copy_hint')">
                    <Check class="text-[#28A745]" aria-hidden="true" v-if="copiedField === `email-${app.id}`" /><Copy aria-hidden="true" v-else />
                  </button>
                </span>
                <span v-if="app.phone" class="inline-flex items-center gap-1">
                  <Phone class="opacity-60" aria-hidden="true" />{{ app.phone }}
                  <button type="button" class="opacity-50 hover:opacity-100 transition-opacity" @click="copyToClipboard(app.phone, `phone-${app.id}`)" :title="$t('users_page.copy_hint')">
                    <Check class="text-[#28A745]" aria-hidden="true" v-if="copiedField === `phone-${app.id}`" /><Copy aria-hidden="true" v-else />
                  </button>
                </span>
              </p>

              <p v-if="app.notes" class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1.5 leading-relaxed">
                {{ app.notes }}
              </p>
              <p v-if="app.review_note" class="text-[11px] text-[#D9534F] mt-1.5">
                {{ $t("owner_applications_page.rejection_reason_prefix") }}{{ app.review_note }}
              </p>

              <div v-if="app.generator_draft?.name" class="mt-2.5 bg-[#EBF1E7]/60 dark:bg-white/[0.03] rounded-lg p-2.5">
                <div class="text-[10.5px] font-bold text-[#3E582E] dark:text-[#8cc35a] mb-1.5">
                  <PlugZap class="me-1" aria-hidden="true" />
                  {{ $t("owner_applications_page.requested_generator") }}
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-3 gap-y-1.5 text-[11px]">
                  <div class="min-w-0">
                    <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.name") }}</span>
                    <div class="font-semibold truncate">{{ app.generator_draft.name }}</div>
                  </div>
                  <div class="min-w-0">
                    <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.price_per_kw_short") }}</span>
                    <div class="font-semibold">{{ fmtGeneratorPrice(app.generator_draft) }}</div>
                  </div>
                  <div class="min-w-0">
                    <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.capacity_label") }}</span>
                    <div class="font-semibold">{{ app.generator_draft.capacity_kw ? `${app.generator_draft.capacity_kw} kW` : "-" }}</div>
                  </div>
                  <div class="min-w-0">
                    <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.location_label") }}</span>
                    <div class="font-semibold truncate" :title="generatorLocationLabel(app.generator_draft)">{{ generatorLocationLabel(app.generator_draft) }}</div>
                  </div>
                </div>
              </div>

              <div v-if="app.documents?.length" class="mt-2.5">
                <div class="text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] mb-1.5">
                  <Paperclip class="me-1" aria-hidden="true" />
                  {{ $t("owner_applications_page.attached_documents") }} ({{ app.documents.length }})
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                  <button
                    v-for="(doc, idx) in app.documents"
                    :key="doc.id ?? idx"
                    type="button"
                    class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border border-[#e7e2d6] dark:border-white/10 relative group"
                    :title="doc.name"
                    @click="openDocViewer(app, idx)"
                  >
                    <img v-if="isImageDoc(doc)" :src="doc.thumbnail_url ?? doc.url" class="w-full h-full object-cover" :alt="doc.name" />
                    <div v-else class="w-full h-full flex items-center justify-center bg-[#f4efe5]/70 dark:bg-white/5">
                      <FileText class="text-[#D9534F] text-base" aria-hidden="true" />
                    </div>
                    <span class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></span>
                  </button>
                </div>
              </div>
              <p v-else class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mt-2.5 italic">
                <Info class="me-1" aria-hidden="true" />
                {{ $t("owner_applications_page.no_verification_documents") }}
              </p>

              <div class="mt-2.5 bg-[#f4efe5]/40 dark:bg-white/[0.03] rounded-lg p-2.5">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                  <span class="text-[10.5px] font-bold text-[#8A6D1F] dark:text-[#D4AF37]">
                    <StickyNote class="me-1" aria-hidden="true" />
                    {{ $t("owner_applications_page.internal_note_label") }}
                  </span>
                  <button
                    v-if="hasUnsavedInternalNote(app) && can('users.create')"
                    type="button"
                    class="text-[10px] font-bold text-[#52733D] disabled:opacity-50"
                    :disabled="savingInternalNoteId === app.id"
                    @click="saveInternalNote(app)"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="savingInternalNoteId === app.id" /><Save aria-hidden="true" v-else />
                    {{ $t("users_page.save_action") }}
                  </button>
                </div>
                <textarea
                  :value="internalNoteDraft(app)"
                  @input="setInternalNoteDraft(app, $event.target.value)"
                  :readonly="!can('users.create')"
                  rows="1"
                  maxlength="500"
                  class="w-full bg-transparent text-[11px] outline-none resize-none placeholder:text-[#9a9d97] dark:placeholder:text-[#8f938a]"
                  :placeholder="$t('owner_applications_page.internal_note_placeholder')"
                ></textarea>
              </div>

              <p v-if="app.status !== 'pending' && app.reviewed_by" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1.5">
                <UserCheck class="me-1" aria-hidden="true" />
                {{ $t("owner_applications_page.reviewed_by_line", { name: app.reviewed_by.name, date: app.reviewed_at ? app.reviewed_at.slice(0, 16).replace("T", " ") : "" }) }}
              </p>

              <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1.5">
                {{ $t("owner_applications_page.submitted_prefix") }}{{ app.created_at?.slice(0, 10) }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end print-hidden">
            <button
              type="button"
              class="icon-btn"
              :title="$t('owner_applications_page.view_full_details')"
              :aria-label="$t('owner_applications_page.view_full_details')"
              @click="openDetails(app)"
            >
              <Info aria-hidden="true" />
            </button>
            <a
              v-if="app.phone"
              :href="`https://wa.me/${String(app.phone).replace(/[^\\d]/g, '')}`"
              target="_blank"
              rel="noopener noreferrer"
              class="icon-btn !bg-[#25D366]/10 !text-[#25D366]"
              :title="$t('owner_applications_page.whatsapp_message')"
              :aria-label="$t('owner_applications_page.whatsapp_message')"
            >
              <i class="fa-brands fa-whatsapp"></i>
            </a>

            <template v-if="app.status === 'pending' && can('users.create')">
              <button
                type="button"
                class="text-[12px] font-bold px-3.5 py-2 rounded-full border border-[#D9534F]/30 text-[#D9534F] hover:bg-[#D9534F]/10 transition-colors disabled:opacity-50"
                :disabled="reviewingId === app.id"
                @click="openReject(app)"
              >
                <X class="me-1" aria-hidden="true" />{{ $t("owner_applications_page.reject_action") }}
              </button>
              <button
                type="button"
                class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12px] font-bold px-4 py-2 rounded-full shadow-md flex items-center gap-1.5 disabled:opacity-60"
                :disabled="reviewingId === app.id"
                @click="handleApprove(app)"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="reviewingId === app.id" /><Check aria-hidden="true" v-else />
                {{ $t("owner_applications_page.approve_action") }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <nav
      v-if="applicationsPagination.last_page > 1"
      class="flex items-center justify-center gap-2 print-hidden"
      :aria-label="$t('owner_applications_page.pagination_aria')"
    >
      <button
        type="button"
        class="icon-btn"
        :disabled="applicationsPagination.current_page <= 1"
        :aria-label="$t('common.previous_page')"
        @click="fetchApplications(applicationsPagination.current_page - 1)"
      >
        <ChevronRight class="rtl:block ltr:hidden" aria-hidden="true" />
        <ChevronLeft class="ltr:block rtl:hidden" aria-hidden="true" />
      </button>
      <span class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
        {{ applicationsPagination.current_page }} / {{ applicationsPagination.last_page }}
      </span>
      <button
        type="button"
        class="icon-btn"
        :disabled="applicationsPagination.current_page >= applicationsPagination.last_page"
        :aria-label="$t('common.next_page')"
        @click="fetchApplications(applicationsPagination.current_page + 1)"
      >
        <ChevronLeft class="rtl:block ltr:hidden" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden" aria-hidden="true" />
      </button>
    </nav>
    </template>

    <Teleport to="body">
      <div v-if="isViewOpen && viewingOwner" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="isViewOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--green shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Info aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.owner_details_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ viewingOwner.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isViewOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 grid sm:grid-cols-[1fr_1.2fr] gap-5 overflow-y-auto">
            <div class="space-y-4">
              <div class="glass-card p-4 text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-3" :style="{ background: `linear-gradient(135deg, ${avatarColor(0)[0]}, ${avatarColor(0)[1]})` }">
                  {{ initialsOf(viewingOwner.name) }}
                </div>
                <h4 class="text-[14.5px] font-extrabold mb-1.5">{{ viewingOwner.name }}</h4>
                <div class="flex items-center justify-center gap-2 flex-wrap">
                  <span v-if="viewingOwner.plan" class="status-chip chip-info">{{ viewingOwner.plan.name }}</span>
                  <span class="status-chip" :class="viewingOwner.is_locked ? 'chip-danger' : statusChip(viewingOwner.status)">
                    {{ viewingOwner.is_locked ? t("owners_page.locked") : statusLabel(viewingOwner.status) }}
                  </span>
                </div>
              </div>
              <div class="glass-card p-4 space-y-2.5 text-[12px]">
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.email_label") }}</span><b class="truncate max-w-[10rem]">{{ viewingOwner.email }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.phone_col") }}</span><b dir="ltr">{{ viewingOwner.phone ?? "-" }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.commission_col") }}</span><b>{{ commissionLabel(viewingOwner.commission_settings?.rate, viewingOwner.commission_settings?.mode) }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.joined_label") }}</span><b>{{ viewingOwner.created_at?.slice(0, 10) ?? "-" }}</b></div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="grid grid-cols-3 gap-2.5">
                <div class="glass-card p-3 text-center">
                  <div class="text-base font-extrabold">{{ viewingOwner.generators_count ?? 0 }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.generators_word_short") }}</div>
                </div>
                <div class="glass-card p-3 text-center">
                  <div class="text-base font-extrabold">{{ viewingOwner.active_subscriptions_count ?? 0 }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.subscribers_word") }}</div>
                </div>
                <div class="glass-card p-3 text-center">
                  <div class="text-sm font-extrabold">{{ fmtMoney(viewingOwner.monthly_revenue_ils) }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.revenue_word") }}</div>
                </div>
              </div>

              <div class="glass-card p-4">
                <h5 class="text-[12px] font-bold mb-2.5">{{ t("owners_page.partnership_summary") }}</h5>
                <div class="space-y-2.5">
                  <div class="timeline-item" style="--dot-color:#52733D">
                    <p class="text-[11.5px] font-semibold">
                      {{ t("owners_page.generators_count_summary", { count: viewingOwner.generators_count ?? 0 }) }}
                    </p>
                  </div>
                  <div class="timeline-item" style="--dot-color:#8A6D1F">
                    <p class="text-[11.5px] font-semibold">
                      {{ t("owners_page.subscribers_served_summary", { count: viewingOwner.active_subscriptions_count ?? 0 }) }}
                    </p>
                  </div>
                  <div v-if="viewingOwner.commission_rate !== null" class="timeline-item" style="--dot-color:#17A2B8">
                    <p class="text-[11.5px] font-semibold">
                      {{ t("owners_page.current_commission_summary", { rate: commissionLabel(viewingOwner.commission_settings?.rate, viewingOwner.commission_settings?.mode) }) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- ===== تقييمات المشتركين لهذا المالك ===== -->
              <div class="glass-card p-4">
                <div class="flex items-center justify-between mb-2.5">
                  <h5 class="text-[12px] font-bold flex items-center gap-1.5">
                    <Star class="text-[#D4AF37] text-[11px]" aria-hidden="true" />
                    {{ t("owner_dashboard.ratings_title") }}
                  </h5>
                  <div v-if="ownerRatingsCount > 0" class="flex items-center gap-1 shrink-0">
                    <span class="font-extrabold text-[13px]">{{ Number(ownerRatingsAverage).toFixed(1) }}</span>
                    <Star class="text-[#D4AF37] text-[11px]" fill="currentColor" aria-hidden="true" />
                    <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">({{ t("owner_dashboard.ratings_count", { count: ownerRatingsCount }) }})</span>
                  </div>
                </div>
                <div v-if="isLoadingOwnerRatings" class="text-center py-4 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("common.loading") }}</div>
                <div v-else-if="ownerRatingsError" class="text-center py-4 text-[11px] text-[#D9534F]">{{ ownerRatingsError }}</div>
                <div v-else-if="ownerRatingsCount === 0" class="text-center py-4 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owners_page.no_ratings_yet") }}</div>
                <div v-else class="space-y-2 max-h-48 overflow-y-auto">
                  <div v-for="r in ownerRatingsList" :key="r.id" class="p-2.5 rounded-xl border border-[#eee8da] dark:border-white/10">
                    <div class="flex items-center justify-between gap-2 mb-0.5">
                      <span class="text-[11px] font-bold">{{ r.rated_by?.name ?? "-" }}</span>
                      <div class="flex items-center gap-0.5 shrink-0">
                        <Star v-for="n in 5" :key="n" class="text-[10px]" :class="n <= r.rating ? 'text-[#D4AF37]' : 'text-[#e7e2d6] dark:text-white/15'" :fill="n <= r.rating ? 'currentColor' : 'none'" aria-hidden="true" />
                      </div>
                    </div>
                    <p v-if="r.comment" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ r.comment }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer-brand !justify-between flex-wrap shrink-0">
            <div class="flex items-center gap-1.5">
              <button v-if="can('users.update')" type="button" :disabled="isSendingResetLink" @click="handleSendResetLink(viewingOwner)" class="w-9 h-9 rounded-full flex items-center justify-center text-[#8A6D1F] dark:text-[#D4AF37] border border-[#8A6D1F]/40 hover:bg-[#8A6D1F]/10 disabled:opacity-50" :title="t('owners_page.send_reset_link_title')">
                <LoaderCircle class="text-[13px] animate-spin" aria-hidden="true" v-if="isSendingResetLink" /><Key class="text-[13px]" aria-hidden="true" v-else />
              </button>
              <button v-if="can('platform-commissions.manage-settings')" type="button" @click="openCommissionModal(viewingOwner)" class="w-9 h-9 rounded-full flex items-center justify-center text-[#17A2B8] border border-[#17A2B8]/40 hover:bg-[#17A2B8]/10" :title="t('owners_page.edit_commission_title')">
                <Percent class="text-[13px]" aria-hidden="true" />
              </button>
            </div>
            <div class="flex items-center gap-2.5">
              <button type="button" @click="isViewOpen = false" class="btn-outline-brand">{{ t("common.close") }}</button>
              <button v-if="can('users.update')" type="button" @click="switchToEditFromView" class="btn-fill-brand">
                <Pencil aria-hidden="true" /> {{ t("common.edit") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showCreateForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showCreateForm = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--green shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><UserPlus aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.create_account_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("owners_page.generator_owner_label") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="showCreateForm = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleCreate" class="p-5 space-y-3 overflow-y-auto">
            <div v-if="createError?.message" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ createError.message }}
            </div>

            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("dashboard.name") }}</label>
              <input v-model="createForm.name" type="text" required minlength="3" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p v-if="createError?.errors?.name" class="text-[10.5px] text-[#D9534F] mt-1">{{ createError.errors.name[0] }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.email_label") }}</label>
              <input v-model="createForm.email" type="email" required class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p v-if="createError?.errors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ createError.errors.email[0] }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.phone_optional_label") }}</label>
              <input v-model="createForm.phone" type="text" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("dashboard.password") }}</label>
              <input v-model="createForm.password" type="password" required minlength="10" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ t("dashboard.password_hint") }}</p>
              <p v-if="createError?.errors?.password" class="text-[10.5px] text-[#D9534F] mt-1">{{ createError.errors.password[0] }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("dashboard.confirm_password") }}</label>
              <input v-model="createForm.password_confirmation" type="password" required class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
            </div>

            <div class="flex gap-2.5 pt-2">
              <button type="button" @click="showCreateForm = false" class="btn-outline-brand flex-1">{{ t("dashboard.cancel") }}</button>
              <button type="submit" :disabled="isCreating" class="btn-fill-brand flex-1 justify-center">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreating" /><Check aria-hidden="true" v-else />
                {{ isCreating ? t("dashboard.creating") : t("dashboard.create_account") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="editingOwner" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="editingOwner = null">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Pencil aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.edit_owner_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ editingOwner.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="editingOwner = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 overflow-y-auto">
          <div v-if="saveError?.message" class="alert-box mb-3">
            <CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveError.message }}
          </div>

          <div class="space-y-3">
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("dashboard.name") }}</label>
              <input v-model="editForm.name" type="text" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.email_label") }}</label>
              <input v-model="editForm.email" type="email" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p v-if="saveError?.errors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ saveError.errors.email[0] }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.phone_only_label") }}</label>
              <input v-model="editForm.phone" type="text" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("dashboard.status") }}</label>
              <AppDropdownSelect v-model="editForm.status" :options="editOwnerStatusOptions" variant="field" width-class="w-full" match-trigger-width />
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button type="button" @click="editingOwner = null" class="btn-outline-brand flex-1">{{ t("dashboard.cancel") }}</button>
            <button type="button" @click="handleSave" :disabled="isSaving" class="btn-fill-brand flex-1 justify-center">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
              {{ isSaving ? t("owners_page.saving_label") : t("owners_page.save_label") }}
            </button>
          </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="planModal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="planModal.open = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Crown aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.assign_plan_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ planModal.owner?.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="planModal.open = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 overflow-y-auto">
          <div v-if="planError" class="alert-box mb-3">
            <CircleAlert class="shrink-0" aria-hidden="true" /> {{ planError }}
          </div>

          <div class="space-y-2">
            <label
              v-for="plan in plans" :key="plan.id"
              class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-colors"
              :class="planModal.planId === plan.id ? 'border-[#8A6D1F] bg-[#f4efe5]/60 dark:bg-white/5' : 'border-[#e7e2d6] dark:border-white/10'"
            >
              <div>
                <p class="text-[12.5px] font-bold">{{ plan.name }}</p>
                <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">
                  {{ plan.max_generators ? t("owners_page.up_to_n_generators", { n: plan.max_generators }) : t("owners_page.unlimited_label") }}
                  — {{ plan.price_monthly }} {{ plan.currency }}/{{ t("owners_page.monthly_suffix") }}
                </p>
              </div>
              <input type="radio" :value="plan.id" v-model="planModal.planId" class="accent-[#8A6D1F]" />
            </label>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button type="button" @click="planModal.open = false" class="btn-outline-brand flex-1">{{ t("dashboard.cancel") }}</button>
            <button type="button" :disabled="!planModal.planId || isAssigningPlan" @click="assignPlan" class="btn-fill-brand flex-1 justify-center">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isAssigningPlan" /><Check aria-hidden="true" v-else />
              {{ isAssigningPlan ? t("owners_page.saving_label") : t("owners_page.assign_label") }}
            </button>
          </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="commissionModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="commissionModal = null">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--blue shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Percent aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.commission_settings_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ commissionModal.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="commissionModal = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleSaveCommission" class="p-5 space-y-3 overflow-y-auto">
            <div v-if="commissionError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ commissionError }}
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.commission_mode_label") }}</label>
              <AppDropdownSelect v-model="commissionForm.commission_mode" :options="commissionModeOptions" variant="field" width-class="w-full" match-trigger-width />
              <p v-if="commissionForm.commission_mode === 'tiered'" class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mt-1.5">
                {{ t("owners_page.tiered_hint") }}
              </p>
            </div>
            <div v-if="commissionForm.commission_mode === 'fixed'">
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.commission_rate_label") }}</label>
              <input v-model="commissionForm.commission_rate" type="number" min="0" max="100" step="0.1" required class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#17A2B8]" />
            </div>
            <div class="flex gap-2.5 pt-2">
              <button type="button" @click="commissionModal = null" class="btn-outline-brand flex-1">{{ t("dashboard.cancel") }}</button>
              <button type="submit" :disabled="isSavingCommission" class="btn-fill-brand flex-1 justify-center">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingCommission" /><Check aria-hidden="true" v-else />
                {{ isSavingCommission ? t("owners_page.saving_label") : t("owners_page.save_label") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="transferGenModal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="transferGenModal.open = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><PlugZap aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.transfer_ownership_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("owners_page.between_owners") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="transferGenModal.open = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 overflow-y-auto">
          <div v-if="transferGenError" class="alert-box mb-3">
            <CircleAlert class="shrink-0" aria-hidden="true" /> {{ transferGenError }}
          </div>

          <form @submit.prevent="submitTransferGenerator" class="space-y-3">
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("dashboard.generator_col") }}</label>
              <AppDropdownSelect
                v-model="transferGenForm.generator_id"
                :options="transferGeneratorOptions"
                :disabled="isLoadingGeneratorsForTransfer"
                :placeholder="isLoadingGeneratorsForTransfer ? t('owners_page.loading_label') : t('owners_page.select_generator_label')"
                variant="field"
                width-class="w-full"
                match-trigger-width
              />
              <p v-if="!isLoadingGeneratorsForTransfer && allGeneratorsForTransfer.length === 0" class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mt-1">
                {{ t("owners_page.no_generators_yet") }}
              </p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("owners_page.new_owner_label") }}</label>
              <AppDropdownSelect
                v-model="transferGenForm.owner_id"
                :options="transferOwnerOptions"
                :placeholder="t('owners_page.select_owner_label')"
                variant="field"
                width-class="w-full"
                match-trigger-width
              />
            </div>

            <div class="flex gap-2.5 pt-2">
              <button type="button" @click="transferGenModal.open = false" class="btn-outline-brand flex-1">{{ t("dashboard.cancel") }}</button>
              <button type="submit" :disabled="isTransferringGenerator || !transferGenForm.generator_id || !transferGenForm.owner_id" class="btn-fill-brand flex-1 justify-center">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isTransferringGenerator" /><Check aria-hidden="true" v-else />
                {{ isTransferringGenerator ? t("owners_page.transferring_label") : t("owners_page.transfer_ownership_btn") }}
              </button>
            </div>
          </form>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isDeleteModalOpen && deletingOwner" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeDeleteModal">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--danger">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Trash2 aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("owners_page.delete_owner_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("dashboard.delete_generator_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="closeDeleteModal" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 space-y-3.5 text-center">
            <div class="w-14 h-14 rounded-full bg-[#D9534F]/10 flex items-center justify-center mx-auto">
              <TriangleAlert class="text-[#D9534F] text-xl" aria-hidden="true" />
            </div>
            <p class="text-[12.5px] leading-relaxed text-[#4b4b4b] dark:text-[#d7d9d3]">
              {{ t("owners_page.delete_owner_confirm_message", { name: deletingOwner.name }) }}
            </p>
            <div v-if="deleteError" class="alert-box text-start">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ deleteError }}
            </div>
          </div>

          <div class="modal-footer-brand">
            <button type="button" @click="closeDeleteModal" class="btn-outline-brand">{{ t("dashboard.cancel") }}</button>
            <button type="button" @click="confirmDeleteOwner" :disabled="deletingId === deletingOwner.id" class="btn-fill-brand btn-fill-brand--danger">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === deletingOwner.id" /><Trash2 aria-hidden="true" v-else />
              {{ t("dashboard.confirm_delete") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <AdminGeneratorFormModal
      :open="isAddGeneratorModalOpen"
      :generator="null"
      :owners="owners"
      :is-saving="isSavingGenerator"
      :save-error="generatorSaveError"
      @close="closeAddGeneratorModal"
      @submit="handleCreateGenerator"
    />

    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="isRejectOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reject-modal-title"
        @click.self="closeReject"
        @keydown.esc="closeReject"
      >
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--danger">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><X aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 id="reject-modal-title" class="modal-head-brand__title">{{ $t("owner_applications_page.rejection_reason_title") }}</h3>
                <p class="modal-head-brand__subtitle">
                  {{ rejectBulkIds?.length ? $t("owner_applications_page.n_selected_requests", { count: rejectBulkIds.length }) : rejectTarget?.name }}
                </p>
              </div>
            </div>
            <button type="button" @click="closeReject" class="modal-head-brand__close" :aria-label="$t('common.close')"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5">
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-3">
              {{
                rejectBulkIds?.length
                  ? $t("owner_applications_page.reject_bulk_message", { count: rejectBulkIds.length })
                  : $t("owner_applications_page.reject_single_message", { name: rejectTarget?.name })
              }}
            </p>
            <textarea
              v-model="rejectReason"
              rows="3"
              maxlength="500"
              :aria-label="$t('owner_applications_page.rejection_reason_label')"
              class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"
              :placeholder="$t('owner_applications_page.rejection_reason_placeholder')"
            ></textarea>
          </div>

          <div class="modal-footer-brand">
            <button type="button" @click="closeReject" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button
              type="button"
              class="btn-fill-brand btn-fill-brand--danger"
              :disabled="reviewingId === rejectTarget?.id || isBulkProcessing"
              @click="submitReject"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="(reviewingId === rejectTarget?.id || isBulkProcessing)" /><Check aria-hidden="true" v-else />
              {{ $t("owner_applications_page.confirm_rejection") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="isDocViewerOpen && activeDoc"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-label="$t('owner_applications_page.document_viewer_aria')"
        @click.self="closeDocViewer"
        @keydown.esc="closeDocViewer"
      >
        <div class="relative w-full max-w-2xl">
          <button type="button" class="absolute -top-10 end-0 text-white/80 hover:text-white" :aria-label="$t('common.close')" @click="closeDocViewer">
            <X class="text-xl" aria-hidden="true" />
          </button>
          <div class="bg-white dark:bg-[#1c1e20] rounded-2xl overflow-hidden w-full flex flex-col shadow-2xl">
            <div class="flex-1 flex items-center justify-center bg-black/5 dark:bg-black/30 min-h-[260px] max-h-[65vh] overflow-auto">
              <img v-if="isImageDoc(activeDoc)" :src="activeDoc.url" :alt="activeDoc.name" class="max-w-full max-h-[65vh] object-contain" />
              <div v-else class="flex flex-col items-center gap-3 p-10 text-center">
                <FileText class="text-[#D9534F] text-4xl" aria-hidden="true" />
                <span class="text-[12px] font-semibold">{{ activeDoc.name }}</span>
              </div>
            </div>
            <div class="flex items-center justify-between gap-2 px-4 py-3 border-t border-[#eee8da] dark:border-white/10">
              <button type="button" class="icon-btn !w-8 !h-8" :disabled="docCount() <= 1" :aria-label="$t('owner_applications_page.previous_document')" @click="prevDoc">
                <ChevronRight class="rtl:block ltr:hidden" aria-hidden="true" />
                <ChevronLeft class="ltr:block rtl:hidden" aria-hidden="true" />
              </button>
              <div class="text-[11.5px] font-semibold truncate px-2">{{ activeDoc.name }}</div>
              <div class="flex items-center gap-1.5 shrink-0">
                <a :href="activeDoc.url" target="_blank" rel="noopener" download class="icon-btn !w-8 !h-8" :title="$t('owner_applications_page.download_short')" :aria-label="$t('owner_applications_page.download_document_aria')">
                  <Download class="text-[11px]" aria-hidden="true" />
                </a>
                <button type="button" class="icon-btn !w-8 !h-8" :disabled="docCount() <= 1" :aria-label="$t('owner_applications_page.next_document')" @click="nextDoc">
                  <ChevronLeft class="rtl:block ltr:hidden" aria-hidden="true" />
                  <ChevronRight class="ltr:block rtl:hidden" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="isDetailsOpen && detailsApp"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="details-modal-title"
        @click.self="closeDetails"
        @keydown.esc="closeDetails"
      >
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--green shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Info aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 id="details-modal-title" class="modal-head-brand__title">{{ $t("owner_applications_page.details_modal_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ detailsApp.name }}</p>
              </div>
            </div>
            <button type="button" @click="closeDetails" class="modal-head-brand__close" :aria-label="$t('common.close')"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 grid sm:grid-cols-[1fr_1.1fr] gap-5 overflow-y-auto">
            <div class="space-y-4">
              <div class="glass-card p-4">
                <h4 class="text-[12px] font-bold mb-2.5 text-[#3E582E] dark:text-[#8cc35a]">
                  <User class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.applicant_info") }}
                </h4>
                <div class="space-y-2 text-[12px]">
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.name") }}</span><b class="text-end truncate max-w-[10rem]">{{ detailsApp.name }}</b></div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.email_label") }}</span><b class="text-end truncate max-w-[10rem]" dir="ltr">{{ detailsApp.email }}</b></div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.phone_label") }}</span><b class="text-end" dir="ltr">{{ detailsApp.phone ?? "-" }}</b></div>
                  <div class="flex justify-between gap-2">
                    <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.status") }}</span>
                    <span class="status-chip" :class="STATUS_CHIP[detailsApp.status]"><AppIcon :name="STATUS_ICON[detailsApp.status]" class="me-1" />{{ detailsApp.status_label }}</span>
                  </div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.submitted_label") }}</span><b>{{ detailsApp.created_at?.slice(0, 16).replace("T", " ") }}</b></div>
                  <div v-if="detailsApp.notes" class="pt-2 border-t border-[#eee8da] dark:border-white/10">
                    <span class="text-[#9a9d97] dark:text-[#8f938a] block mb-1">{{ $t("owner_applications_page.applicant_notes") }}</span>
                    <p class="leading-relaxed">{{ detailsApp.notes }}</p>
                  </div>
                </div>
              </div>

              <div v-if="detailsApp.status !== 'pending'" class="glass-card p-4">
                <h4 class="text-[12px] font-bold mb-2.5 text-[#3E582E] dark:text-[#8cc35a]">
                  <UserCheck class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.review_info") }}
                </h4>
                <div class="space-y-2 text-[12px]">
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.reviewed_by_label") }}</span><b>{{ detailsApp.reviewed_by?.name ?? "-" }}</b></div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.reviewed_at_label") }}</span><b>{{ detailsApp.reviewed_at?.slice(0, 16).replace("T", " ") ?? "-" }}</b></div>
                  <div v-if="detailsApp.status === 'rejected' && detailsApp.review_note" class="pt-2 border-t border-[#eee8da] dark:border-white/10">
                    <span class="text-[#D9534F] block mb-1">{{ $t("owner_applications_page.rejection_reason_label") }}</span>
                    <p class="leading-relaxed text-[#D9534F]">{{ detailsApp.review_note }}</p>
                  </div>
                  <div v-if="detailsApp.status === 'approved' && detailsApp.created_user" class="pt-2 border-t border-[#eee8da] dark:border-white/10">
                    <span class="text-[#9a9d97] dark:text-[#8f938a] block mb-1">{{ $t("owner_applications_page.created_account_label") }}</span>
                    <p class="font-semibold">{{ detailsApp.created_user.name }} <span class="text-[#9a9d97] dark:text-[#8f938a] font-normal" dir="ltr">({{ detailsApp.created_user.email }})</span></p>
                  </div>
                </div>
              </div>

              <div v-if="detailsApp.is_duplicate_email || detailsApp.is_duplicate_phone" class="glass-card p-4 border border-[#D4AF37]/40">
                <h4 class="text-[12px] font-bold mb-2 text-[#8A6D1F] dark:text-[#D4AF37]"><TriangleAlert class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.duplicate_data_title") }}</h4>
                <p class="text-[11.5px] leading-relaxed">
                  {{ $t("owner_applications_page.matches_existing_account_prefix") }}
                  <b>{{ detailsApp.duplicate_user_name }}</b> <span dir="ltr">({{ detailsApp.duplicate_user_email }})</span>
                </p>
                <button type="button" class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] underline mt-1.5" @click="goToDuplicateUser(detailsApp)">
                  {{ $t("owner_applications_page.view_users_link") }}
                </button>
              </div>

              <div class="glass-card p-4">
                <h4 class="text-[12px] font-bold mb-2.5 text-[#3E582E] dark:text-[#8cc35a]">
                  <Paperclip class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.attached_documents") }} ({{ detailsApp.documents?.length ?? 0 }})
                </h4>
                <div v-if="detailsApp.documents?.length" class="flex items-center gap-2 overflow-x-auto pb-1">
                  <button
                    v-for="(doc, idx) in detailsApp.documents" :key="doc.id ?? idx" type="button"
                    class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border border-[#e7e2d6] dark:border-white/10 relative group"
                    :title="doc.name"
                    @click="openDocViewer(detailsApp, idx)"
                  >
                    <img v-if="isImageDoc(doc)" :src="doc.thumbnail_url ?? doc.url" class="w-full h-full object-cover" :alt="doc.name" />
                    <div v-else class="w-full h-full flex items-center justify-center bg-[#f4efe5]/70 dark:bg-white/5">
                      <FileText class="text-[#D9534F] text-base" aria-hidden="true" />
                    </div>
                    <span class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></span>
                  </button>
                </div>
                <p v-else class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] italic">{{ $t("owner_applications_page.no_documents_attached") }}</p>
              </div>
            </div>

            <div class="space-y-4">
              <div v-if="detailsApp.generator_draft?.name" class="glass-card p-4">
                <h4 class="text-[12px] font-bold mb-2.5 text-[#3E582E] dark:text-[#8cc35a]">
                  <PlugZap class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.requested_generator") }}
                </h4>
                <div class="space-y-2 text-[12px]">
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.name") }}</span><b class="text-end truncate max-w-[10rem]">{{ detailsApp.generator_draft.name }}</b></div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.price_per_kw_short") }}</span><b>{{ fmtGeneratorPrice(detailsApp.generator_draft) }}</b></div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.capacity_label") }}</span><b>{{ detailsApp.generator_draft.capacity_kw ? `${detailsApp.generator_draft.capacity_kw} kW` : "-" }}</b></div>
                  <div class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.location_label") }}</span><b class="text-end truncate max-w-[10rem]">{{ generatorLocationLabel(detailsApp.generator_draft) }}</b></div>
                  <div v-if="detailsApp.generator_draft.address" class="flex justify-between gap-2"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.address_label") }}</span><b class="text-end truncate max-w-[10rem]">{{ detailsApp.generator_draft.address }}</b></div>
                  <div v-if="detailsApp.generator_draft.latitude && detailsApp.generator_draft.longitude" class="flex justify-between gap-2">
                    <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("owner_applications_page.coordinates_label") }}</span>
                    <b dir="ltr">{{ detailsApp.generator_draft.latitude }}, {{ detailsApp.generator_draft.longitude }}</b>
                  </div>
                </div>
              </div>

              <div class="glass-card p-4">
                <h4 class="text-[12px] font-bold mb-2 text-[#8A6D1F] dark:text-[#D4AF37]"><StickyNote class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.internal_note_title") }}</h4>
                <p class="text-[11.5px] leading-relaxed" :class="detailsApp.internal_note ? '' : 'text-[#9a9d97] dark:text-[#8f938a] italic'">
                  {{ detailsApp.internal_note || $t("owner_applications_page.no_internal_note") }}
                </p>
              </div>

              <div class="glass-card p-4">
                <h4 class="text-[12px] font-bold mb-2.5 text-[#3E582E] dark:text-[#8cc35a]">
                  <RotateCcwClock class="me-1.5" aria-hidden="true" />{{ $t("owner_applications_page.review_history_title") }}
                </h4>
                <div v-if="isLoadingHistory" class="space-y-2">
                  <div v-for="i in 3" :key="i" class="h-8 rounded-lg thumb-loading"></div>
                </div>
                <div v-else-if="reviewHistory.length === 0" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] italic">
                  {{ $t("owner_applications_page.no_review_history") }}
                </div>
                <div v-else class="space-y-2.5">
                  <div v-for="log in reviewHistory" :key="log.id" class="timeline-item" :style="{ '--dot-color': historyEventMeta(log).color }">
                    <p class="text-[11.5px] font-semibold">
                      <AppIcon :name="historyEventMeta(log).icon" class="text-[10px] me-1" :style="{ color: historyEventMeta(log).color }" />
                      {{ historyEventLabel(log) }}
                      <span v-if="log.causer" class="font-normal text-[#6B6B6B] dark:text-[#a8aaa5]"> — {{ log.causer.name }}</span>
                    </p>
                    <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ log.created_at?.slice(0, 16).replace("T", " ") }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer-brand !justify-between flex-wrap shrink-0">
            <span v-if="isLoadingDetails" class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]"><LoaderCircle class="me-1 animate-spin" aria-hidden="true" />{{ $t("owner_applications_page.loading_full_details") }}</span>
            <span v-else></span>
            <div class="flex items-center gap-2.5">
              <button type="button" @click="closeDetails" class="btn-outline-brand">{{ $t("common.close") }}</button>
              <template v-if="detailsApp.status === 'pending'">
                <button type="button" class="btn-fill-brand btn-fill-brand--danger" @click="detailsSwitchToReject">
                  <X aria-hidden="true" />{{ $t("owner_applications_page.reject_action") }}
                </button>
                <button type="button" class="btn-fill-brand" @click="detailsApprove">
                  <Check aria-hidden="true" />{{ $t("owner_applications_page.approve_action") }}
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>
  </div>
</template>