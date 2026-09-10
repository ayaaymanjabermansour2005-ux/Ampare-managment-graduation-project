<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { reactive, ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminTechnicians } from "@/composables/useAdminTechnicians";
import { useOwnerOptions } from "@/composables/useOwnerOptions";
import { useTechnicianAttachments } from "@/composables/useTechnicianAttachments";
import { useConfirm } from "@/composables/useConfirm";
import { usePermissions } from "@/composables/usePermissions";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import technicianTaskService from "@/services/technicianTaskService";
import technicianService from "@/services/technicianService";
import generatorService from "@/services/generatorService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import AttachmentPreviewModal from "@/components/ui/AttachmentPreviewModal.vue";
import { Ban, Check, ChevronLeft, ChevronRight, ClipboardCheck, Eye, FileSpreadsheet, LoaderCircle, LockOpen, Mail, Paperclip, Pencil, Phone, Plus, Search, Star, Trash2, UserCog, UserPlus, UserRound, Wrench, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t, locale } = useI18n();

const {
  technicians,
  pagination,
  isLoading,
  error,
  searchTerm,
  fetchTechnicians,
  isCreating,
  createError,
  createTechnician,
  isSaving,
  saveError,
  updateTechnician,
  deletingId,
  deleteError,
  deleteTechnician,
  unlockTechnician,
} = useAdminTechnicians();

const { confirm } = useConfirm();
const { can } = usePermissions();
const toast = useToastStore();

const techniciansExportUrl = computed(() => technicianService.exportUrl({ search: searchTerm.value }));

/* ---------------- إضافة فني جديد (يتطلب اختيار مالك إلزاميًا) ---------------- */
const { owners, isLoadingOwners, ownerSelectOptions, fetchOwners } = useOwnerOptions();
const isCreateOpen = ref(false);
const createForm = reactive({ owner_id: "", name: "", email: "", password: "", password_confirmation: "", notes: "" });

function openCreate() {
  createForm.owner_id = "";
  createForm.name = "";
  createForm.email = "";
  createForm.password = "";
  createForm.password_confirmation = "";
  createForm.notes = "";
  createError.value = null;
  isCreateOpen.value = true;
  if (owners.value.length === 0) fetchOwners();
}

async function handleCreate() {
  const ok = await createTechnician({ ...createForm });
  if (ok) {
    isCreateOpen.value = false;
    toast.show({ type: "success", title: t("admin_technicians_page.created_toast_title"), message: t("admin_technicians_page.created_toast_message") });
  } else {
    toast.show({ type: "danger", title: t("admin_technicians_page.save_failed_title"), message: createError.value?.message ?? t("admin_technicians_page.create_error") });
  }
}

let searchTimeout = null;
function handleSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchTechnicians(1), 400);
}

/* ==========================================================================
 * ==========================  التابات (فنيون / صيانة)  ====================
 * ========================================================================== */
const TABS = computed(() => [
  { key: "technicians", label: t("admin_technicians_page.tab_technicians"), icon: "fa-user-gear" },
  { key: "maintenance", label: t("admin_technicians_page.tab_maintenance"), icon: "fa-screwdriver-wrench" },
]);
const activeTab = ref("technicians");

/* ---------------- فلتر الحالة (فنيون) ---------------- */
const STATUS_PILLS = computed(() => [
  { value: "", label: t("admin_technicians_page.status_all") },
  { value: "locked", label: t("admin_technicians_page.status_locked") },
  { value: "active", label: t("admin_technicians_page.status_active") },
]);
const statusFilter = ref("");

const filteredTechnicians = computed(() => {
  if (statusFilter.value === "locked") return technicians.value.filter((t) => t.is_locked);
  if (statusFilter.value === "active") return technicians.value.filter((t) => !t.is_locked);
  return technicians.value;
});

/* FIX (تدقيق شامل — A1): "مشغول" الآن مبني على وجود أمر شغل نشط فعليًا
 * لهذا الفني (has_active_task من الباك)، بدل حقل availability_status
 * غير الموجود أصلًا. */
const availabilityOf = (t) => {
  if (t.is_locked) return "unavailable";
  return t.has_active_task ? "busy" : "available";
};
const countByAvailability = (status) => technicians.value.filter((t) => availabilityOf(t) === status).length;
const avgRating = computed(() => {
  const rated = technicians.value.filter((t) => typeof t.rating === "number");
  if (!rated.length) return "—";
  return (rated.reduce((sum, t) => sum + t.rating, 0) / rated.length).toFixed(1);
});
const KPI_CARDS = computed(() => [
  { icon: "fa-user-gear", label: t("admin_technicians_page.kpi_total"), value: pagination.value.total, tone: "secondary" },
  { icon: "fa-circle-check", label: t("admin_technicians_page.kpi_available_now"), value: countByAvailability("available"), tone: "success" },
  { icon: "fa-briefcase", label: t("admin_technicians_page.kpi_busy"), value: countByAvailability("busy"), tone: "info" },
  { icon: "fa-circle-xmark", label: t("admin_technicians_page.kpi_unavailable"), value: countByAvailability("unavailable"), tone: "danger" },
  { icon: "fa-star", label: t("admin_technicians_page.kpi_avg_rating"), value: avgRating.value, decimals: 1, tone: "secondary" },
]);

/* ---------------- Pagination بأزرار محدودة (فنيون) ---------------- */
function buildPaginationRange(current, total) {
  const delta = 1;
  const range = [];
  const withDots = [];
  let last = null;

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }
  for (const i of range) {
    if (last !== null) {
      if (i - last === 2) withDots.push(last + 1);
      else if (i - last > 2) withDots.push("...");
    }
    withDots.push(i);
    last = i;
  }
  return withDots;
}

const paginationRange = computed(() => buildPaginationRange(pagination.value.current_page, pagination.value.last_page));

/* ---------------- تعديل بيانات الفني ---------------- */
const editingTechnician = ref(null);
const editForm = reactive({ name: "", email: "", phone: "" });

function openEdit(technician) {
  editingTechnician.value = technician;
  editForm.name = technician.name;
  editForm.email = technician.email;
  editForm.phone = technician.phone ?? "";
  saveError.value = null;
}

