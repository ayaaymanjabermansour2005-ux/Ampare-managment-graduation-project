<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminFaults } from "@/composables/useAdminFaults";
import faultService from "@/services/faultService";
import { useFaultPredictions } from "@/composables/useFaultPredictions";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { printTable } from "@/utils/printTable";
import generatorService from "@/services/generatorService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ChevronLeft, ChevronRight, CircleAlert, CircleCheck, Eye, FileSpreadsheet, LoaderCircle, Plus, PlugZap, Printer, Search, ShieldCheck, Trash2, TriangleAlert, User, WandSparkles, X, Zap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t, locale } = useI18n();
const { confirm } = useConfirm();

const {
  faults, pagination, isLoading, error,
  search, statusFilter,
  isOverriding, overrideError,
  deletingId,
  fetchFaults, onSearchInput, onFilterChange,
  overrideFaultStatus, deleteFault,
} = useAdminFaults();

const {
  predictions,
  isLoading: isLoadingPredictions,
  error: predictionsError,
  fetchPredictions,
  decidingId: decidingPredictionId,
  decisionError: predictionDecisionError,
  confirmPrediction,
  dismissPrediction,
} = useFaultPredictions();

async function handleConfirmPrediction(prediction) {
  const confirmed = await confirm({
    title: t("fault_predictions.confirm_title"),
    message: t("fault_predictions.confirm_message", { type: prediction.prediction_type, generator: prediction.generator?.name ?? "—" }),
    confirmLabel: t("fault_predictions.confirm_action"),
  });
  if (confirmed) await confirmPrediction(prediction.id);
}

async function handleDismissPrediction(prediction) {
  const confirmed = await confirm({
    title: t("fault_predictions.dismiss_title"),
    message: t("fault_predictions.dismiss_message"),
    confirmLabel: t("fault_predictions.dismiss_action"),
    variant: "danger",
  });
  if (confirmed) await dismissPrediction(prediction.id);
}

const STATUS_META = {
  pending_verification: { chip: "chip-info", key: "faults_page.status_pending_verification" },
  verified: { chip: "chip-warning", key: "faults_page.status_verified" },
  rejected: { chip: "chip-danger", key: "faults_page.status_rejected" },
  in_repair: { chip: "chip-warning", key: "faults_page.status_in_repair" },
  resolved: { chip: "chip-success", key: "faults_page.status_resolved" },
  closed: { chip: "chip-info", key: "faults_page.status_closed" },
};
function statusLabel(s) {
  const m = STATUS_META[s];
  if (!m) return s;
  return t(m.key);
}
const overrideStatusOptions = computed(() =>
  Object.keys(STATUS_META).map((key) => ({ value: key, label: statusLabel(key) })),
);
const STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  ...Object.entries(STATUS_META).map(([value, m]) => ({ value, label: t(m.key) })),
]);

const PRIORITY_META = {
  low: { chip: "chip-info", key: "faults_page.priority_low" },
  medium: { chip: "chip-warning", key: "complaints_page.priority_medium" },
  high: { chip: "chip-warning", key: "complaints_page.priority_high" },
  critical: { chip: "chip-danger", key: "faults_page.priority_critical" },
};
function priorityLabel(p) {
  const m = PRIORITY_META[p];
  return m ? t(m.key) : p;
}

const priorityOptions = computed(() =>
  Object.keys(PRIORITY_META).map((key) => ({ value: key, label: priorityLabel(key) })),
);

const toast = useToastStore();
const isAddModalOpen = ref(false);
const isCreating = ref(false);
const createError = ref(null);
const generatorOptions = ref([]);
const addForm = ref({ generator_id: "", title: "", description: "", priority: "medium" });

async function openAddModal() {
  isAddModalOpen.value = true;
  createError.value = null;
  addForm.value = { generator_id: "", title: "", description: "", priority: "medium" };
  if (generatorOptions.value.length === 0) {
    try {
      const { data } = await generatorService.list({ per_page: 500 });
      const list = data.data?.data ?? data.data ?? [];
      generatorOptions.value = list.map((g) => ({ value: g.id, label: g.name }));
    } catch {
      generatorOptions.value = [];
    }
  }
}

function closeAddModal() {
  isAddModalOpen.value = false;
}

