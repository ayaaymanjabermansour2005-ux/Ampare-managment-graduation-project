<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useOwnerTechnicians } from "@/composables/useOwnerTechnicians";
import { useOwnerTechnicianTasks } from "@/composables/useOwnerTechnicianTasks";
import { useOwnerTechnicianPortal } from "@/composables/useOwnerTechnicianPortal";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import generatorService from "@/services/generatorService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Check, ChevronLeft, ChevronRight, CircleAlert, ClipboardList, Gauge, Hourglass, LoaderCircle, Pencil, Plus, Save, Search, Send, Smartphone, Star, Trash2, UserCheck, UserPlus, Wallet, Wrench, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { confirm } = useConfirm();
const toast = useToastStore();
const { t } = useI18n();
const route = useRoute();

/* ==========================================================================
 * ==================  التابات (فنيون / الصيانة / بوابة الفني)  =============
 * ========================================================================== */
const TABS = [
  { key: "technicians", label: t("owner_technicians.title"), icon: "fa-screwdriver-wrench" },
  { key: "maintenance", label: t("owner_technician_tasks.maintenance_title"), icon: "fa-clipboard-list" },
  { key: "portal", label: t("owner_technician_portal.title"), icon: "fa-mobile-screen-button" },
];

const TAB_META = {
  technicians: { title: t("owner_technicians.title"), eyebrow: t("owner_technicians.eyebrow"), icon: "fa-screwdriver-wrench" },
  maintenance: { title: t("owner_technician_tasks.maintenance_title"), eyebrow: t("owner_technician_tasks.eyebrow"), icon: "fa-clipboard-list" },
  portal: { title: t("owner_technician_portal.title"), eyebrow: t("owner_technician_portal.eyebrow"), icon: "fa-mobile-screen-button" },
};
const activeTab = ref("technicians");

/* ==========================================================================
 * =============================  تبويب: الفنيون  ===========================
 * ========================================================================== */
const {
  technicians,
  pagination: techniciansPagination,
  isLoading: isTechniciansLoading,
  error: techniciansError,
  search,
  fetchTechnicians,
  onSearchInput,
  eligibleUsers,
  isLoadingUsers,
  loadEligibleUsers,
  isSaving,
  saveError,
  createTechnician,
  createTechnicianAccount,
  updateTechnician,
  deletingId,
  deleteError,
  deleteTechnician,
} = useOwnerTechnicians();

const TECHNICIAN_STATUS_META = { active: "chip-success", inactive: "chip-danger" };
function technicianStatusLabel(status) {
  return t(`owner_technicians.status.${status}`, status);
}

// فورم إضافة فني — يدعم وضعين: فني موجود (ربط حساب له صلاحية فني) أو فني جديد (إنشاء حساب كامل)
const isFormOpen = ref(false);
const addMode = ref("existing"); // "existing" | "new"
const newForm = reactive({ user_id: "", notes: "" });
const newAccountForm = reactive({ name: "", email: "", password: "", password_confirmation: "", notes: "" });
const userSearch = ref("");
let searchTimeout = null;

function openAddForm() {
  addMode.value = "existing";
  newForm.user_id = "";
  newForm.notes = "";
  newAccountForm.name = "";
  newAccountForm.email = "";
  newAccountForm.password = "";
  newAccountForm.password_confirmation = "";
  newAccountForm.notes = "";
  saveError.value = null;
  userSearch.value = "";
  eligibleUsers.value = [];
  isFormOpen.value = true;
}

function handleUserSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => loadEligibleUsers(userSearch.value), 350);
}

async function handleCreate() {
  const ok =
    addMode.value === "new"
      ? await createTechnicianAccount({ ...newAccountForm })
      : await createTechnician({ ...newForm });

  if (ok) {
    isFormOpen.value = false;
    toast.show({
      type: "success",
      title: t("owner_technicians.created_toast_title"),
      message: t("owner_technicians.created_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technicians.save_failed_title"),
      message: saveError.value?.message ?? t("owner_technicians.create_error"),
    });
  }
}

// تعديل حالة/ملاحظات فني
const editingTechnician = ref(null);
const editForm = reactive({ status: "active", notes: "" });

function openEdit(technician) {
  editingTechnician.value = technician;
  editForm.status = technician.status;
  editForm.notes = technician.notes ?? "";
  saveError.value = null;
}

async function handleSaveEdit() {
  const ok = await updateTechnician(editingTechnician.value.id, {
    ...editForm,
  });
  if (ok) {
    editingTechnician.value = null;
    toast.show({
      type: "success",
      title: t("owner_technicians.updated_toast_title"),
      message: t("owner_technicians.updated_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technicians.save_failed_title"),
      message: saveError.value?.message ?? t("owner_technicians.update_error"),
    });
  }
}

