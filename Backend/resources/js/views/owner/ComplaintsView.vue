<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useOwnerComplaints } from "@/composables/useOwnerComplaints";
import { usePermissions } from "@/composables/usePermissions";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { ChevronLeft, ChevronRight, CircleAlert, CircleCheck, LoaderCircle, Pencil, Plus, Save, Search, Send, TriangleAlert, X } from "@lucide/vue";


const { can } = usePermissions();
const toast = useToastStore();
const { t } = useI18n();

const {
  complaints,
  pagination,
  isLoading,
  error,
  search,
  statusFilter,
  hasComplaints,
  fetchComplaints,
  onSearchInput,
  onFilterChange,

  isSubmittingNew,
  newComplaintError,
  submitComplaint,

  resolvingId,
  isResolving,
  resolveError,
  startResolving,
  cancelResolving,
  resolveComplaint,
} = useOwnerComplaints();

const STATUS_META = {
  pending: "chip-warning",
  in_progress: "chip-info",
  resolved: "chip-success",
};
function statusLabel(status) {
  return t(`owner_complaints.status.${status}`, status);
}

const COMPLAINABLE_TYPE_KEYS = {
  Generator: "complaints_page.complainable_generator",
  Fault: "complaints_page.complainable_fault",
  Subscription: "complaints_page.complainable_subscription",
  Invoice: "complaints_page.complainable_invoice",
  Payment: "complaints_page.complainable_payment",
};
function complainableTypeLabel(type) {
  const key = COMPLAINABLE_TYPE_KEYS[type];
  return key ? t(key) : type;
}

const STATUS_PILLS = computed(() => [
  { v: "", l: t("common.all") },
  { v: "pending", l: statusLabel("pending") },
  { v: "in_progress", l: statusLabel("in_progress") },
  { v: "resolved", l: statusLabel("resolved") },
]);

function selectStatusFilter(value) {
  statusFilter.value = value;
  onFilterChange();
}

const RESOLVE_STATUS_OPTIONS = computed(() => [
  { value: "in_progress", label: statusLabel("in_progress") },
  { value: "resolved", label: statusLabel("resolved") },
]);

const showNewForm = ref(false);
const newComplaintForm = reactive({ subject: "", description: "" });

const resolveForm = reactive({ status: "in_progress", resolution_note: "" });

async function handleCreate() {
  const ok = await submitComplaint({ ...newComplaintForm });
  if (ok) {
    newComplaintForm.subject = "";
    newComplaintForm.description = "";
    showNewForm.value = false;
    toast.show({
      type: "success",
      title: t("owner_complaints.submitted_toast_title"),
      message: t("owner_complaints.submitted_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_complaints.submit_failed_title"),
      message: newComplaintError.value ?? t("owner_complaints.create_error"),
    });
  }
}

function openResolveForm(complaint) {
  resolveForm.status = "in_progress";
  resolveForm.resolution_note = complaint.resolution_note ?? "";
  startResolving(complaint.id);
}

async function handleResolveSubmit(complaintId) {
  const ok = await resolveComplaint(complaintId, { ...resolveForm });
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_complaints.status_updated_toast_title"),
      message: t("owner_complaints.status_updated_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_complaints.update_failed_title"),
      message: resolveError.value ?? t("owner_complaints.resolve_error"),
    });
  }
}