async function handleSave() {
  const ok = await updateTechnician(editingTechnician.value.id, {
    ...editForm,
  });
  if (ok) {
    editingTechnician.value = null;
    toast.show({ type: "success", title: t("admin_technicians_page.updated_toast_title"), message: t("admin_technicians_page.updated_toast_message") });
  } else {
    toast.show({ type: "danger", title: t("admin_technicians_page.save_failed_title"), message: saveError.value?.message ?? t("admin_technicians_page.update_error") });
  }
}

async function handleDelete(technician) {
  const confirmed = await confirm({
    title: t("admin_technicians_page.delete_confirm_title", { name: technician.name }),
    message: t("admin_technicians_page.delete_confirm_message"),
    confirmLabel: t("admin_technicians_page.delete_confirm_button"),
    variant: "danger",
  });
  if (!confirmed) return;

  const ok = await deleteTechnician(technician.id);
  if (ok) {
    toast.show({ type: "success", title: t("admin_technicians_page.deleted_toast_title"), message: t("admin_technicians_page.deleted_toast_message") });
  } else {
    toast.show({ type: "danger", title: t("admin_technicians_page.save_failed_title"), message: deleteError.value?.message ?? t("admin_technicians_page.delete_error") });
  }
}

onMounted(() => fetchTechnicians());

/* ==========================================================================
 * FIX (تدقيق شامل للوحة الأدمن — بندان 15 و5): technicianService.show()
 * ومرفقات الفني (GET/POST technicians/{id}/attachments) كانا جاهزين بالكامل
 * بالباك اند بلا أي واجهة استخدام. نافذة واحدة تجمع بين عرض تفاصيل الفني
 * وإدارة مرفقاته (شهادة/هوية) — نفس منطق الارتباط الطبيعي بين الميزتين.
 * ========================================================================== */
const viewingTechnician = ref(null);
const isLoadingTechnicianView = ref(false);

const {
  attachments: technicianAttachments,
  isLoading: isLoadingTechnicianAttachments,
  error: technicianAttachmentsError,
  fetchAttachments: fetchTechnicianAttachments,
  isUploading: isUploadingTechnicianAttachment,
  uploadError: technicianAttachmentUploadError,
  uploadAttachment: uploadTechnicianAttachment,
} = useTechnicianAttachments();

const TECHNICIAN_DOCUMENT_TYPE_OPTIONS = computed(() => [
  { value: "technician_certificate", label: t("admin_technicians_page.document_type_certificate") },
  { value: "technician_identity", label: t("admin_technicians_page.document_type_identity") },
]);
const newTechnicianAttachmentType = ref("technician_certificate");
const newTechnicianAttachmentFile = ref(null);
const newTechnicianAttachmentDescription = ref("");
const technicianAttachmentFileInput = ref(null);
const previewingTechnicianAttachment = ref(null);

async function openView(technician) {
  viewingTechnician.value = technician;
  isLoadingTechnicianView.value = true;
  newTechnicianAttachmentType.value = "technician_certificate";
  newTechnicianAttachmentFile.value = null;
  newTechnicianAttachmentDescription.value = "";
  try {
    const { data } = await technicianService.show(technician.id);
    viewingTechnician.value = data.data;
  } catch {
    // تفاصيل الصفّ الأصلي (من القائمة) تبقى معروضة كحد أدنى لو فشل التحميل.
  } finally {
    isLoadingTechnicianView.value = false;
  }
  fetchTechnicianAttachments(technician.id);
}

function onTechnicianAttachmentFileChange(e) {
  newTechnicianAttachmentFile.value = e.target.files?.[0] ?? null;
}

async function handleUploadTechnicianAttachment() {
  if (!newTechnicianAttachmentFile.value || !viewingTechnician.value) return;
  const ok = await uploadTechnicianAttachment(viewingTechnician.value.id, {
    documentType: newTechnicianAttachmentType.value,
    file: newTechnicianAttachmentFile.value,
    description: newTechnicianAttachmentDescription.value || undefined,
  });
  if (ok) {
    newTechnicianAttachmentFile.value = null;
    newTechnicianAttachmentDescription.value = "";
    if (technicianAttachmentFileInput.value) technicianAttachmentFileInput.value.value = "";
    toast.show({
      type: "success",
      title: t("admin_technicians_page.attachments_title"),
      message: t("admin_technicians_page.attachment_uploaded_message"),
    });
  }
}

const AVAILABILITY_META = {
  available: "admin_technicians_page.availability_available",
  busy: "admin_technicians_page.availability_busy",
  unavailable: "admin_technicians_page.availability_unavailable",
};
function availabilityLabel(technician) {
  const status = availabilityOf(technician);
  return t(AVAILABILITY_META[status] ?? AVAILABILITY_META.unavailable);
}

const MAINTENANCE_STATUS_META = {
  pending: { chip: "chip-info" },
  assigned: { chip: "chip-info" },
  on_the_way: { chip: "chip-info" },
  in_progress: { chip: "chip-warning" },
  waiting_parts: { chip: "chip-warning" },
  submitted: { chip: "chip-warning" },
  approved: { chip: "chip-success" },
  rejected: { chip: "chip-danger" },
  cancelled: { chip: "chip-danger" },
};

const TASK_TYPE_OPTIONS = computed(() => [
  { value: "general_maintenance", label: t("admin_technicians_page.task_type_general_maintenance") },
  { value: "fault_repair", label: t("admin_technicians_page.task_type_fault_repair") },
  { value: "wiring_maintenance", label: t("admin_technicians_page.task_type_wiring_maintenance") },
  { value: "meter_reading", label: t("admin_technicians_page.task_type_meter_reading") },
  { value: "new_subscription_installation", label: t("admin_technicians_page.task_type_new_subscription_installation") },
]);
/* عرض نوع/حالة مهمة الصيانة عبر خرائط i18n بدل الاعتماد على النص الجاهز
 * من الباك اند (type_label / status_label) — يبقى متزامنًا مع لغة الواجهة */
function taskTypeLabel(type) {
  return TASK_TYPE_OPTIONS.value.find((opt) => opt.value === type)?.label ?? type;
}
function maintenanceStatusLabel(status) {
  return MAINTENANCE_STATUS_PILLS.value.find((opt) => opt.value === status)?.label ?? status;
}

