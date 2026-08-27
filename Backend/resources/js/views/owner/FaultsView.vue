<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useOwnerFaults } from "@/composables/useOwnerFaults";
import { useConfirm } from "@/composables/useConfirm";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import technicianService from "@/services/technicianService";
import { Check, ChevronLeft, ChevronRight, CircleCheck, Eye, LoaderCircle, Search, ShieldCheck, TriangleAlert, User, Wrench, X, Zap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();
const { confirm } = useConfirm();

const {
  faults, pagination, isLoading, error,
  search, statusFilter,
  isVerifying, verifyError,
  isDecidingRepair, decideRepairError,
  fetchFaults, onSearchInput, onFilterChange,
  verifyFault, decideFaultRepair,
} = useOwnerFaults();

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
  return m ? t(m.key) : s;
}
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

const countOnPage = (status) => faults.value.filter((f) => f.status === status).length;
const KPI_CARDS = computed(() => [
  { icon: "fa-triangle-exclamation", label: t("faults_page.total_faults"), value: pagination.value.total, c1: "#D9534F", c2: "#8A6D1F" },
  { icon: "fa-hourglass-half", label: statusLabel("pending_verification") + t("common.this_page_suffix"), value: countOnPage("pending_verification"), c1: "#17A2B8", c2: "#0f6c7d" },
  { icon: "fa-screwdriver-wrench", label: statusLabel("in_repair") + t("common.this_page_suffix"), value: countOnPage("in_repair"), c1: "#FFC107", c2: "#a3760a" },
  { icon: "fa-circle-check", label: statusLabel("resolved") + t("common.this_page_suffix"), value: countOnPage("resolved"), c1: "#28A745", c2: "#1f7a37" },
]);