onMounted(() => fetchComplaints());
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_complaints.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">{{ t("owner_complaints.eyebrow") }}</p>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <TriangleAlert aria-hidden="true" />
            </span>
            {{ t("owner_complaints.title") }}
          </h1>
        </div>
        <button
          v-if="can('complaints.create')" type="button" @click="showNewForm = !showNewForm"
          class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
        >
          <X aria-hidden="true" v-if="showNewForm" /><Plus aria-hidden="true" v-else />
          {{ showNewForm ? t("owner_complaints.cancel") : t("owner_complaints.new_complaint_button") }}
        </button>
      </div>
    </section>

    <!-- ===== NEW COMPLAINT FORM ===== -->
    <section v-if="showNewForm" v-reveal class="glass-card p-5">
      <h3 class="text-[13.5px] font-bold mb-3">{{ t("owner_complaints.new_complaint_button") }}</h3>
      <form @submit.prevent="handleCreate" class="space-y-3.5">
        <div v-if="newComplaintError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ newComplaintError }}</div>

        <div>
          <label class="field-label">{{ t("owner_complaints.subject_label") }}</label>
          <input v-model="newComplaintForm.subject" type="text" required maxlength="191" class="field-input" />
        </div>

        <div>
          <label class="field-label">{{ t("owner_complaints.description_label") }}</label>
          <textarea v-model="newComplaintForm.description" required rows="3" maxlength="2000" class="field-input resize-none"></textarea>
        </div>

        <button type="submit" :disabled="isSubmittingNew" class="btn-fill-brand">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmittingNew" /><Send aria-hidden="true" v-else />
          {{ isSubmittingNew ? t("owner_complaints.submitting_ellipsis") : t("owner_complaints.submit_button") }}
        </button>
      </form>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative flex-1 min-w-[220px] max-w-xs">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
          <input
            v-model="search" type="text" @input="onSearchInput"
            :placeholder="t('owner_complaints.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="pill in STATUS_PILLS" :key="pill.v" type="button"
            @click="selectStatusFilter(pill.v)"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="statusFilter === pill.v ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >{{ pill.l }}</button>
        </div>
      </div>
    </section>

    <!-- ===== LIST ===== -->
    <section v-reveal class="space-y-3">
      <div v-if="error" class="glass-card text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="isLoading" class="space-y-2">
        <div v-for="i in 4" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>

      <div v-else-if="!hasComplaints" class="glass-card text-center py-12">
        <CircleCheck class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97]">{{ t("owner_complaints.no_matching_complaints") }}</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="complaint in complaints" :key="complaint.id" class="glass-card p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <h3 class="font-bold text-[13px]">{{ complaint.subject }}</h3>
                <span class="status-chip" :class="STATUS_META[complaint.status] ?? 'chip-info'">{{ statusLabel(complaint.status) }}</span>
              </div>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ complaint.description }}</p>
              <p class="text-[10.5px] text-[#9a9d97] mt-2">
                {{ t("owner_complaints.submitted_by", { name: complaint.submitted_by?.name ?? "—" }) }}
                <span v-if="complaint.complainable">
                  — {{ t("owner_complaints.regarding", { type: complainableTypeLabel(complaint.complainable.type), id: complaint.complainable.id }) }}
                </span>
              </p>
              <p v-if="complaint.resolution_note" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-2 bg-[#f4efe5]/70 dark:bg-white/5 rounded-lg p-2.5">
                {{ t("owner_complaints.resolution_note_prefix") }} {{ complaint.resolution_note }}
              </p>
            </div>

            <button
              v-if="can('complaints.resolve') && complaint.status !== 'resolved' && resolvingId !== complaint.id"
              type="button" @click="openResolveForm(complaint)"
              class="action-btn action-btn--edit shrink-0" :title="t('owner_complaints.update_status_button')"
            >
              <Pencil aria-hidden="true" />
            </button>
          </div>

          <form v-if="resolvingId === complaint.id" @submit.prevent="handleResolveSubmit(complaint.id)" class="mt-4 pt-4 border-t border-[#f0ece0] dark:border-white/5 space-y-3">
            <div v-if="resolveError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ resolveError }}</div>

            <div>
              <label class="field-label">{{ t("owner_complaints.new_status_label") }}</label>
              <AppDropdownSelect
                v-model="resolveForm.status"
                :options="RESOLVE_STATUS_OPTIONS"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>

            <div>
              <label class="field-label">
                {{ t("owner_complaints.resolution_note_label") }}
                {{ resolveForm.status === "resolved" ? t("owner_complaints.note_required") : t("owner_complaints.note_optional") }}
              </label>
              <textarea
                v-model="resolveForm.resolution_note" rows="2" maxlength="2000"
                :required="resolveForm.status === 'resolved'"
                class="field-input resize-none"
              ></textarea>
            </div>

            <div class="flex gap-2">
              <button type="submit" :disabled="isResolving" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isResolving" /><Save aria-hidden="true" v-else />
                {{ isResolving ? t("owner_complaints.saving_ellipsis") : t("owner_complaints.save") }}
              </button>
              <button type="button" @click="cancelResolving" class="btn-outline-brand">{{ t("owner_complaints.cancel") }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[11px] text-[#9a9d97]">
        <span>{{ t("owner_complaints.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchComplaints(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchComplaints(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
        </div>
      </div>
    </section>
  </div>
</template>