async function handleDelete(technician) {
  const confirmed = await confirm({
    title: t("owner_technicians.delete_title", { name: technician.name }),
    message: t("owner_technicians.delete_message"),
    confirmLabel: t("owner_technicians.delete_confirm"),
    variant: "danger",
  });
  if (!confirmed) return;

  const ok = await deleteTechnician(technician.id);
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_technicians.deleted_toast_title"),
      message: t("owner_technicians.deleted_toast_message", { name: technician.name }),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technicians.delete_failed_title"),
      message: deleteError.value ?? t("owner_technicians.delete_error"),
    });
  }
}

/* ==========================================================================
 * =============================  تبويب: المهمات  ===========================
 * ========================================================================== */
const {
  tasks,
  pagination: tasksPagination,
  isLoading: isTasksLoading,
  error: tasksError,
  needsReviewTasks,
  needsRatingTasks,
  otherTasks,
  actingId,
  actionError,
  fetchTasks,
  reviewTask,
  rateTask,
  isCreating: isCreatingTask,
  createError: createTaskError,
  createTask,
  isAssigning: isAssigningTask,
  assignError: assignTaskError,
  assignTask,
} = useOwnerTechnicianTasks();

const TASK_STATUS_META = {
  pending: "chip-info",
  assigned: "chip-info",
  on_the_way: "chip-info",
  in_progress: "chip-warning",
  waiting_parts: "chip-warning",
  submitted: "chip-warning",
  approved: "chip-success",
  rejected: "chip-danger",
  cancelled: "chip-danger",
};
function taskStatusLabel(status) {
  return t(`owner_technician_tasks.status.${status}`, status);
}
function taskTypeLabel(type) {
  return t(`owner_technician_tasks.type.${type}`, type);
}

const TASK_TYPE_OPTIONS = computed(() => [
  { value: "general_maintenance", label: t("owner_technician_tasks.type.general_maintenance") },
  { value: "wiring_maintenance", label: t("owner_technician_tasks.type.wiring_maintenance") },
  { value: "fault_repair", label: t("owner_technician_tasks.type.fault_repair") },
  { value: "meter_reading", label: t("owner_technician_tasks.type.meter_reading") },
  { value: "new_subscription_installation", label: t("owner_technician_tasks.type.new_subscription_installation") },
]);

const EDIT_STATUS_OPTIONS = computed(() => [
  { value: "active", label: t("owner_technicians.status.active") },
  { value: "inactive", label: t("owner_technicians.status.inactive") },
]);
const technicianSelectOptions = computed(() => technicians.value.map((tech) => ({ value: tech.id, label: tech.name })));

/* ---------------- طلب صيانة جديد ---------------- */
const generatorOptions = ref([]);
async function ensureGeneratorOptionsLoaded() {
  if (generatorOptions.value.length > 0) return;
  const { data } = await generatorService.list({ per_page: 200 });
  generatorOptions.value = data.data.data ?? data.data;
}
const generatorSelectOptions = computed(() => generatorOptions.value.map((g) => ({ value: g.id, label: g.name })));

const isCreateRequestOpen = ref(false);
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
  createTaskError.value = null;
  isCreateRequestOpen.value = true;
  await ensureGeneratorOptionsLoaded();
}

async function handleCreateRequest() {
  const ok = await createTask({
    generator_id: createRequestForm.generator_id,
    type: createRequestForm.type,
    instructions: createRequestForm.instructions.trim() || undefined,
    technician_id: createRequestForm.technician_id || undefined,
  });
  if (ok) {
    isCreateRequestOpen.value = false;
    toast.show({
      type: "success",
      title: t("owner_technician_tasks.created_toast_title"),
      message: t("owner_technician_tasks.created_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technician_tasks.action_failed_title"),
      message: createTaskError.value ?? t("owner_technician_tasks.create_error"),
    });
  }
}

/* ---------------- تعيين فني لمهمة غير معيّنة ---------------- */
const assigningTask = ref(null);
const assignTechnicianId = ref("");

function openAssign(task) {
  assigningTask.value = task;
  assignTechnicianId.value = "";
  assignTaskError.value = null;
}

async function handleAssign() {
  if (!assignTechnicianId.value) return;
  const ok = await assignTask(assigningTask.value.id, assignTechnicianId.value);
  if (ok) {
    assigningTask.value = null;
    toast.show({
      type: "success",
      title: t("owner_technician_tasks.assigned_toast_title"),
      message: t("owner_technician_tasks.assigned_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technician_tasks.action_failed_title"),
      message: assignTaskError.value ?? t("owner_technician_tasks.assign_error"),
    });
  }
}