async function handleCreateFault() {
  isCreating.value = true;
  createError.value = null;
  try {
    await faultService.create({
      generator_id: addForm.value.generator_id,
      title: addForm.value.title,
      description: addForm.value.description,
      priority: addForm.value.priority,
    });
    isAddModalOpen.value = false;
    toast.show({ type: "success", message: t("faults_page.add_fault_button") });
    await fetchFaults(1);
  } catch (err) {
    createError.value = normalizeApiError(err, t("faults_page.create_error")).message;
  } finally {
    isCreating.value = false;
  }
}

function timeAgo(str) {
  if (!str) return "—";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}

/* ---------------- إصلاح: علامة النسبة المئوية كانت "٪" ثابتة (عربي دائمًا)
   حتى بالواجهة الإنجليزية — أصبحت الآن تتبع اللغة الحالية ---------------- */
function percentSign() {
  return locale.value === "ar" ? "٪" : "%";
}

/* ---------------- KPI Cards ----------------*/
const countOnPage = (status) => faults.value.filter((f) => f.status === status).length;
const KPI_CARDS = computed(() => [
  { icon: "fa-triangle-exclamation", label: t("faults_page.total_faults"), value: pagination.value.total, tone: "danger" },
  { icon: "fa-hourglass-half", label: statusLabel("pending_verification") + t("common.this_page_suffix"), value: countOnPage("pending_verification"), tone: "info" },
  { icon: "fa-screwdriver-wrench", label: statusLabel("in_repair") + t("common.this_page_suffix"), value: countOnPage("in_repair"), tone: "warning" },
  { icon: "fa-circle-check", label: statusLabel("resolved") + t("common.this_page_suffix"), value: countOnPage("resolved"), tone: "success" },
]);

/* ---------------- Pagination بأزرار محدودة (نفس منهجية صفحتي الفنيين والشكاوى) ---------------- */
const paginationRange = computed(() => {
  const total = pagination.value.last_page;
  const current = pagination.value.current_page;
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
});

/* ---------------- نافذة التفاصيل + تجاوز الحالة ---------------- */
const isDetailOpen = ref(false);
const activeFault = ref(null);
const overrideStatusValue = ref("");
const overrideReason = ref("");

function openDetail(f) {
  activeFault.value = f;
  overrideStatusValue.value = f.status;
  overrideReason.value = "";
  isDetailOpen.value = true;
}

async function handleOverrideSubmit() {
  if (!overrideReason.value.trim()) return;
  const ok = await overrideFaultStatus(activeFault.value.id, {
    status: overrideStatusValue.value,
    admin_override_reason: overrideReason.value.trim(),
  });
  if (ok) isDetailOpen.value = false;
}