const paginationRange = computed(() => {
  const total = pagination.value.last_page;
  const current = pagination.value.current_page;
  const delta = 1;
  const range = [];
  const withDots = [];
  let last = null;
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) range.push(i);
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

/* ---------------- نافذة التفاصيل + التحقق + قرار الإصلاح ---------------- */
const isDetailOpen = ref(false);
const activeFault = ref(null);

const REPAIR_METHOD_OPTIONS = computed(() => [
  { value: "owner_fixed", label: t("owner_faults.repair_owner_fixed") },
  { value: "internal_technician", label: t("owner_faults.repair_internal_technician") },
]);
const repairMethod = ref("owner_fixed");
const repairTechnicianId = ref("");
const repairInstructions = ref("");

const technicianOptions = ref([]);
const isLoadingTechnicians = ref(false);
async function ensureTechnicianOptionsLoaded() {
  if (technicianOptions.value.length > 0) return;
  isLoadingTechnicians.value = true;
  try {
    const { data } = await technicianService.list({ per_page: 200 });
    const payload = data.data;
    technicianOptions.value = payload.data ?? payload;
  } finally {
    isLoadingTechnicians.value = false;
  }
}
const technicianSelectOptions = computed(() => technicianOptions.value.map((tech) => ({ value: tech.id, label: tech.name })));

function openDetail(f) {
  activeFault.value = f;
  repairMethod.value = "owner_fixed";
  repairTechnicianId.value = "";
  repairInstructions.value = "";
  isDetailOpen.value = true;
  if (f.status === "verified") ensureTechnicianOptionsLoaded();
}

async function handleVerify(isValid) {
  if (isValid) {
    const confirmed = await confirm({
      title: t("owner_faults.verify_title"),
      message: t("owner_faults.confirm_valid_message"),
      confirmLabel: t("owner_faults.valid_fault_button"),
    });
    if (!confirmed) return;
  } else {
    const confirmed = await confirm({
      title: t("owner_faults.verify_title"),
      message: t("owner_faults.confirm_invalid_message"),
      confirmLabel: t("owner_faults.invalid_fault_button"),
      variant: "danger",
    });
    if (!confirmed) return;
  }
  const updated = await verifyFault(activeFault.value.id, isValid);
  if (updated) activeFault.value = updated;
}

async function handleDecideRepairSubmit() {
  const updated = await decideFaultRepair(activeFault.value.id, {
    repair_method: repairMethod.value,
    technician_id: repairMethod.value === "internal_technician" ? (repairTechnicianId.value || null) : null,
    instructions: repairInstructions.value.trim() || undefined,
  });
  if (updated) {
    activeFault.value = updated;
    isDetailOpen.value = false;
  }
}

onMounted(() => fetchFaults(1));
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
      <div class="relative">
        <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#D9534F] to-[#8A6D1F] text-white flex items-center justify-center text-base">
            <TriangleAlert aria-hidden="true" />
          </span>
          {{ $t("faults_page.title") }}
        </h1>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
          {{ $t("owner_faults.subtitle") }}
        </p>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <div v-for="c in KPI_CARDS" :key="c.label" class="kpi-card glass-card hoverable" :style="{ '--kpi-color': c.c1, '--kpi-color2': c.c2 }">
          <div class="kpi-icon mb-2.5"><AppIcon :name="c.icon" /></div>
          <div class="text-lg font-extrabold">{{ c.value }}</div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.label }}</div>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
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
        <div v-for="f in faults" :key="f.id" class="flex items-center gap-3.5 py-3.5 px-1.5">
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
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("faults_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchFaults(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
          <template v-for="(page, i) in paginationRange" :key="i">
            <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
            <button
              v-else type="button" @click="fetchFaults(page)"
              class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
              :class="page === pagination.current_page ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'"
            >{{ page }}</button>
          </template>
          <button :aria-label="$t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchFaults(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== نافذة التفاصيل + التحقق + قرار الإصلاح ===== -->
    <Teleport to="body">
      <div v-if="isDetailOpen && activeFault" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="isDetailOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[85vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--danger">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Zap aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ activeFault.title }}</h3>
                <p class="modal-head-brand__subtitle">{{ activeFault.generator_name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isDetailOpen = false" class="modal-head-brand__close">
              <X aria-hidden="true" />
            </button>
          </div>

          <div class="p-5 space-y-4 overflow-y-auto">
            <div class="flex flex-wrap items-center gap-2 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
              <span><User aria-hidden="true" /> {{ activeFault.reported_by_name }}</span>
              <span class="status-chip" :class="PRIORITY_META[activeFault.priority]?.chip">{{ priorityLabel(activeFault.priority) }}</span>
              <span class="status-chip" :class="STATUS_META[activeFault.status]?.chip">{{ statusLabel(activeFault.status) }}</span>
            </div>
            <p class="text-[12.5px] leading-relaxed glass-card p-3.5">{{ activeFault.description }}</p>

            <!-- ===== التحقق من صحة البلاغ (pending_verification فقط) ===== -->
            <div v-if="activeFault.status === 'pending_verification'" class="border-t border-[#eee8da] dark:border-white/10 pt-4">
              <p class="text-[12px] font-extrabold mb-3 flex items-center gap-1.5"><ShieldCheck class="text-[#8A6D1F]" aria-hidden="true" /> {{ $t("owner_faults.verify_title") }}</p>
              <div v-if="verifyError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-3">{{ verifyError }}</div>
              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-3">{{ $t("owner_faults.verify_question") }}</p>
              <div class="flex gap-2.5">
                <button type="button" @click="handleVerify(false)" :disabled="isVerifying" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#D9534F]/40 text-[#D9534F] hover:bg-[#D9534F]/10 transition disabled:opacity-60">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isVerifying" /><X aria-hidden="true" v-else />
                  {{ $t("owner_faults.invalid_fault_button") }}
                </button>
                <button type="button" @click="handleVerify(true)" :disabled="isVerifying" class="flex-1 btn-fill-brand !justify-center disabled:opacity-60">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isVerifying" /><Check aria-hidden="true" v-else />
                  {{ $t("owner_faults.valid_fault_button") }}
                </button>
              </div>
            </div>

            <!-- ===== قرار الإصلاح (verified فقط) ===== -->
            <div v-else-if="activeFault.status === 'verified'" class="border-t border-[#eee8da] dark:border-white/10 pt-4">
              <p class="text-[12px] font-extrabold mb-3 flex items-center gap-1.5"><Wrench class="text-[#8A6D1F]" aria-hidden="true" /> {{ $t("owner_faults.decide_repair_title") }}</p>
              <div v-if="decideRepairError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-3">{{ decideRepairError }}</div>
              <div class="space-y-3">
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("owner_faults.repair_method_label") }}</label>
                  <AppDropdownSelect
                    v-model="repairMethod" @update:model-value="ensureTechnicianOptionsLoaded"
                    :options="REPAIR_METHOD_OPTIONS" variant="field" width-class="w-full" match-trigger-width
                  />
                </div>
                <div v-if="repairMethod === 'internal_technician'">
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("owner_faults.technician_label") }}</label>
                  <AppDropdownSelect
                    v-model="repairTechnicianId"
                    :options="technicianSelectOptions"
                    :disabled="isLoadingTechnicians"
                    :placeholder="isLoadingTechnicians ? $t('common.loading') : $t('owner_faults.choose_technician')"
                    variant="field" width-class="w-full" match-trigger-width
                  />
                </div>
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("owner_faults.instructions_label") }} <span class="text-[#9a9d97] font-normal">({{ $t("owner_technicians.optional") }})</span></label>
                  <textarea v-model="repairInstructions" rows="3" maxlength="2000" :placeholder="$t('owner_faults.instructions_placeholder')" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"></textarea>
                </div>
              </div>
            </div>

            <div v-else class="glass-card p-3.5 text-center">
              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ $t("owner_faults.no_action_needed") }}</p>
            </div>
          </div>

          <div v-if="activeFault.status === 'verified'" class="modal-footer-brand">
            <button type="button" @click="isDetailOpen = false" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button
              type="button" @click="handleDecideRepairSubmit"
              :disabled="isDecidingRepair || (repairMethod === 'internal_technician' && !repairTechnicianId)"
              class="btn-fill-brand"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isDecidingRepair" /><Check aria-hidden="true" v-else />
              {{ $t("owner_faults.submit_decision") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