const rejectModal = ref({ open: false, taskId: null, taskLabel: "", reason: "" });
function openReject(task) {
  rejectModal.value = { open: true, taskId: task.id, taskLabel: taskTypeLabel(task.type), reason: "" };
}
async function submitReject() {
  const ok = await reviewTask(rejectModal.value.taskId, "rejected", rejectModal.value.reason);
  if (ok) {
    rejectModal.value.open = false;
    toast.show({
      type: "success",
      title: t("owner_technician_tasks.rejected_toast_title"),
      message: t("owner_technician_tasks.rejected_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technician_tasks.action_failed_title"),
      message: actionError.value ?? t("owner_technician_tasks.review_error"),
    });
  }
}

async function handleApprove(task) {
  const confirmed = await confirm({
    title: t("owner_technician_tasks.approve_confirm_title"),
    message: t("owner_technician_tasks.approve_confirm_message", { type: taskTypeLabel(task.type) }),
    confirmLabel: t("owner_technician_tasks.approve_action"),
  });
  if (!confirmed) return;

  const ok = await reviewTask(task.id, "approved");
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_technician_tasks.approved_toast_title"),
      message: t("owner_technician_tasks.approved_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technician_tasks.action_failed_title"),
      message: actionError.value ?? t("owner_technician_tasks.review_error"),
    });
  }
}

const ratingForm = ref({});
function initRatingForm(taskId) {
  if (!ratingForm.value[taskId]) {
    ratingForm.value[taskId] = { rating: 5, comment: "" };
  }
}
async function submitRating(taskId) {
  const form = ratingForm.value[taskId];
  const ok = await rateTask(taskId, form.rating, form.comment);
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_technician_tasks.rated_toast_title"),
      message: t("owner_technician_tasks.rated_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technician_tasks.action_failed_title"),
      message: actionError.value ?? t("owner_technician_tasks.rate_error"),
    });
  }
}

/* ==========================================================================
 * =========================  تبويب: بوابة الفني  ============================
 * ========================================================================== */
const {
  selectedTechnicianId,
  tasks: portalTasks,
  readings: portalReadings,
  payments: portalPayments,
  isLoading: isPortalLoading,
  error: portalError,
  loadTechnicianWork,
} = useOwnerTechnicianPortal();

const PORTAL_PAYMENT_STATUS_META = { pending: "chip-warning", approved: "chip-success", rejected: "chip-danger" };
function portalPaymentStatusLabel(status) {
  return t(`owner_technician_payments.status.${status}`, status);
}