async function handleDelete(f) {
  const confirmed = await confirm({
    title: t("faults_page.delete_fault_title"),
    message: t("faults_page.delete_fault_message", { title: f.title }),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;
  await deleteFault(f.id);
}

const exportUrl = computed(() =>
  faultService.exportUrl({
    search: search.value,
    status: statusFilter.value,
  })
);

function handlePrint() {
  printTable({
    title: t("faults_page.faults_list_title"),
    locale: locale.value,
    columns: [
      { label: t("faults_page.col_title"), value: (f) => f.title },
      { label: t("dashboard.generator_col"), value: (f) => f.generator_name ?? "—" },
      { label: t("subscribers_page.subscriber_col"), value: (f) => f.reported_by_name ?? "—" },
      { label: t("complaints_page.col_priority"), value: (f) => priorityLabel(f.priority) },
      { label: t("dashboard.status_col"), value: (f) => statusLabel(f.status) },
      { label: t("faults_page.col_reported_at"), value: (f) => timeAgo(f.reported_at) },
    ],
    rows: faults.value,
  });
}

onMounted(() => {
  fetchFaults(1);
  fetchPredictions();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D9534F]/20 dark:bg-[#D9534F]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
        <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("faults_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#D9534F] to-[#8A6D1F] text-white flex items-center justify-center text-base">
              <TriangleAlert aria-hidden="true" />
            </span>
            {{ $t("faults_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("faults_page.subtitle") }}
          </p>
        </div>
        <button
          type="button"
          @click="openAddModal"
          class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2 shrink-0"
        >
          <Plus class="text-[11px]" aria-hidden="true" />
          {{ $t("faults_page.add_fault_button") }}
        </button>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal class="print-hidden">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
        />
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4 print-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="search" type="text" @input="onSearchInput"
            :placeholder="$t('faults_page.search_placeholder')"
            class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
          />
        </div>
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
            @click="statusFilter = pill.value; onFilterChange()"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >{{ pill.label }}</button>
        </div>
        <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
        <a
          :href="exportUrl"
          target="_blank"
          class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
          :title="$t('faults_page.export_excel')"
        >
          <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
        </a>
        <button
          type="button"
          @click="handlePrint"
          class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
          :title="$t('faults_page.print')"
        >
          <Printer class="text-[11px]" aria-hidden="true" />
        </button>
      </div>
    </section>

    <!-- ===== FAULT PREDICTIONS ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden print-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ $t("owner_dashboard.fault_predictions_title") }}</h3>

      <div v-if="predictionDecisionError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-3">
        {{ predictionDecisionError }}
      </div>

      <div v-if="isLoadingPredictions" class="space-y-2">
        <div v-for="i in 2" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="predictionsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ predictionsError }}</div>

      <div v-else-if="predictions.length === 0" class="text-center py-8">
        <CircleCheck class="text-2xl text-[#28A745] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("faults_page.no_predictions_pending") }}</p>
      </div>

      <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
        <div
          v-for="p in predictions"
          :key="p.id"
          class="flex items-center gap-3.5 py-3.5 px-1.5"
          :class="{ 'opacity-50 pointer-events-none': decidingPredictionId === p.id }"
        >
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center shrink-0 text-white">
            <WandSparkles class="text-[12px]" aria-hidden="true" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <p class="text-[12.5px] font-bold truncate">{{ p.prediction_type }}</p>
              <span
                v-if="p.confidence !== null"
                class="status-chip chip-info shrink-0"
              >{{ $t("faults_page.confidence_label") }} {{ Math.round(p.confidence * 100) }}{{ percentSign() }}</span>
            </div>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 truncate">
              {{ p.generator?.name ?? "—" }} · {{ p.source_label }}
            </p>
            <p v-if="p.recommendation" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1 truncate">
              {{ p.recommendation }}
            </p>
          </div>

          <div class="flex items-center gap-1.5 shrink-0">
            <button
              type="button"
              @click="handleConfirmPrediction(p)"
              :disabled="decidingPredictionId === p.id"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold text-white bg-gradient-to-l from-[#3E582E] to-[#52733D] hover:opacity-90 transition disabled:opacity-50"
            >
              {{ $t("fault_predictions.confirm_button") }}
            </button>
            <button
              type="button"
              @click="handleDismissPrediction(p)"
              :disabled="decidingPredictionId === p.id"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#D9534F] hover:bg-[#D9534F]/10 transition"
              :title="$t('fault_predictions.reject_button')"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="decidingPredictionId === p.id" style="font-size:12px" /><X aria-hidden="true" v-else style="font-size:12px" />
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== LIST ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ $t("faults_page.faults_list_title") }}</h3>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="faults.length === 0" class="text-center py-12">
        <CircleCheck class="text-2xl text-[#28A745] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("faults_page.no_matching_faults") }}</p>
      </div>

      <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
        <div
          v-for="f in faults"
          :key="f.id"
          class="flex items-center gap-3.5 py-3.5 px-1.5"
          :class="{ 'opacity-50 pointer-events-none': deletingId === f.id }"
        >
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#D9534F] to-[#8A6D1F] flex items-center justify-center shrink-0 text-white">
            <Zap class="text-[12px]" aria-hidden="true" />
          </div>

          <button type="button" @click="openDetail(f)" class="flex-1 min-w-0 text-start">
            <div class="flex items-center gap-2">
              <p class="text-[12.5px] font-bold truncate">{{ f.title }}</p>
              <span class="status-chip shrink-0" :class="PRIORITY_META[f.priority]?.chip">{{ priorityLabel(f.priority) }}</span>
              <span class="status-chip shrink-0" :class="STATUS_META[f.status]?.chip">{{ statusLabel(f.status) }}</span>
            </div>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 truncate">
              {{ f.generator_name ?? "—" }} · {{ f.reported_by_name ?? "—" }} · {{ timeAgo(f.reported_at) }}
            </p>
          </button>

          <div class="flex items-center gap-1 shrink-0">
            <button
              type="button"
              @click="openDetail(f)"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#17A2B8] hover:bg-[#17A2B8]/10 transition"
              :title="$t('common.view')"
            >
              <Eye class="text-[12px]" aria-hidden="true" />
            </button>
            <button
              type="button"
              @click="handleDelete(f)"
              :disabled="deletingId === f.id"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#D9534F] hover:bg-[#D9534F]/10 transition"
              :title="$t('common.delete')"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === f.id" style="font-size:12px" /><Trash2 aria-hidden="true" v-else style="font-size:12px" />
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination بأزرار محدودة -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("faults_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="pagination.current_page <= 1"
            @click="fetchFaults(pagination.current_page - 1)"
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
              @click="fetchFaults(page)"
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
            @click="fetchFaults(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== نافذة التفاصيل + تجاوز الحالة ===== -->
    <Teleport to="body">
      <div v-if="isDetailOpen && activeFault" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="isDetailOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[85vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--danger shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Zap aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ activeFault.title }}</h3>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isDetailOpen = false" class="modal-head-brand__close">
              <X aria-hidden="true" />
            </button>
          </div>
          <div class="p-5 space-y-4 overflow-y-auto">
            <div class="flex flex-wrap items-center gap-2 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
              <span class="inline-flex items-center gap-1.5"><PlugZap aria-hidden="true" /> {{ activeFault.generator_name }}</span>
              <span>·</span>
              <span class="inline-flex items-center gap-1.5"><User aria-hidden="true" /> {{ activeFault.reported_by_name }}</span>
              <span class="status-chip" :class="PRIORITY_META[activeFault.priority]?.chip">{{ priorityLabel(activeFault.priority) }}</span>
              <span class="status-chip" :class="STATUS_META[activeFault.status]?.chip">{{ statusLabel(activeFault.status) }}</span>
            </div>
            <p class="text-[12.5px] leading-relaxed glass-card p-3.5">{{ activeFault.description }}</p>

            <div v-if="activeFault.admin_override_reason" class="glass-card p-3.5 border border-[#D4AF37]/40">
              <p class="text-[11px] font-bold text-[#8A6D1F] mb-1"><ShieldCheck aria-hidden="true" /> {{ $t("faults_page.previously_overridden") }}</p>
              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ activeFault.admin_override_reason }}</p>
            </div>

            <div class="border-t border-[#eee8da] dark:border-white/10 pt-4">
              <p class="text-[12px] font-extrabold mb-3 flex items-center gap-1.5"><ShieldCheck class="text-[#8A6D1F]" aria-hidden="true" /> {{ $t("faults_page.override_status_manually") }}</p>
              <div v-if="overrideError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-3">{{ overrideError }}</div>
              <div class="space-y-3">
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("faults_page.new_status_label") }}</label>
                  <AppDropdownSelect v-model="overrideStatusValue" :options="overrideStatusOptions" variant="field" width-class="w-full" match-trigger-width />
                </div>
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("faults_page.override_reason_label") }} <span class="text-[#D9534F]">*</span></label>
                  <textarea v-model="overrideReason" rows="3" :placeholder="$t('faults_page.override_reason_placeholder')" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="isDetailOpen = false" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="handleOverrideSubmit" :disabled="isOverriding || !overrideReason.trim()" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isOverriding" /><ShieldCheck aria-hidden="true" v-else />
              {{ $t("faults_page.override_status_button") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===== نافذة إضافة عطل جديد ===== -->
    <Teleport to="body">
      <div v-if="isAddModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="closeAddModal">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[85vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--danger shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><TriangleAlert aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("faults_page.add_fault_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ $t("faults_page.add_fault_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="closeAddModal" class="modal-head-brand__close">
              <X aria-hidden="true" />
            </button>
          </div>
          <form @submit.prevent="handleCreateFault" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="createError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ createError }}
            </div>
            <div>
              <label class="field-label">{{ $t("faults_page.field_generator") }}</label>
              <AppDropdownSelect
                v-model="addForm.generator_id"
                :options="generatorOptions"
                :placeholder="$t('faults_page.select_generator_placeholder')"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
            <div>
              <label class="field-label">{{ $t("faults_page.field_title") }}</label>
              <input v-model="addForm.title" type="text" required maxlength="150" class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ $t("faults_page.field_description") }}</label>
              <textarea v-model="addForm.description" rows="4" required maxlength="2000" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"></textarea>
            </div>
            <div>
              <label class="field-label">{{ $t("faults_page.field_priority") }}</label>
              <AppDropdownSelect
                v-model="addForm.priority"
                :options="priorityOptions"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
          </form>
          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="closeAddModal" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="handleCreateFault" :disabled="isCreating || !addForm.generator_id || !addForm.title || !addForm.description" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreating" /><Plus aria-hidden="true" v-else />
              {{ $t("faults_page.add_fault_button") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>