/* ---------------- قائمة المولدات (لنموذج طلب صيانة جديد) ---------------- */
const generatorOptions = ref([]);
async function ensureGeneratorOptionsLoaded() {
  if (generatorOptions.value.length > 0) return;
  const { data } = await generatorService.list({ per_page: 200 });
  generatorOptions.value = data.data.data ?? data.data;
}
const generatorSelectOptions = computed(() => generatorOptions.value.map((g) => ({ value: g.id, label: g.name })));
const technicianSelectOptions = computed(() => technicians.value.map((tech) => ({ value: tech.id, label: tech.name })));
const technicianSelectOptionsWithNone = computed(() => [
  { value: "", label: t("admin_technicians_page.none_option") },
  ...technicianSelectOptions.value,
]);

const maintenanceRequests = ref([]);
const isMaintenanceLoading = ref(false);
const maintenancePagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 });

async function loadMaintenanceRequests(page = 1) {
  isMaintenanceLoading.value = true;
  try {
    const { data } = await technicianTaskService.list({
      page,
      per_page: 10,
      status: maintenanceStatusFilter.value || undefined,
      search: maintenanceSearchTerm.value.trim() || undefined,
    });
    const payload = data.data;
    maintenanceRequests.value = payload.data ?? payload;
    maintenancePagination.value = {
      current_page: payload.current_page ?? 1,
      last_page: payload.last_page ?? 1,
      total: payload.total ?? maintenanceRequests.value.length,
      per_page: payload.per_page ?? 10,
    };
  } finally {
    isMaintenanceLoading.value = false;
  }
}

const maintenanceStats = ref({ total: 0, active: 0, pending_review: 0, approved: 0 });
async function loadMaintenanceStats() {
  const { data } = await technicianTaskService.stats();
  maintenanceStats.value = data.data;
}

const maintenanceSearchTerm = ref("");
const maintenanceStatusFilter = ref("");

let maintenanceSearchDebounce = null;
function onMaintenanceSearchInput() {
  clearTimeout(maintenanceSearchDebounce);
  maintenanceSearchDebounce = setTimeout(() => loadMaintenanceRequests(1), 300);
}
function onMaintenanceStatusFilterChange() {
  loadMaintenanceRequests(1);
}

const MAINTENANCE_STATUS_PILLS = computed(() => [
  { value: "", label: t("admin_technicians_page.status_all") },
  { value: "pending", label: t("admin_technicians_page.maintenance_status_pending") },
  { value: "assigned", label: t("admin_technicians_page.maintenance_status_assigned") },
  { value: "on_the_way", label: t("admin_technicians_page.maintenance_status_on_the_way") },
  { value: "in_progress", label: t("admin_technicians_page.maintenance_status_in_progress") },
  { value: "waiting_parts", label: t("admin_technicians_page.maintenance_status_waiting_parts") },
  { value: "submitted", label: t("admin_technicians_page.maintenance_status_submitted") },
  { value: "approved", label: t("admin_technicians_page.maintenance_status_approved") },
  { value: "rejected", label: t("admin_technicians_page.maintenance_status_rejected") },
  { value: "cancelled", label: t("admin_technicians_page.maintenance_status_cancelled") },
]);

const ACTIVE_TASK_STATUSES = ["pending", "assigned", "on_the_way", "in_progress", "waiting_parts"];

/* ---------------- KPI Cards (طلبات الصيانة — إجماليات حقيقية من الباك، وليست الصفحة الحالية فقط) ---------------- */
const MAINTENANCE_KPI_CARDS = computed(() => [
  { icon: "fa-clipboard-list", label: t("admin_technicians_page.kpi_maintenance_total"), value: maintenanceStats.value.total, tone: "secondary" },
  { icon: "fa-gears", label: t("admin_technicians_page.kpi_maintenance_active"), value: maintenanceStats.value.active, tone: "info" },
  { icon: "fa-clipboard-check", label: t("admin_technicians_page.kpi_maintenance_pending_review"), value: maintenanceStats.value.pending_review, tone: "warning" },
  { icon: "fa-circle-check", label: t("admin_technicians_page.kpi_maintenance_approved"), value: maintenanceStats.value.approved, tone: "success" },
]);

/* ---------------- Pagination (طلبات الصيانة — Server-side حقيقي) ---------------- */
const maintenancePaginationRange = computed(() =>
  buildPaginationRange(maintenancePagination.value.current_page, maintenancePagination.value.last_page)
);

function goToMaintenancePage(page) {
  if (page < 1 || page > maintenancePagination.value.last_page) return;
  loadMaintenanceRequests(page);
}

function handleMaintenanceSearchInput() {
  onMaintenanceSearchInput();
}

function setMaintenanceStatusFilter(value) {
  maintenanceStatusFilter.value = value;
  onMaintenanceStatusFilterChange();
}

function formatRequestDate(iso) {
  return new Date(iso).toLocaleDateString(locale.value === "ar" ? "ar" : "en-US", { year: "numeric", month: "short", day: "numeric" });
}

/* ---------------- طلب صيانة جديد ---------------- */
const isCreateRequestOpen = ref(false);
const isCreatingRequest = ref(false);
const createRequestError = ref(null);
const createRequestForm = reactive({
  generator_id: "",
  type: "general_maintenance",
  instructions: "",
  technician_id: "",
});

async function openCreateRequest() {
  createRequestForm.generator_id = "";
  createRequestForm.type = "general_maintenance";
  createRequestForm.instructions = "";
  createRequestForm.technician_id = "";
  createRequestError.value = null;
  isCreateRequestOpen.value = true;
  await ensureGeneratorOptionsLoaded();
}

async function handleCreateRequest() {
  if (!createRequestForm.generator_id) {
    createRequestError.value = t("admin_technicians_page.generator_required_error");
    return;
  }

  isCreatingRequest.value = true;
  createRequestError.value = null;
  try {
    await technicianTaskService.create({
      generator_id: createRequestForm.generator_id,
      type: createRequestForm.type,
      instructions: createRequestForm.instructions.trim() || undefined,
      technician_id: createRequestForm.technician_id || undefined,
    });
    isCreateRequestOpen.value = false;
    await loadMaintenanceRequests(1);
    loadMaintenanceStats();
  } catch (err) {
    createRequestError.value = normalizeApiError(err, t("admin_technicians_page.create_request_error")).message;
  } finally {
    isCreatingRequest.value = false;
  }
}