onMounted(() => {
  // يسمح بفتح هذه الصفحة مباشرة على تاب معيّن (مثلاً من الداشبورد): ?tab=maintenance
  if (route.query.tab === "maintenance") activeTab.value = "maintenance";
  if (route.query.tab === "portal") activeTab.value = "portal";

  fetchTechnicians();
  fetchTasks();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("owner_dashboard.breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ TAB_META[activeTab].title }}</span>
      </nav>

      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">{{ TAB_META[activeTab].eyebrow }}</p>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <AppIcon :name="TAB_META[activeTab].icon" />
            </span>
            {{ TAB_META[activeTab].title }}
          </h1>

          <p v-if="activeTab === 'maintenance' && needsReviewTasks.length > 0" class="text-[12.5px] font-bold text-[#FFC107] flex items-center gap-1.5">
            <Hourglass class="text-[10px]" aria-hidden="true" />
            {{ t("owner_technician_tasks.needs_review_title", { count: needsReviewTasks.length }) }}
          </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <button
            v-if="activeTab === 'technicians'"
            type="button" @click="openAddForm"
            class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
          >
            <Plus aria-hidden="true" />
            {{ t("owner_technicians.add_button") }}
          </button>

          <button
            v-if="activeTab === 'maintenance'"
            type="button" @click="openCreateRequest"
            class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
          >
            <Plus aria-hidden="true" />
            {{ t("owner_technician_tasks.new_request_button") }}
          </button>

          <!-- ===== تبديل التابات ===== -->
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button
              v-for="tab in TABS" :key="tab.key" type="button"
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
                v-if="tab.key === 'maintenance' && needsReviewTasks.length > 0"
                class="absolute -top-1 -end-1 min-w-[16px] h-4 px-1 rounded-full bg-[#D9534F] text-white text-[9px] font-bold flex items-center justify-center"
              >{{ needsReviewTasks.length }}</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================
         ==================  تبويب: الفنيون  =========================
         ========================================================== -->
    <template v-if="activeTab === 'technicians'">
      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="relative max-w-xs">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
          <input
            v-model="search" type="text" @input="onSearchInput"
            :placeholder="t('owner_technicians.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>
      </section>

      <!-- ===== TABLE ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="deleteError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ deleteError }}</div>

        <div v-if="isTechniciansLoading" class="space-y-2">
          <div v-for="i in 5" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="techniciansError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ techniciansError }}</div>

        <div v-else-if="technicians.length === 0" class="text-center py-12">
          <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center">
            <Wrench class="text-2xl text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" />
          </div>
          <h3 class="text-[13.5px] font-bold mb-1">{{ t("owner_technicians.empty_title") }}</h3>
          <p class="text-[12px] text-[#9a9d97]">{{ t("owner_technicians.empty_desc") }}</p>
        </div>

        <div v-else class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[640px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_technicians.name_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_technicians.notes_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_technicians.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_technicians.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="tech in technicians" :key="tech.id"
                class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
                :class="{ 'opacity-50 pointer-events-none': deletingId === tech.id }"
              >
                <td class="py-2.5 px-3">
                  <div class="flex items-center gap-2.5 justify-center">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-[11px] font-bold shrink-0">
                      {{ tech.name?.charAt(0) }}
                    </div>
                    <span class="font-bold">{{ tech.name }}</span>
                  </div>
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5] truncate max-w-[16rem]">{{ tech.notes || "-" }}</td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="TECHNICIAN_STATUS_META[tech.status] ?? 'chip-info'">{{ technicianStatusLabel(tech.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div class="row-actions">
                    <button type="button" @click="openEdit(tech)" class="action-btn action-btn--edit" :title="t('common.edit')"><Pencil aria-hidden="true" /></button>
                    <span class="row-actions-divider"></span>
                    <button type="button" @click="handleDelete(tech)" :disabled="deletingId === tech.id" class="action-btn action-btn--delete" :title="t('common.delete')">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === tech.id" /><Trash2 aria-hidden="true" v-else />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="techniciansPagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
          <span>{{ t("owner_technicians.pagination_text", { current: techniciansPagination.current_page, last: techniciansPagination.last_page, total: techniciansPagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="t('common.previous_page')" type="button" :disabled="techniciansPagination.current_page <= 1" @click="fetchTechnicians(techniciansPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
            <button :aria-label="t('common.next_page')" type="button" :disabled="techniciansPagination.current_page >= techniciansPagination.last_page" @click="fetchTechnicians(techniciansPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
          </div>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         ==================  تبويب: الصيانة  =========================
         ========================================================== -->
    <template v-else-if="activeTab === 'maintenance'">
      <div v-if="actionError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ actionError }}</div>

      <div v-if="isTasksLoading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>

      <div v-else-if="tasksError" class="glass-card text-center py-10 text-[12px] text-[#D9534F]">{{ tasksError }}</div>

      <div v-else-if="tasks.length === 0" class="glass-card text-center py-14">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center">
          <ClipboardList class="text-2xl text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" />
        </div>
        <p class="text-[12.5px] text-[#9a9d97]">{{ t("owner_technician_tasks.empty") }}</p>
      </div>

      <template v-else>
        <!-- ===== NEEDS REVIEW ===== -->
        <section v-if="needsReviewTasks.length > 0" v-reveal class="glass-card p-4 space-y-3">
          <h3 class="text-[13.5px] font-bold">{{ t("owner_technician_tasks.needs_review_title", { count: needsReviewTasks.length }) }}</h3>
          <div
            v-for="task in needsReviewTasks" :key="task.id"
            class="rounded-xl border border-[#e7e2d6] dark:border-white/10 p-3.5 space-y-2.5"
            :class="{ 'opacity-50 pointer-events-none': actingId === task.id }"
          >
            <div class="flex items-center justify-between flex-wrap gap-2">
              <p class="font-bold text-[12.5px]">{{ taskTypeLabel(task.type) }} — {{ task.generator_name }}</p>
              <span class="status-chip" :class="TASK_STATUS_META[task.status] ?? 'chip-info'">{{ taskStatusLabel(task.status) }}</span>
            </div>
            <p class="text-[11px] text-[#9a9d97]">{{ t("owner_technician_tasks.technician_label") }}: {{ task.technician?.name }}</p>
            <p v-if="task.completion_notes" class="text-[11.5px] bg-[#f4efe5]/60 dark:bg-white/5 rounded-lg p-2.5">{{ task.completion_notes }}</p>
            <div class="flex gap-2 pt-1">
              <button type="button" :disabled="actingId === task.id" @click="handleApprove(task)" class="btn-fill-brand flex-1 justify-center">
                <Check aria-hidden="true" /> {{ t("owner_technician_tasks.approve_action") }}
              </button>
              <button type="button" :disabled="actingId === task.id" @click="openReject(task)" class="btn-fill-brand btn-fill-brand--danger flex-1 justify-center">
                <X aria-hidden="true" /> {{ t("owner_technician_tasks.reject_action") }}
              </button>
            </div>
          </div>
        </section>

        <!-- ===== NEEDS RATING ===== -->
        <section v-if="needsRatingTasks.length > 0" v-reveal class="glass-card p-4 space-y-3">
          <h3 class="text-[13.5px] font-bold">{{ t("owner_technician_tasks.needs_rating_title", { count: needsRatingTasks.length }) }}</h3>
          <div
            v-for="task in needsRatingTasks" :key="task.id"
            class="rounded-xl border border-[#e7e2d6] dark:border-white/10 p-3.5 space-y-3"
            :class="{ 'opacity-50 pointer-events-none': actingId === task.id }"
            @vue:mounted="initRatingForm(task.id)"
          >
            <p class="font-bold text-[12.5px]">{{ taskTypeLabel(task.type) }} — {{ task.technician?.name }}</p>
            <div v-if="ratingForm[task.id]" class="space-y-2.5">
              <div class="flex gap-1">
                <button
                  v-for="star in 5" :key="star" type="button"
                  :aria-label="t('common.rate_n_stars', { n: star })"
                  @click="ratingForm[task.id].rating = star"
                  class="text-2xl transition-colors"
                  :class="star <= ratingForm[task.id].rating ? 'text-[#FFC107]' : 'text-[#e7e2d6] dark:text-white/15'"
                >
                  <Star aria-hidden="true" />
                </button>
              </div>
              <textarea v-model="ratingForm[task.id].comment" rows="2" maxlength="500" :placeholder="t('owner_technician_tasks.comment_placeholder')" class="field-input resize-none"></textarea>
              <button type="button" :disabled="actingId === task.id" @click="submitRating(task.id)" class="btn-fill-brand w-full justify-center">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="actingId === task.id" /><Send aria-hidden="true" v-else />
                {{ t("owner_technician_tasks.submit_rating") }}
              </button>
            </div>
          </div>
        </section>

        <!-- ===== ALL TASKS TABLE ===== -->
        <section v-reveal class="glass-card p-4 overflow-hidden">
          <h3 class="text-[13.5px] font-bold mb-3">{{ t("owner_technician_tasks.all_tasks_title") }}</h3>
          <div v-if="otherTasks.length === 0" class="text-center py-6 text-[11.5px] text-[#9a9d97]">{{ t("owner_technician_tasks.no_other_tasks") }}</div>
          <div v-else class="overflow-x-auto -mx-1">
            <table class="data-table w-full text-[12px] min-w-[640px]">
              <thead>
                <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                  <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_technician_tasks.task_col") }}</th>
                  <th class="py-2.5 px-3">{{ t("owner_technician_tasks.technician_col") }}</th>
                  <th class="py-2.5 px-3">{{ t("owner_technician_tasks.status_col") }}</th>
                  <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_technician_tasks.rating_col") }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="task in otherTasks" :key="task.id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center">
                  <td class="py-2.5 px-3">
                    <div class="font-bold">{{ taskTypeLabel(task.type) }}</div>
                    <div class="text-[10px] text-[#9a9d97]">{{ task.generator_name }}</div>
                  </td>
                  <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">
                    <template v-if="task.technician">{{ task.technician.name }}</template>
                    <button
                      v-else-if="task.status === 'pending'"
                      type="button" @click="openAssign(task)"
                      class="text-[10.5px] font-bold text-[#8A6D1F] hover:underline"
                    >{{ t("owner_technician_tasks.assign_action") }}</button>
                    <template v-else>-</template>
                  </td>
                  <td class="py-2.5 px-3"><span class="status-chip" :class="TASK_STATUS_META[task.status] ?? 'chip-info'">{{ taskStatusLabel(task.status) }}</span></td>
                  <td class="py-2.5 px-3">
                    <span v-if="task.rating" class="text-[#FFC107] font-bold text-[11px]"><Star aria-hidden="true" /> {{ task.rating.rating }}/5</span>
                    <span v-else class="text-[#9a9d97]">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="tasksPagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
            <span>{{ t("owner_technician_tasks.pagination_text", { current: tasksPagination.current_page, last: tasksPagination.last_page, total: tasksPagination.total }) }}</span>
            <div class="flex items-center gap-1">
              <button :aria-label="t('common.previous_page')" type="button" :disabled="tasksPagination.current_page <= 1" @click="fetchTasks(tasksPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
              <button :aria-label="t('common.next_page')" type="button" :disabled="tasksPagination.current_page >= tasksPagination.last_page" @click="fetchTasks(tasksPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
            </div>
          </div>
        </section>
      </template>
    </template>

    <!-- ==========================================================
         ==================  تبويب: بوابة الفني  =====================
         ========================================================== -->
    <template v-else-if="activeTab === 'portal'">
      <section v-reveal class="glass-card p-4">
        <label class="field-label">{{ t("owner_technician_portal.select_label") }}</label>
        <AppDropdownSelect
          :model-value="selectedTechnicianId"
          @update:model-value="loadTechnicianWork"
          :options="technicianSelectOptions"
          :placeholder="t('owner_technician_portal.choose_technician')"
          variant="field" width-class="max-w-sm"
        />
      </section>

      <div v-if="!selectedTechnicianId" class="glass-card text-center py-14">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center">
          <Smartphone class="text-2xl text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" />
        </div>
        <p class="text-[12.5px] text-[#9a9d97]">{{ t("owner_technician_portal.empty_prompt") }}</p>
      </div>

      <div v-else-if="isPortalLoading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>

      <div v-else-if="portalError" class="glass-card text-center py-10 text-[12px] text-[#D9534F]">{{ portalError }}</div>

      <template v-else>
        <!-- ===== المهام ===== -->
        <section v-reveal class="glass-card p-4">
          <h3 class="text-[13.5px] font-bold mb-3"><ClipboardList class="me-1.5 text-[#52733D]" aria-hidden="true" />{{ t("owner_technician_portal.tasks_title") }}</h3>
          <div v-if="portalTasks.length === 0" class="text-center py-6 text-[11.5px] text-[#9a9d97]">{{ t("owner_technician_portal.no_tasks") }}</div>
          <div v-else class="space-y-2">
            <div v-for="task in portalTasks" :key="task.id" class="flex items-center justify-between gap-2 border-b border-[#f0ece0] dark:border-white/5 last:border-0 py-2">
              <div class="min-w-0">
                <p class="font-bold text-[12px] truncate">{{ taskTypeLabel(task.type) }}</p>
                <p class="text-[10px] text-[#9a9d97]">{{ task.generator_name }}</p>
              </div>
              <span class="status-chip shrink-0" :class="TASK_STATUS_META[task.status] ?? 'chip-info'">{{ taskStatusLabel(task.status) }}</span>
            </div>
          </div>
        </section>

        <!-- ===== القراءات ===== -->
        <section v-reveal class="glass-card p-4">
          <h3 class="text-[13.5px] font-bold mb-3"><Gauge class="me-1.5 text-[#17A2B8]" aria-hidden="true" />{{ t("owner_technician_portal.readings_title") }}</h3>
          <div v-if="portalReadings.length === 0" class="text-center py-6 text-[11.5px] text-[#9a9d97]">{{ t("owner_technician_portal.no_readings") }}</div>
          <div v-else class="space-y-2">
            <div v-for="reading in portalReadings" :key="reading.id" class="flex items-center justify-between gap-2 border-b border-[#f0ece0] dark:border-white/5 last:border-0 py-2">
              <div class="min-w-0">
                <p class="font-bold text-[12px] truncate">{{ reading.subscriber?.name ?? "-" }}</p>
                <p class="text-[10px] text-[#9a9d97]" dir="ltr">{{ reading.reading_date }}</p>
              </div>
              <span class="font-mono font-data text-[11.5px] font-bold shrink-0" dir="ltr">{{ reading.current_reading }}</span>
            </div>
          </div>
        </section>

        <!-- ===== الدفعات ===== -->
        <section v-reveal class="glass-card p-4">
          <h3 class="text-[13.5px] font-bold mb-3"><Wallet class="me-1.5 text-[#D4AF37]" aria-hidden="true" />{{ t("owner_technician_portal.payments_title") }}</h3>
          <div v-if="portalPayments.length === 0" class="text-center py-6 text-[11.5px] text-[#9a9d97]">{{ t("owner_technician_portal.no_payments") }}</div>
          <div v-else class="space-y-2">
            <div v-for="payment in portalPayments" :key="payment.id" class="flex items-center justify-between gap-2 border-b border-[#f0ece0] dark:border-white/5 last:border-0 py-2">
              <span class="font-mono font-data text-[12px] font-bold" dir="ltr">{{ payment.amount }} {{ payment.currency }}</span>
              <span class="status-chip shrink-0" :class="PORTAL_PAYMENT_STATUS_META[payment.status] ?? 'chip-info'">{{ portalPaymentStatusLabel(payment.status) }}</span>
            </div>
          </div>
        </section>
      </template>
    </template>

    <!-- ===== ADD TECHNICIAN MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isFormOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isFormOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><UserPlus aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_technicians.add_modal_title") }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="isFormOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="px-5 pt-4">
              <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
                <button
                  type="button" @click="addMode = 'existing'"
                  class="flex-1 py-1.5 rounded-full text-[11.5px] font-bold transition-colors"
                  :class="addMode === 'existing' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
                >{{ t("owner_technicians.mode_existing") }}</button>
                <button
                  type="button" @click="addMode = 'new'"
                  class="flex-1 py-1.5 rounded-full text-[11.5px] font-bold transition-colors"
                  :class="addMode === 'new' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
                >{{ t("owner_technicians.mode_new") }}</button>
              </div>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="saveError?.message" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveError.message }}</div>

              <template v-if="addMode === 'existing'">
                <div>
                  <label class="field-label">{{ t("owner_technicians.search_user_label") }}</label>
                  <input
                    v-model="userSearch" @input="handleUserSearch" type="text"
                    :placeholder="t('owner_technicians.search_user_placeholder')"
                    class="field-input"
                  />
                  <p v-if="saveError?.errors?.user_id" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.user_id[0] }}</p>
                </div>

                <div v-if="isLoadingUsers" class="text-[11px] text-[#9a9d97] text-center py-2">{{ t("common.searching") }}</div>

                <div v-else-if="eligibleUsers.length > 0" class="border border-[#e7e2d6] dark:border-white/10 rounded-lg divide-y divide-[#e7e2d6] dark:divide-white/10 max-h-40 overflow-y-auto">
                  <label
                    v-for="u in eligibleUsers" :key="u.id"
                    class="flex items-center gap-2 p-2.5 cursor-pointer hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition-colors"
                    :class="{ 'bg-[#f4efe5] dark:bg-white/10': newForm.user_id === u.id }"
                  >
                    <input v-model="newForm.user_id" type="radio" :value="u.id" class="accent-[#52733D]" />
                    <span class="text-[12px] font-bold">{{ u.name }}</span>
                    <span class="text-[10.5px] text-[#9a9d97]">{{ u.email }}</span>
                  </label>
                </div>

                <div>
                  <label class="field-label">{{ t("owner_technicians.notes_label") }} <span class="text-[#9a9d97] font-normal">({{ t("owner_technicians.optional") }})</span></label>
                  <textarea v-model="newForm.notes" rows="2" maxlength="1000" class="field-input resize-none"></textarea>
                </div>
              </template>

              <template v-else>
                <div>
                  <label class="field-label">{{ t("owner_technicians.name_label") }}</label>
                  <input v-model="newAccountForm.name" type="text" maxlength="255" class="field-input" />
                  <p v-if="saveError?.errors?.name" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.name[0] }}</p>
                </div>
                <div>
                  <label class="field-label">{{ t("owner_technicians.email_label") }}</label>
                  <input v-model="newAccountForm.email" type="email" maxlength="255" class="field-input" dir="ltr" />
                  <p v-if="saveError?.errors?.email" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.email[0] }}</p>
                </div>
                <div>
                  <label class="field-label">{{ t("owner_technicians.password_label") }}</label>
                  <input v-model="newAccountForm.password" type="password" class="field-input" dir="ltr" />
                  <p v-if="saveError?.errors?.password" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.password[0] }}</p>
                </div>
                <div>
                  <label class="field-label">{{ t("owner_technicians.password_confirmation_label") }}</label>
                  <input v-model="newAccountForm.password_confirmation" type="password" class="field-input" dir="ltr" />
                </div>
                <div>
                  <label class="field-label">{{ t("owner_technicians.notes_label") }} <span class="text-[#9a9d97] font-normal">({{ t("owner_technicians.optional") }})</span></label>
                  <textarea v-model="newAccountForm.notes" rows="2" maxlength="1000" class="field-input resize-none"></textarea>
                </div>
              </template>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="isFormOpen = false" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
              <button
                type="button" @click="handleCreate"
                :disabled="isSaving || (addMode === 'existing' ? !newForm.user_id : !newAccountForm.name || !newAccountForm.email || !newAccountForm.password)"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Plus aria-hidden="true" v-else />
                {{ isSaving ? t("owner_technicians.adding_ellipsis") : t("owner_technicians.add_button") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== EDIT TECHNICIAN MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="editingTechnician" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="editingTechnician = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--gold">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Pencil aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title truncate">{{ editingTechnician.name }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="editingTechnician = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="saveError?.message" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveError.message }}</div>

              <div>
                <label class="field-label">{{ t("owner_technicians.status_label") }}</label>
                <AppDropdownSelect
                  v-model="editForm.status"
                  :options="EDIT_STATUS_OPTIONS"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>
              <div>
                <label class="field-label">{{ t("owner_technicians.notes_label") }}</label>
                <textarea v-model="editForm.notes" rows="2" maxlength="1000" class="field-input resize-none"></textarea>
              </div>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="editingTechnician = null" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
              <button type="button" @click="handleSaveEdit" :disabled="isSaving" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
                {{ isSaving ? t("owner_technicians.saving_ellipsis") : t("owner_technicians.save") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== REJECT TASK MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="rejectModal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="rejectModal.open = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--danger">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><X aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title truncate">{{ rejectModal.taskLabel }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="rejectModal.open = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>
            <div class="p-5 space-y-3">
              <label class="field-label">{{ t("owner_technician_tasks.rejection_reason_label") }}</label>
              <textarea v-model="rejectModal.reason" rows="3" maxlength="1000" required class="field-input resize-none"></textarea>
            </div>
            <div class="modal-footer-brand">
              <button type="button" @click="rejectModal.open = false" class="btn-outline-brand">{{ t("owner_technician_tasks.cancel") }}</button>
              <button type="button" :disabled="!rejectModal.reason" @click="submitReject" class="btn-fill-brand btn-fill-brand--danger">
                <X aria-hidden="true" /> {{ t("owner_technician_tasks.confirm_reject") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== طلب صيانة جديد ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isCreateRequestOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isCreateRequestOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><ClipboardList aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_technician_tasks.new_request_title") }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="isCreateRequestOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="createTaskError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ createTaskError }}</div>

              <div>
                <label class="field-label">{{ t("owner_technician_tasks.generator_label") }}</label>
                <AppDropdownSelect
                  v-model="createRequestForm.generator_id"
                  :options="generatorSelectOptions"
                  :placeholder="t('owner_technician_tasks.choose_generator')"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>

              <div>
                <label class="field-label">{{ t("owner_technician_tasks.type_label") }}</label>
                <AppDropdownSelect
                  v-model="createRequestForm.type"
                  :options="TASK_TYPE_OPTIONS"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>

              <div>
                <label class="field-label">{{ t("owner_technician_tasks.assign_optional_label") }}</label>
                <AppDropdownSelect
                  v-model="createRequestForm.technician_id"
                  :options="[{ value: '', label: t('owner_technician_tasks.assign_later') }, ...technicianSelectOptions]"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>

              <div>
                <label class="field-label">{{ t("owner_technician_tasks.instructions_label") }} <span class="text-[#9a9d97] font-normal">({{ t("owner_technicians.optional") }})</span></label>
                <textarea v-model="createRequestForm.instructions" rows="2" maxlength="1000" class="field-input resize-none"></textarea>
              </div>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="isCreateRequestOpen = false" class="btn-outline-brand">{{ t("owner_technician_tasks.cancel") }}</button>
              <button type="button" @click="handleCreateRequest" :disabled="isCreatingTask || !createRequestForm.generator_id" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingTask" /><Plus aria-hidden="true" v-else />
                {{ isCreatingTask ? t("owner_technician_tasks.creating_ellipsis") : t("owner_technician_tasks.create_request_button") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== تعيين فني ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="assigningTask" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="assigningTask = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--gold">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><UserCheck aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title truncate">{{ taskTypeLabel(assigningTask?.type) }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="assigningTask = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>
            <div class="p-5 space-y-3">
              <div v-if="assignTaskError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ assignTaskError }}</div>
              <label class="field-label">{{ t("owner_technician_tasks.assign_optional_label") }}</label>
              <AppDropdownSelect
                v-model="assignTechnicianId"
                :options="technicianSelectOptions"
                :placeholder="t('owner_technician_tasks.choose_technician')"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
            <div class="modal-footer-brand">
              <button type="button" @click="assigningTask = null" class="btn-outline-brand">{{ t("owner_technician_tasks.cancel") }}</button>
              <button type="button" :disabled="isAssigningTask || !assignTechnicianId" @click="handleAssign" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isAssigningTask" /><UserCheck aria-hidden="true" v-else />
                {{ t("owner_technician_tasks.assign_action") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>