/* ---------------- تعيين فني للمهمة ---------------- */
const assigningRequest = ref(null);
const assignTechnicianId = ref("");
const isAssigning = ref(false);
const assignError = ref(null);

function openAssignRequest(request) {
  assigningRequest.value = request;
  assignTechnicianId.value = "";
  assignError.value = null;
}

async function handleAssignRequest() {
  if (!assignTechnicianId.value) {
    assignError.value = t("admin_technicians_page.technician_required_error");
    return;
  }
  isAssigning.value = true;
  assignError.value = null;
  try {
    await technicianTaskService.assign(assigningRequest.value.id, { technician_id: assignTechnicianId.value });
    assigningRequest.value = null;
    await loadMaintenanceRequests(maintenancePagination.value.current_page);
    loadMaintenanceStats();
  } catch (err) {
    assignError.value = normalizeApiError(err, t("admin_technicians_page.assign_error")).message;
  } finally {
    isAssigning.value = false;
  }
}

/* ---------------- مراجعة مهمة بانتظار الاعتماد (اعتماد/رفض) ---------------- */
const reviewingRequest = ref(null);
const reviewForm = reactive({ decision: "approved", rejection_reason: "", admin_override_reason: "" });
const isReviewing = ref(false);
const reviewError = ref(null);

function openReviewRequest(request) {
  reviewingRequest.value = request;
  reviewForm.decision = "approved";
  reviewForm.rejection_reason = "";
  reviewForm.admin_override_reason = "";
  reviewError.value = null;
}

async function handleReviewRequest() {
  if (!reviewForm.admin_override_reason.trim()) {
    reviewError.value = t("admin_technicians_page.override_reason_required_error");
    return;
  }
  if (reviewForm.decision === "rejected" && !reviewForm.rejection_reason.trim()) {
    reviewError.value = t("admin_technicians_page.rejection_reason_required_error");
    return;
  }
  isReviewing.value = true;
  reviewError.value = null;
  try {
    await technicianTaskService.review(reviewingRequest.value.id, {
      decision: reviewForm.decision,
      rejection_reason: reviewForm.decision === "rejected" ? reviewForm.rejection_reason.trim() : undefined,
      admin_override_reason: reviewForm.admin_override_reason.trim(),
    });
    reviewingRequest.value = null;
    await loadMaintenanceRequests(maintenancePagination.value.current_page);
    loadMaintenanceStats();
  } catch (err) {
    reviewError.value = normalizeApiError(err, t("admin_technicians_page.review_error")).message;
  } finally {
    isReviewing.value = false;
  }
}

/* ---------------- إلغاء مهمة ---------------- */
const cancellingRequestId = ref(null);

async function handleCancelRequest(request) {
  const confirmed = await confirm({
    title: t("admin_technicians_page.cancel_request_title", { id: request.id }),
    message: t("admin_technicians_page.cancel_request_message"),
    confirmLabel: t("admin_technicians_page.cancel_request_button"),
    variant: "danger",
  });
  if (!confirmed) return;

  cancellingRequestId.value = request.id;
  try {
    await technicianTaskService.cancel(request.id);
    await loadMaintenanceRequests(maintenancePagination.value.current_page);
    loadMaintenanceStats();
  } finally {
    cancellingRequestId.value = null;
  }
}

onMounted(() => {
  loadMaintenanceRequests();
  loadMaintenanceStats();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
        <ChevronRight class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronLeft class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ activeTab === "technicians" ? $t("admin_technicians_page.tab_technicians") : $t("admin_technicians_page.tab_maintenance") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-base">
              <UserCog aria-hidden="true" v-if="activeTab === 'technicians'" /><Wrench aria-hidden="true" v-else />
            </span>
            {{ activeTab === "technicians" ? $t("admin_technicians_page.tab_technicians") : $t("admin_technicians_page.tab_maintenance") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ activeTab === "technicians" ? $t("admin_technicians_page.subtitle_technicians") : $t("admin_technicians_page.subtitle_maintenance") }}
          </p>
        </div>

        <!-- ===== تبديل التابات ===== -->
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            @click="activeTab = tab.key"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
            :class="
              activeTab === tab.key
                ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
            "
          >
            <AppIcon :name="tab.icon" />
            {{ tab.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- ==========================================================
         ==================  تبويب: الفنيون  =======================
         ========================================================== -->
    <template v-if="activeTab === 'technicians'">
      <!-- ===== KPI CARDS ===== -->
      <section v-reveal>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
          <StatCard
            v-for="c in KPI_CARDS" :key="c.label"
            :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone" :decimals="c.decimals ?? 0"
          />
        </div>
      </section>

      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="relative">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="searchTerm"
              @input="handleSearchInput"
              type="text"
              :placeholder="$t('admin_technicians_page.search_placeholder')"
              class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
            />
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
              <button
                v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
                @click="statusFilter = pill.value"
                class="px-3.5 py-1.5 rounded-full text-[11px] font-bold transition-colors"
                :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
              >{{ pill.label }}</button>
            </div>
            <a
              :href="techniciansExportUrl"
              target="_blank"
              class="btn-fill relative text-[12.5px] font-bold px-4 py-2 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
            >
              <FileSpreadsheet aria-hidden="true" />
              {{ $t("admin_technicians_page.export_button") }}
            </a>
            <button
              v-if="can('technicians.create')"
              type="button" @click="openCreate"
              class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2 rounded-full shadow-md flex items-center gap-2"
            >
              <Plus aria-hidden="true" />
              {{ $t("admin_technicians_page.add_button") }}
            </button>
          </div>
        </div>
      </section>

      <!-- ===== LIST ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("admin_technicians_page.list_title") }}</h3>

        <div v-if="deleteError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">
          {{ deleteError }}
        </div>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

        <div v-else-if="filteredTechnicians.length === 0" class="text-center py-12">
          <UserCog class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
          <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("admin_technicians_page.no_matching_results") }}</p>
        </div>

        <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
          <div
            v-for="technician in filteredTechnicians"
            :key="technician.id"
            class="flex items-center gap-3.5 py-3.5 px-1.5"
            :class="{ 'opacity-50 pointer-events-none': deletingId === technician.id }"
          >
            <div
              class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center shrink-0 text-white font-bold text-[12px]"
            >
              {{ technician.name?.charAt(0) }}
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[12.5px] font-bold truncate">{{ technician.name }}</p>
                <span v-if="technician.is_locked" class="status-chip chip-danger">{{ $t("admin_technicians_page.locked_badge") }}</span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 truncate">
                {{ technician.email }}
                <span v-if="technician.phone"> · {{ technician.phone }}</span>
              </p>
              <p class="text-[10.5px] text-[#8A6D1F] dark:text-[#D4AF37] mt-0.5 truncate">
                <UserRound class="text-[9px] me-1" aria-hidden="true" />
                {{ technician.owner_name ?? $t("admin_technicians_page.no_owner") }}
              </p>
            </div>

            <div class="flex items-center gap-1 shrink-0">
              <button
                v-if="can('technicians.view')"
                type="button"
                @click="openView(technician)"
                class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F] hover:bg-[#8A6D1F]/10 transition"
                :title="$t('admin_technicians_page.view_title')"
                :aria-label="$t('admin_technicians_page.view_title')"
              >
                <Eye class="text-[12px]" aria-hidden="true" />
              </button>
              <button
                v-if="technician.is_locked && can('technicians.update')"
                type="button"
                @click="unlockTechnician(technician.id)"
                class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-success hover:bg-success/10 transition"
                :title="$t('admin_technicians_page.unlock_title')"
                :aria-label="$t('admin_technicians_page.unlock_title')"
              >
                <LockOpen class="text-[12px]" aria-hidden="true" />
              </button>
              <button
                v-if="can('technicians.update')"
                type="button"
                @click="openEdit(technician)"
                class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-secondary-700 hover:bg-secondary-700/10 transition"
                :title="$t('admin_technicians_page.edit_title')"
                :aria-label="$t('admin_technicians_page.edit_title')"
              >
                <Pencil class="text-[12px]" aria-hidden="true" />
              </button>
              <button
                v-if="can('technicians.delete')"
                type="button"
                @click="handleDelete(technician)"
                class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-danger hover:bg-danger/10 transition"
                :title="$t('admin_technicians_page.delete_title')"
                :aria-label="$t('admin_technicians_page.delete_title')"
              >
                <Trash2 class="text-[12px]" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination بأزرار محدودة -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
          <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
            {{ $t("admin_technicians_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
          </span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')"
              type="button"
              :disabled="pagination.current_page <= 1"
              @click="fetchTechnicians(pagination.current_page - 1)"
              class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
            >
              <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>

            <template v-for="(page, i) in paginationRange" :key="i">
              <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
              <button
                v-else
                type="button"
                @click="fetchTechnicians(page)"
                class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
                :class="
                  page === pagination.current_page
                    ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                    : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
                "
              >
                {{ page }}
              </button>
            </template>

            <button
              :aria-label="$t('common.next_page')"
              type="button"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="fetchTechnicians(pagination.current_page + 1)"
              class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
            >
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         ==============  تبويب: طلبات الصيانة  ======================
         ========================================================== -->
    <template v-else>
      <!-- ===== KPI CARDS ===== -->
      <section v-reveal>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
          <StatCard
            v-for="c in MAINTENANCE_KPI_CARDS" :key="c.label"
            :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
          />
        </div>
      </section>

      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="relative">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="maintenanceSearchTerm"
              @input="handleMaintenanceSearchInput"
              type="text"
              :placeholder="$t('admin_technicians_page.maintenance_search_placeholder')"
              class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
            />
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
              <button
                v-for="pill in MAINTENANCE_STATUS_PILLS" :key="pill.value" type="button"
                @click="setMaintenanceStatusFilter(pill.value)"
                class="px-3.5 py-1.5 rounded-full text-[11px] font-bold transition-colors"
                :class="maintenanceStatusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
              >{{ pill.label }}</button>
            </div>
            <button
              type="button"
              @click="openCreateRequest"
              class="flex items-center gap-1.5 px-4 py-2 rounded-full text-[11.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md hover:brightness-110 transition"
            >
              <Plus aria-hidden="true" />
              {{ $t("admin_technicians_page.new_request_button") }}
            </button>
          </div>
        </div>
      </section>

      <!-- ===== LIST ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("admin_technicians_page.maintenance_list_title") }}</h3>

        <div v-if="isMaintenanceLoading" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="maintenanceRequests.length === 0" class="text-center py-12">
          <Wrench class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
          <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("admin_technicians_page.no_matching_requests") }}</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] border-b border-[#f0ece0] dark:border-white/5">
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_task_id") }}</th>
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_generator") }}</th>
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_type_instructions") }}</th>
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_assigned_technician") }}</th>
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_date") }}</th>
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_status") }}</th>
                <th class="text-start font-bold py-2 px-1.5">{{ $t("admin_technicians_page.col_actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0ece0] dark:divide-white/5">
              <tr
                v-for="request in maintenanceRequests"
                :key="request.id"
                :class="{ 'opacity-50 pointer-events-none': cancellingRequestId === request.id }"
              >
                <td class="py-3 px-1.5 font-bold whitespace-nowrap">#{{ request.id }}</td>
                <td class="py-3 px-1.5">
                  <p class="font-bold truncate max-w-[140px]">{{ request.generator_name ?? "—" }}</p>
                </td>
                <td class="py-3 px-1.5 max-w-[180px]">
                  <p class="font-bold truncate">{{ taskTypeLabel(request.type) }}</p>
                  <p v-if="request.instructions" class="truncate text-[#6B6B6B] dark:text-[#a8aaa5]" :title="request.instructions">{{ request.instructions }}</p>
                </td>
                <td class="py-3 px-1.5 whitespace-nowrap text-[#6B6B6B] dark:text-[#a8aaa5]">
                  {{ request.technician?.name ?? $t("admin_technicians_page.unassigned_label") }}
                </td>
                <td class="py-3 px-1.5 whitespace-nowrap text-[#6B6B6B] dark:text-[#a8aaa5]">
                  {{ formatRequestDate(request.created_at) }}
                </td>
                <td class="py-3 px-1.5 whitespace-nowrap">
                  <span class="status-chip" :class="MAINTENANCE_STATUS_META[request.status]?.chip">{{ maintenanceStatusLabel(request.status) }}</span>
                </td>
                <td class="py-3 px-1.5 whitespace-nowrap">
                  <div class="row-actions">
                    <button
                      v-if="request.status === 'pending' && can('technician-tasks.assign')"
                      type="button" @click="openAssignRequest(request)"
                      class="action-btn action-btn--edit" :title="$t('admin_technicians_page.assign_technician_title')"
                      :aria-label="$t('admin_technicians_page.assign_technician_title')"
                    >
                      <UserCog aria-hidden="true" />
                    </button>
                    <button
                      v-if="request.status === 'submitted' && can('technician-tasks.review')"
                      type="button" @click="openReviewRequest(request)"
                      class="action-btn action-btn--approve" :title="$t('admin_technicians_page.review_task_title')"
                      :aria-label="$t('admin_technicians_page.review_task_title')"
                    >
                      <ClipboardCheck aria-hidden="true" />
                    </button>
                    <button
                      v-if="ACTIVE_TASK_STATUSES.includes(request.status) && can('technician-tasks.cancel')"
                      type="button" :disabled="cancellingRequestId === request.id" @click="handleCancelRequest(request)"
                      class="action-btn action-btn--delete" :title="$t('admin_technicians_page.cancel_task_title')"
                      :aria-label="$t('admin_technicians_page.cancel_task_title')"
                    >
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingRequestId === request.id" /><Ban aria-hidden="true" v-else />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination بأزرار محدودة -->
        <div v-if="maintenancePagination.last_page > 1" class="flex items-center justify-between mt-3.5">
          <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
            {{ $t("admin_technicians_page.maintenance_pagination_text", { current: maintenancePagination.current_page, last: maintenancePagination.last_page, total: maintenancePagination.total }) }}
          </span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')"
              type="button"
              :disabled="maintenancePagination.current_page <= 1"
              @click="goToMaintenancePage(maintenancePagination.current_page - 1)"
              class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
            >
              <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>

            <template v-for="(page, i) in maintenancePaginationRange" :key="i">
              <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
              <button
                v-else
                type="button"
                @click="goToMaintenancePage(page)"
                class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
                :class="
                  page === maintenancePagination.current_page
                    ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                    : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
                "
              >
                {{ page }}
              </button>
            </template>

            <button
              :aria-label="$t('common.next_page')"
              type="button"
              :disabled="maintenancePagination.current_page >= maintenancePagination.last_page"
              @click="goToMaintenancePage(maintenancePagination.current_page + 1)"
              class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
            >
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>
    </template>

    <!-- ===================== نافذة تعديل بيانات الفني ===================== -->
    <Teleport to="body">
      <div
        v-if="editingTechnician"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="editingTechnician = null"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-[12px]">
              <Pencil aria-hidden="true" />
            </span>
            {{ $t("admin_technicians_page.edit_modal_title", { name: editingTechnician.name }) }}
          </h3>

          <div
            v-if="saveError?.message"
            class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5 mb-3.5"
          >
            {{ saveError.message }}
          </div>

          <div class="space-y-3.5">
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_name") }}</label>
              <input
                v-model="editForm.name"
                type="text"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors"
              />
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_email") }}</label>
              <input
                v-model="editForm.email"
                type="email"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors"
              />
              <p v-if="saveError?.errors?.email" class="text-[11px] text-[#D9534F] mt-1">
                {{ saveError.errors.email[0] }}
              </p>
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_phone") }}</label>
              <input
                v-model="editForm.phone"
                type="text"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors"
              />
            </div>

            <div
              v-if="editingTechnician.family_members_count != null || editingTechnician.has_sick_family_member"
              class="pt-3 border-t border-[#f0ece0] dark:border-white/5 space-y-2"
            >
              <p class="text-[10.5px] font-bold text-[#9a9d97] dark:text-[#8f938a]">{{ $t("admin_technicians_page.family_section_title") }}</p>
              <div v-if="editingTechnician.family_members_count != null" class="flex items-center justify-between text-[12px]">
                <span class="text-[#6B6B6B] dark:text-[#a8aaa5]">{{ $t("admin_technicians_page.family_members_count_label") }}</span>
                <b>{{ editingTechnician.family_members_count }}</b>
              </div>
              <div v-if="editingTechnician.has_sick_family_member" class="flex items-center justify-between gap-2 text-[12px]">
                <span class="text-[#6B6B6B] dark:text-[#a8aaa5] shrink-0">{{ $t("admin_technicians_page.sick_family_member_label") }}</span>
                <b class="text-danger text-end">{{ editingTechnician.sick_family_member_illness || $t("admin_technicians_page.sick_family_member_yes") }}</b>
              </div>
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button
              type="button"
              @click="editingTechnician = null"
              class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
            >
              {{ $t("admin_technicians_page.cancel") }}
            </button>
            <button
              type="button"
              @click="handleSave"
              :disabled="isSaving"
              class="flex-1 btn-fill relative text-[12.5px] font-bold py-2.5 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" />
              {{ isSaving ? $t("admin_technicians_page.saving") : $t("admin_technicians_page.save") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===== إنشاء فني جديد (owner_id إلزامي) ===== -->
    <Teleport to="body">
      <div
        v-if="isCreateOpen"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="isCreateOpen = false"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl max-h-[90vh] overflow-y-auto">
          <h3 class="text-[14px] font-extrabold flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-[12px]">
              <UserPlus aria-hidden="true" />
            </span>
            {{ $t("admin_technicians_page.add_modal_title") }}
          </h3>

          <div v-if="createError?.message" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5 mb-3.5">
            {{ createError.message }}
          </div>

          <div class="space-y-3.5">
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_owner") }} <span class="text-[#D9534F]">*</span></label>
              <AppDropdownSelect
                v-model="createForm.owner_id"
                :options="ownerSelectOptions"
                :disabled="isLoadingOwners"
                :placeholder="isLoadingOwners ? $t('common.loading') : $t('admin_technicians_page.choose_owner')"
                variant="field" width-class="w-full" match-trigger-width
              />
              <p v-if="createError?.errors?.owner_id" class="text-[11px] text-[#D9534F] mt-1">{{ createError.errors.owner_id[0] }}</p>
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_name") }}</label>
              <input v-model="createForm.name" type="text" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors" />
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_email") }}</label>
              <input v-model="createForm.email" type="email" dir="ltr" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors" />
              <p v-if="createError?.errors?.email" class="text-[11px] text-[#D9534F] mt-1">{{ createError.errors.email[0] }}</p>
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_password") }}</label>
              <input v-model="createForm.password" type="password" dir="ltr" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors" />
              <p v-if="createError?.errors?.password" class="text-[11px] text-[#D9534F] mt-1">{{ createError.errors.password[0] }}</p>
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_password_confirmation") }}</label>
              <input v-model="createForm.password_confirmation" type="password" dir="ltr" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors" />
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button
              type="button" @click="isCreateOpen = false"
              class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
            >{{ $t("admin_technicians_page.cancel") }}</button>
            <button
              type="button" @click="handleCreate"
              :disabled="isCreating || !createForm.owner_id || !createForm.name || !createForm.email || !createForm.password"
              class="flex-1 btn-fill relative text-[12.5px] font-bold py-2.5 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreating" /><Plus aria-hidden="true" v-else />
              {{ isCreating ? $t("admin_technicians_page.saving") : $t("admin_technicians_page.add_button") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة طلب صيانة جديد ===================== -->
    <Teleport to="body">
      <div
        v-if="isCreateRequestOpen"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="isCreateRequestOpen = false"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-[12px]">
              <Wrench aria-hidden="true" />
            </span>
            {{ $t("admin_technicians_page.new_request_modal_title") }}
          </h3>

          <div
            v-if="createRequestError"
            class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5 mb-3.5"
          >
            {{ createRequestError }}
          </div>

          <div class="space-y-3.5">
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_generator") }}</label>
              <AppDropdownSelect
                v-model="createRequestForm.generator_id"
                :options="generatorSelectOptions"
                :placeholder="$t('admin_technicians_page.select_generator_placeholder')"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_task_type") }}</label>
              <AppDropdownSelect
                v-model="createRequestForm.type"
                :options="TASK_TYPE_OPTIONS"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_instructions_optional") }}</label>
              <textarea
                v-model="createRequestForm.instructions"
                rows="3"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#52733D] transition-colors resize-none"
              ></textarea>
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_assigned_technician_optional") }}</label>
              <AppDropdownSelect
                v-model="createRequestForm.technician_id"
                :options="technicianSelectOptionsWithNone"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button
              type="button"
              @click="isCreateRequestOpen = false"
              class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
            >
              {{ $t("admin_technicians_page.cancel") }}
            </button>
            <button
              type="button"
              @click="handleCreateRequest"
              :disabled="isCreatingRequest"
              class="flex-1 btn-fill relative text-[12.5px] font-bold py-2.5 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingRequest" />
              {{ isCreatingRequest ? $t("admin_technicians_page.adding") : $t("admin_technicians_page.add_request_button") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة تعيين فني لمهمة الصيانة ===================== -->
    <Teleport to="body">
      <div
        v-if="assigningRequest"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="assigningRequest = null"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-[12px]">
              <UserCog aria-hidden="true" />
            </span>
            {{ $t("admin_technicians_page.assign_modal_title", { id: assigningRequest.id }) }}
          </h3>

          <div v-if="assignError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5 mb-3.5">
            {{ assignError }}
          </div>

          <div>
            <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_technician") }}</label>
            <AppDropdownSelect
              v-model="assignTechnicianId"
              :options="technicianSelectOptions"
              :placeholder="$t('admin_technicians_page.select_technician_placeholder')"
              variant="field" width-class="w-full" match-trigger-width
            />
          </div>

          <div class="flex gap-2.5 mt-5">
            <button
              type="button" @click="assigningRequest = null"
              class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
            >
              {{ $t("admin_technicians_page.cancel") }}
            </button>
            <button
              type="button" @click="handleAssignRequest" :disabled="isAssigning"
              class="flex-1 btn-fill relative text-[12.5px] font-bold py-2.5 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isAssigning" /><Check aria-hidden="true" v-else />
              {{ isAssigning ? $t("admin_technicians_page.assigning_ellipsis") : $t("admin_technicians_page.assign_technician_title") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة مراجعة مهمة صيانة (اعتماد/رفض) ===================== -->
    <Teleport to="body">
      <div
        v-if="reviewingRequest"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="reviewingRequest = null"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-[12px]">
              <ClipboardCheck aria-hidden="true" />
            </span>
            {{ $t("admin_technicians_page.review_modal_title", { id: reviewingRequest.id }) }}
          </h3>

          <div v-if="reviewError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5 mb-3.5">
            {{ reviewError }}
          </div>

          <div class="space-y-3.5">
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_decision") }}</label>
              <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
                <button
                  type="button" @click="reviewForm.decision = 'approved'"
                  class="flex-1 px-3 py-1.5 rounded-full text-[11.5px] font-bold transition-colors"
                  :class="reviewForm.decision === 'approved' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
                >{{ $t("admin_technicians_page.decision_approve") }}</button>
                <button
                  type="button" @click="reviewForm.decision = 'rejected'"
                  class="flex-1 px-3 py-1.5 rounded-full text-[11.5px] font-bold transition-colors"
                  :class="reviewForm.decision === 'rejected' ? 'bg-[#D9534F] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
                >{{ $t("admin_technicians_page.decision_reject") }}</button>
              </div>
            </div>
            <div v-if="reviewForm.decision === 'rejected'">
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_rejection_reason") }}</label>
              <textarea
                v-model="reviewForm.rejection_reason"
                rows="2"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#52733D] transition-colors resize-none"
              ></textarea>
            </div>
            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ $t("admin_technicians_page.field_override_reason") }}</label>
              <textarea
                v-model="reviewForm.admin_override_reason"
                rows="2"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#52733D] transition-colors resize-none"
              ></textarea>
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button
              type="button" @click="reviewingRequest = null"
              class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
            >
              {{ $t("admin_technicians_page.cancel") }}
            </button>
            <button
              type="button" @click="handleReviewRequest" :disabled="isReviewing"
              class="flex-1 btn-fill relative text-[12.5px] font-bold py-2.5 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isReviewing" /><Check aria-hidden="true" v-else />
              {{ isReviewing ? $t("admin_technicians_page.saving_decision") : $t("admin_technicians_page.save_decision") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة عرض تفاصيل الفني + إدارة مرفقاته =====================
         FIX (تدقيق شامل للوحة الأدمن — بندان 5 و15): technicianService.show()
         ومرفقات الفني (GET/POST technicians/{id}/attachments) كانا بلا أي
         واجهة استخدام رغم دعمهما الكامل بالباك اند. -->
    <Teleport to="body">
      <div
        v-if="viewingTechnician"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="viewingTechnician = null"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md p-5 shadow-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-[14px] font-extrabold flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-[12px]">
                <UserCog aria-hidden="true" />
              </span>
              {{ $t("admin_technicians_page.view_modal_title") }}
            </h3>
            <button :aria-label="$t('common.close')" type="button" @click="viewingTechnician = null" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#f4efe5]/70 dark:hover:bg-white/5">
              <X class="text-[13px]" aria-hidden="true" />
            </button>
          </div>

          <div class="space-y-3.5">
            <div class="glass-card p-4 flex items-center gap-3.5">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center shrink-0 text-white font-bold text-[14px]">
                {{ viewingTechnician.name?.charAt(0) }}
              </div>
              <div class="min-w-0">
                <p class="text-[13px] font-bold truncate">{{ viewingTechnician.name }}</p>
                <p class="text-[10.5px] text-[#8A6D1F] dark:text-[#D4AF37] truncate">
                  <UserRound class="text-[9px] me-1" aria-hidden="true" />{{ viewingTechnician.owner_name ?? $t("admin_technicians_page.no_owner") }}
                </p>
              </div>
              <LoaderCircle v-if="isLoadingTechnicianView" class="animate-spin text-[12px] text-[#9a9d97] shrink-0 ms-auto" aria-hidden="true" />
            </div>

            <div class="glass-card p-4 space-y-2 text-[12px]">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[#9a9d97] dark:text-[#8f938a] flex items-center gap-1.5"><Mail class="text-[10px]" aria-hidden="true" />{{ $t("admin_technicians_page.field_email") }}</span>
                <b dir="ltr" class="truncate">{{ viewingTechnician.email }}</b>
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-[#9a9d97] dark:text-[#8f938a] flex items-center gap-1.5"><Phone class="text-[10px]" aria-hidden="true" />{{ $t("admin_technicians_page.field_phone") }}</span>
                <b dir="ltr">{{ viewingTechnician.phone ?? "—" }}</b>
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-[#9a9d97] dark:text-[#8f938a] flex items-center gap-1.5"><Star class="text-[10px]" aria-hidden="true" />{{ $t("admin_technicians_page.rating_label") }}</span>
                <b>{{ typeof viewingTechnician.rating === "number" ? viewingTechnician.rating.toFixed(1) : $t("admin_technicians_page.no_rating_yet") }}</b>
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("admin_technicians_page.availability_label") }}</span>
                <span class="status-chip" :class="availabilityOf(viewingTechnician) === 'available' ? 'chip-success' : availabilityOf(viewingTechnician) === 'busy' ? 'chip-info' : 'chip-danger'">{{ availabilityLabel(viewingTechnician) }}</span>
              </div>
            </div>

            <!-- ===== المرفقات (شهادة/هوية) ===== -->
            <div class="glass-card p-4">
              <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-1.5"><Paperclip class="text-[10px]" aria-hidden="true" />{{ $t("admin_technicians_page.attachments_title") }}</h5>

              <div v-if="isLoadingTechnicianAttachments" class="space-y-2">
                <div v-for="i in 2" :key="i" class="h-9 rounded-lg thumb-loading"></div>
              </div>
              <div v-else-if="technicianAttachmentsError" class="text-[11px] text-[#D9534F]">{{ technicianAttachmentsError }}</div>
              <div v-else-if="technicianAttachments.length === 0" class="dropdown-empty py-4 text-[11px]">
                {{ $t("admin_technicians_page.no_attachments_yet") }}
              </div>
              <div v-else class="space-y-2 mb-3">
                <div v-for="a in technicianAttachments" :key="a.id" class="flex items-center gap-2.5 p-2 rounded-lg bg-[#f4efe5]/50 dark:bg-white/5">
                  <Paperclip class="text-[11px] text-[#9a9d97] shrink-0" aria-hidden="true" />
                  <div class="min-w-0 flex-1">
                    <button type="button" @click="previewingTechnicianAttachment = a" class="text-[11.5px] font-semibold hover:underline truncate block text-start">{{ a.original_name }}</button>
                  </div>
                </div>
              </div>

              <div v-if="can('technicians.update')" class="space-y-2 pt-3 border-t border-[#f0ece0] dark:border-white/5">
                <div v-if="technicianAttachmentUploadError?.file" class="text-[11px] text-[#D9534F]">{{ technicianAttachmentUploadError.file[0] }}</div>
                <AppDropdownSelect v-model="newTechnicianAttachmentType" :options="TECHNICIAN_DOCUMENT_TYPE_OPTIONS" variant="field" width-class="w-full" match-trigger-width />
                <input ref="technicianAttachmentFileInput" type="file" @change="onTechnicianAttachmentFileChange" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="field-input text-[11px]" />
                <input v-model="newTechnicianAttachmentDescription" type="text" :placeholder="$t('admin_technicians_page.short_description_optional')" class="field-input text-[11.5px]" />
                <button
                  type="button" @click="handleUploadTechnicianAttachment"
                  :disabled="!newTechnicianAttachmentFile || isUploadingTechnicianAttachment"
                  class="w-full btn-fill relative text-[11.5px] font-bold py-2 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
                >
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isUploadingTechnicianAttachment" /><Plus aria-hidden="true" v-else />
                  {{ isUploadingTechnicianAttachment ? $t("admin_technicians_page.uploading_ellipsis") : $t("admin_technicians_page.upload_attachment_button") }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <AttachmentPreviewModal :attachment="previewingTechnicianAttachment" @close="previewingTechnicianAttachment = null" />
  </div>
</template>