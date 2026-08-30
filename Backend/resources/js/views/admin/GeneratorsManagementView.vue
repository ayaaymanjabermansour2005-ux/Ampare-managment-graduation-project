<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { Chart as ChartJS, registerables } from "chart.js";
import { Doughnut } from "vue-chartjs";
import { useAdminGenerators } from "@/composables/useAdminGenerators";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";
import { useToastStore } from "@/stores/toast";
import { useConfirm } from "@/composables/useConfirm";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { vReveal } from "@/directives/reveal";
import AdminGeneratorsMap from "@/components/admin/AdminGeneratorsMap.vue";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import AdminGeneratorFormModal from "@/components/generators/AdminGeneratorFormModal.vue";
import GeneratorViewModal from "@/components/generators/GeneratorViewModal.vue";
import { Check, ChevronLeft, ChevronRight, Circle, CircleAlert, Eye, FileSpreadsheet, GripVertical, LoaderCircle, Pencil, PlugZap, Plus, Printer, Search, Table2, Trash2, TriangleAlert, X, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


ChartJS.register(...registerables);

const { t, locale } = useI18n();
const route = useRoute();
const toast = useToastStore();
const { confirm: confirmDialog } = useConfirm();

const {
  generators, pagination, isLoading, error,
  search, statusFilter, sortBy, areaFilter, ownerFilter, cities,
  stats, isLoadingStats,
  isSaving, saveError,
  deletingId, deleteError,
  fetchGenerators, fetchCities, onSearchInput, onFilterChange, onSortChange,
  createGenerator, updateGenerator, deleteGenerator,
  loadAll,
} = useAdminGenerators();

const ownerFilterOptions = computed(() => [
  { value: "", label: t("generators_management_page.all_owners") },
  ...[...owners.value]
    .sort((a, b) => a.name.localeCompare(b.name, locale.value === "ar" ? "ar" : "en"))
    .map((o) => ({ value: o.id, label: o.name })),
]);

const areaOptions = computed(() => [
  { value: "", label: t("generators_management_page.all_areas") },
  ...cities.value.map((c) => ({ value: c, label: c })),
]);

/* ---------------- خيارات الحالة الحقيقية (5 حالات فعلية) ---------------- */
const STATUS_META = {
  active: { chip: "chip-success", color: "#28A745" },
  maintenance: { chip: "chip-warning", color: "#FFC107" },
  inactive: { chip: "chip-danger", color: "#D9534F" },
  pending_verification: { chip: "chip-info", color: "#17A2B8" },
  rejected: { chip: "chip-danger", color: "#8A6D1F" },
};
function statusLabel(status) {
  return t(`status.${status}`, status);
}
const STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  { value: "active", label: t("status.active") },
  { value: "maintenance", label: t("status.maintenance") },
  { value: "inactive", label: t("status.inactive") },
  { value: "pending_verification", label: t("status.pending_verification") },
  { value: "rejected", label: t("status.rejected") },
]);

const systemStatusInfo = computed(() => {
  if (!stats.value) return null;
  const criticalCount = stats.value.inactive + stats.value.rejected;
  if (criticalCount === 0) {
    return {
      label: t("common.system_running"),
      color: "#28A745",
    };
  }
  return {
    label: t("generators_management_page.generators_offline", { count: criticalCount }),
    color: "#D9534F",
  };
});

/* ---------------- فرز عبر رؤوس الأعمدة (أيقونة تصاعدي/تنازلي) ---------------- */
function sortIconClass(key) {
  const [curKey, curDir] = sortBy.value.split("-");
  if (curKey !== key) return "opacity-40";
  return curDir === "desc" ? "opacity-100 text-[#8A6D1F] rotate-180" : "opacity-100 text-[#8A6D1F]";
}
function toggleSort(key) {
  const [curKey, curDir] = sortBy.value.split("-");
  const newDir = curKey === key && curDir === "desc" ? "asc" : "desc";
  sortBy.value = `${key}-${newDir}`;
  onSortChange();
}

/* ---------------- عرض جدول/شبكة ---------------- */
const viewMode = ref("table");

/* ---------------- شريط أدوات الجدول: تصدير Excel / طباعة (أيقونة الفلترة أُزيلت لأن فلاتر الحالة متوفرة عبر الـ Pills) ---------------- */
/* ---------------- أصحاب المولدات (لقائمة اختيار حقيقية بنموذج الإضافة) ---------------- */
const owners = ref([]);
async function fetchOwners() {
  const { data } = await userService.list({ role: "generator_owner", per_page: 100 });
  const list = data.data.data ?? data.data;
  owners.value = [...list].sort((a, b) => a.name.localeCompare(b.name, locale.value === "ar" ? "ar" : "en"));
}

/* ---------------- تنسيق ---------------- */
function fmtMoney(n) {
  return "₪ " + Number(n ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}
function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}

/**
 * أيقونة تحذيرية بجانب شريط الوقود (مطابقة للوحة التحكم الرئيسية):
 * تحذير حرج تحت 25%، تنبيه بسيط تحت 50%، بدون أيقونة فوق ذلك.
 */
function fuelIcon(pct) {
  if (pct === null || pct === undefined) return null;
  if (pct < 25) return "fa-triangle-exclamation";
  if (pct < 50) return "fa-gas-pump";
  return null;
}
function fuelIconTitle(pct) {
  if (pct === null || pct === undefined) return "";
  return pct < 25
    ? t("generators_management_page.low_fuel_level")
    : t("generators_management_page.medium_fuel_level");
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

/* ---------------- بطاقات KPI (حقيقية 100%) ---------------- */
const KPI_CARDS = computed(() => {
  if (!stats.value) return [];
  const s = stats.value;
  return [
    { icon: "fa-plug-circle-bolt", label: t("generators_management_page.total_generators"), value: s.total, sub: t("generators_management_page.total_capacity_suffix", { kw: s.total_capacity_kw.toLocaleString() }), c1: "#52733D", c2: "#3E582E" },
    { icon: "fa-circle-check", label: t("status.active"), value: s.active, sub: s.total > 0 ? `${Math.round((s.active / s.total) * 100)}%` : "-", c1: "#28A745", c2: "#1f7a37" },
    { icon: "fa-screwdriver-wrench", label: t("status.maintenance"), value: s.maintenance, sub: t("generators_management_page.needs_follow_up"), c1: "#FFC107", c2: "#a3760a" },
    { icon: "fa-power-off", label: t("generators_management_page.inactive_rejected"), value: s.inactive + s.rejected, sub: t("generators_management_page.high_priority"), c1: "#D9534F", c2: "#8A6D1F" },
    { icon: "fa-gas-pump", label: t("generators_management_page.avg_fuel_level"), value: s.avg_fuel_percentage !== null ? `${s.avg_fuel_percentage}%` : "-", sub: t("generators_management_page.among_tracked_units"), c1: "#17A2B8", c2: "#0f6c7d" },
    { icon: "fa-wallet", label: t("generators_management_page.total_monthly_revenue"), value: fmtMoney(s.total_monthly_revenue_ils), sub: t("generators_management_page.n_subscribers_suffix", { n: s.total_subscribers.toLocaleString() }), c1: "#8A6D1F", c2: "#D4AF37" },
  ];
});

/* ---------------- مخطط توزيع الحالات ---------------- */
const statusChartData = computed(() => {
  if (!stats.value) return { labels: [], datasets: [{ data: [] }] };
  const s = stats.value;
  return {
    labels: [t("status.active"), t("status.maintenance"), t("generators_management_page.inactive_rejected"), t("status.pending_verification")],
    datasets: [{
      data: [s.active, s.maintenance, s.inactive + s.rejected, s.pending_verification],
      backgroundColor: ["#28A745", "#FFC107", "#D9534F", "#17A2B8"],
      borderWidth: 0,
    }],
  };
});
const doughnutOptions = { responsive: true, maintainAspectRatio: false, cutout: "68%", plugins: { legend: { display: false } } };
const statusLegendItems = computed(() => {
  const chart = statusChartData.value;
  const total = chart.datasets[0].data.reduce((sum, n) => sum + n, 0) || 1;
  return chart.labels.map((label, i) => ({
    label,
    color: chart.datasets[0].backgroundColor[i],
    value: chart.datasets[0].data[i],
    pct: Math.round((chart.datasets[0].data[i] / total) * 100),
  }));
});

/* ---------------- الأكثر انخفاضا بالوقود (من الصفحة المحملة حاليا، قابلة للضغط) ---------------- */
const lowestFuelGenerators = computed(() =>
  [...generators.value]
    .filter((g) => g.fuel_percentage !== null && g.fuel_percentage !== undefined)
    .sort((a, b) => a.fuel_percentage - b.fuel_percentage)
    .slice(0, 5),
);

/* ---------------- نموذج الإضافة/التعديل (القالب/المنطق مستخرَجان بمكوّن
   مشترك AdminGeneratorFormModal.vue — مستخدَم أيضًا بصفحة أصحاب المولدات
   لإضافة مولد جديد، لتفادي تكرار نفس النموذج بأكثر من مكان) ---------------- */
const isFormOpen = ref(false);
const editingGenerator = ref(null); // null = وضع الإضافة، كائن المولد = وضع التعديل

async function openAddModal() {
  editingGenerator.value = null;
  saveError.value = null;
  if (owners.value.length === 0) await fetchOwners();
  isFormOpen.value = true;
}

function openEditModal(g) {
  editingGenerator.value = g;
  saveError.value = null;
  isFormOpen.value = true;
}

function closeForm() {
  if (isSaving.value) return;
  isFormOpen.value = false;
  saveError.value = null;
}

async function handleGeneratorFormSubmit(payload) {
  const name = payload.name;
  let ok;
  if (editingGenerator.value) {
    ok = await updateGenerator(editingGenerator.value.id, payload);
    if (ok) {
      toast.show({
        type: "success",
        title: t("users_page.saved_toast_title"),
        message: t("subscribers_page.subscriber_updated_message", { name }),
      });
    }
  } else {
    ok = await createGenerator(payload);
    if (ok) {
      toast.show({
        type: "success",
        title: t("users_page.created_toast_title"),
        message: t("owners_page.generator_created_msg", { name }),
      });
    }
  }
  if (ok) isFormOpen.value = false;
}


/* ---------------- توثيق/رفض مولد بانتظار الاعتماد ---------------- */
const isVerifying = ref(false);
const isRejectModalOpen = ref(false);
const rejectingGenerator = ref(null);
const rejectReason = ref("");
const isRejecting = ref(false);
const rejectFormError = ref(null);

async function handleVerify(g) {
  const confirmed = await confirmDialog({
    title: t("generators_management_page.approve_generator_title"),
    message: t("generators_management_page.approve_generator_message", { name: g.name }),
    confirmLabel: t("generators_management_page.approve_action"),
    variant: "default",
  });
  if (!confirmed) return;

  isVerifying.value = true;
  try {
    await generatorService.verify(g.id);
    await fetchGenerators(pagination.value.current_page);
    toast.show({
      type: "success",
      title: t("generators_management_page.approved_toast_title"),
      message: t("generators_management_page.generator_approved_message", { name: g.name }),
    });
  } catch (err) {
    toast.show({
      type: "danger",
      title: t("generators_management_page.approval_failed_title"),
      message: normalizeApiError(err, t("generators_management_page.try_again")).message,
    });
  } finally {
    isVerifying.value = false;
  }
}

function openRejectModal(g) {
  rejectingGenerator.value = g;
  rejectReason.value = "";
  rejectFormError.value = null;
  isRejectModalOpen.value = true;
}
function closeRejectModal() {
  if (isRejecting.value) return;
  isRejectModalOpen.value = false;
  rejectingGenerator.value = null;
}
async function handleReject() {
  if (!rejectingGenerator.value) return;
  isRejecting.value = true;
  rejectFormError.value = null;
  try {
    await generatorService.reject(rejectingGenerator.value.id, rejectReason.value);
    await fetchGenerators(pagination.value.current_page);
    toast.show({
      type: "success",
      title: t("generators_management_page.rejected_toast_title"),
      message: t("generators_management_page.generator_rejected_message", { name: rejectingGenerator.value.name }),
    });
    isRejectModalOpen.value = false;
    rejectingGenerator.value = null;
  } catch (err) {
    const normalized = normalizeApiError(err, t("generators_management_page.rejection_failed"));
    rejectFormError.value = normalized.fieldError("reason") ?? normalized.message;
  } finally {
    isRejecting.value = false;
  }
}

/* ---------------- نافذة العرض (مكوّن مشترك GeneratorViewModal.vue يتولّى
   جلب سجل الصيانة/المرفقات/الفنيين المرتبطين داخليًا عند فتحه) ---------------- */
const isViewOpen = ref(false);
const viewingGenerator = ref(null);
function openViewModal(g) {
  viewingGenerator.value = g;
  isViewOpen.value = true;
}
function switchToEditFromView() {
  isViewOpen.value = false;
  openEditModal(viewingGenerator.value);
}

/* ---------------- حذف مولد (نافذة تأكيد بنفس هوية الموقع بدل confirm() العام) ---------------- */
const isDeleteModalOpen = ref(false);
const deletingGenerator = ref(null);

function openDeleteModal(g) {
  deletingGenerator.value = g;
  deleteError.value = null;
  isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
  if (deletingId.value) return;
  isDeleteModalOpen.value = false;
  deletingGenerator.value = null;
}

async function confirmDeleteGenerator() {
  if (!deletingGenerator.value) return;
  const name = deletingGenerator.value.name;
  const ok = await deleteGenerator(deletingGenerator.value.id);
  if (ok) {
    isDeleteModalOpen.value = false;
    deletingGenerator.value = null;
    toast.show({
      type: "success",
      title: t("users_page.deleted_toast_title"),
      message: t("generators_management_page.generator_deleted_message", { name }),
    });
  }
}

/* ---------------- تصدير/طباعة ---------------- */
const exportUrl = computed(() =>
  generatorService.exportUrl({ search: search.value || undefined, status: statusFilter.value || undefined, city: areaFilter.value || undefined }),
);
function printPage() {
  window.print();
}

onMounted(async () => {
  await Promise.all([loadAll(), fetchCities(), fetchOwners()]);

  // دعم الوصول من رابط "عرض المولد" بخريطة الداشبورد الرئيسية (?highlight=ID)
  // — يفتح نافذة تفاصيل المولد المطلوب تلقائيًا بدل ما يوصل الأدمن لقائمة
  // عامة ويضطر يدوّر يدويًا عن نفس المولد يلي ضغط عليه بالخريطة.
  const highlightId = route.query.highlight ? Number(route.query.highlight) : null;
  if (highlightId) {
    const match = generators.value.find((g) => g.id === highlightId);
    if (match) openViewModal(match);
  }
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
<ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
<ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("generators_management_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <PlugZap aria-hidden="true" />
            </span>
            {{ $t("generators_management_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("generators_management_page.subtitle") }}
          </p>
          <span
            v-if="systemStatusInfo"
            class="inline-flex items-center gap-2 text-[11px] font-bold mt-2.5"
            :style="{ color: systemStatusInfo.color }"
          >
            <Circle class="text-[7px]" aria-hidden="true" /> {{ systemStatusInfo.label }}
          </span>
        </div>
        <div class="flex flex-wrap gap-2.5">
          <a :href="exportUrl" target="_blank" rel="noopener" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
            <FileSpreadsheet aria-hidden="true" /> {{ $t("owner_applications_page.export_excel_title") }}
          </a>
          <button type="button" @click="printPage" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
            <Printer aria-hidden="true" /> {{ $t("owner_applications_page.print") }}
          </button>
          <button type="button" @click="openAddModal" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2">
            <Plus aria-hidden="true" /> {{ $t("owner_dashboard.add_generator") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div v-if="isLoadingStats" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3.5">
        <div v-for="i in 6" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3.5">
        <div v-for="c in KPI_CARDS" :key="c.label" class="kpi-card glass-card hoverable" :style="{ '--kpi-color': c.c1, '--kpi-color2': c.c2 }">
          <div class="kpi-icon mb-2.5"><AppIcon :name="c.icon" /></div>
          <div class="text-lg font-extrabold">{{ c.value }}</div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.label }}</div>
          <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1.5">{{ c.sub }}</div>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
          <div class="relative flex-1 min-w-[160px] max-w-xs">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="search" type="text" @input="onSearchInput"
              :placeholder="$t('generators_management_page.search_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>
          <AppDropdownSelect
            :model-value="areaFilter"
            @update:model-value="(val) => { areaFilter = val; onFilterChange(); }"
            :options="areaOptions"
            width-class="w-36"
            :panel-width="144"
          />
          <AppDropdownSelect
            :model-value="ownerFilter"
            @update:model-value="(val) => { ownerFilter = val; onFilterChange(); }"
            :options="ownerFilterOptions"
            width-class="w-44"
            :panel-width="176"
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
            :title="$t('owner_applications_page.export_excel_title')"
            :aria-label="$t('owner_applications_page.export_excel_title')"
          >
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </a>
          <button
            type="button"
            @click="printPage"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="$t('owner_applications_page.print')"
            :aria-label="$t('owner_applications_page.print')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE (عرض كامل) ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-12 rounded-lg thumb-loading"></div>
      </div>
      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>
      <div v-else-if="generators.length === 0" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("generators_management_page.no_matching_generators") }}</p>
      </div>

      <template v-else>
      <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[900px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg cursor-pointer select-none" @click="toggleSort('name')">
                {{ $t("dashboard.generator_col") }}
                <AppIcon :name="sortIconClass('name')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.city_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("dashboard.capacity_col") }}</th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('fuel')">
                {{ $t("dashboard.fuel_label") }}
                <AppIcon :name="sortIconClass('fuel')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('subs')">
                {{ $t("dashboard.subscribers_col") }}
                <AppIcon :name="sortIconClass('subs')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('rev')">
                {{ $t("generators_management_page.monthly_revenue_col") }}
                <AppIcon :name="sortIconClass('rev')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.status_col") }}</th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ $t("subscribers_page.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="g in generators" :key="g.id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center">
              <td class="py-2.5 px-3">
                <div class="text-center">
                  <div class="font-bold truncate max-w-[9rem] mx-auto">{{ g.name }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ g.code }}</div>
                </div>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ g.location?.city ?? "-" }}</td>
              <td class="py-2.5 px-3">{{ g.capacity_kw ?? "-" }} {{ $t("dashboard.kw_label") }}</td>
              <!-- ===== عمود الوقود: شريط + نسبة + أيقونة تحذيرية (مطابق للوحة التحكم الرئيسية) ===== -->
              <td class="py-2.5 px-3 w-28">
                <div v-if="g.fuel_percentage !== null" class="flex items-center justify-center gap-2">
                  <AppIcon :name="fuelIcon(g.fuel_percentage)" class="text-[10px]" v-if="fuelIcon(g.fuel_percentage)"
                    
                    
                    :style="{ color: fuelColor(g.fuel_percentage) }"
                    :title="fuelIconTitle(g.fuel_percentage)" />
                  <div class="bar-track w-14"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
                  <span class="text-[10.5px] font-bold w-8" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
                </div>
                <span v-else class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">-</span>
              </td>
              <td class="py-2.5 px-3">{{ g.active_subscriptions_count ?? 0 }}</td>
              <td class="py-2.5 px-3 font-bold">{{ fmtMoney(g.monthly_revenue_ils) }}</td>
              <td class="py-2.5 px-3"><span class="status-chip" :class="STATUS_META[g.status]?.chip ?? 'chip-info'">{{ statusLabel(g.status) }}</span></td>
              <td class="py-2.5 px-3">
                <div class="row-actions">
                  <template v-if="g.status === 'pending_verification'">
                    <button type="button" @click="handleVerify(g)" :disabled="isVerifying" class="action-btn action-btn--view !text-[#28A745]" :title="$t('generators_management_page.approve_action')" :aria-label="$t('generators_management_page.approve_action')"><Check aria-hidden="true" /></button>
                    <button type="button" @click="openRejectModal(g)" class="action-btn action-btn--delete" :title="$t('owner_applications_page.reject_action')" :aria-label="$t('owner_applications_page.reject_action')"><X aria-hidden="true" /></button>
                    <span class="row-actions-divider"></span>
                  </template>
                  <button type="button" @click="openViewModal(g)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')"><Eye aria-hidden="true" /></button>
                  <button type="button" @click="openEditModal(g)" class="action-btn action-btn--edit" :title="$t('common.edit')" :aria-label="$t('common.edit')"><Pencil aria-hidden="true" /></button>
                  <span class="row-actions-divider"></span>
                  <button type="button" @click="openDeleteModal(g)" :disabled="deletingId === g.id" class="action-btn action-btn--delete" :title="$t('common.delete')" :aria-label="$t('common.delete')">
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === g.id" /><Trash2 aria-hidden="true" v-else />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="g in generators" :key="g.id" class="glass-card p-3.5">
          <div class="flex items-start justify-between mb-2.5">
            <div class="min-w-0">
              <div class="font-bold text-[12.5px] truncate">{{ g.name }}</div>
              <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ g.code }} · {{ g.location?.city ?? "-" }}</div>
            </div>
            <span class="status-chip shrink-0" :class="STATUS_META[g.status]?.chip ?? 'chip-info'">{{ statusLabel(g.status) }}</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-[11px] mb-2.5">
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.capacity_col") }}:</span> <b>{{ g.capacity_kw ?? "-" }} {{ $t("dashboard.kw_label") }}</b></div>
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.subscribers_col") }}:</span> <b>{{ g.active_subscriptions_count ?? 0 }}</b></div>
          </div>
          <div v-if="g.fuel_percentage !== null" class="flex items-center gap-2 mb-3">
            <AppIcon :name="fuelIcon(g.fuel_percentage)" class="text-[10px]" v-if="fuelIcon(g.fuel_percentage)"
              
              
              :style="{ color: fuelColor(g.fuel_percentage) }"
              :title="fuelIconTitle(g.fuel_percentage)" />
            <div class="bar-track flex-1"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
            <span class="text-[10.5px] font-bold" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-extrabold text-[12.5px]">{{ fmtMoney(g.monthly_revenue_ils) }}</span>
            <div class="row-actions">
              <template v-if="g.status === 'pending_verification'">
                <button type="button" @click="handleVerify(g)" :disabled="isVerifying" class="action-btn action-btn--view !text-[#28A745]" :title="$t('generators_management_page.approve_action')" :aria-label="$t('generators_management_page.approve_action')"><Check aria-hidden="true" /></button>
                <button type="button" @click="openRejectModal(g)" class="action-btn action-btn--delete" :title="$t('owner_applications_page.reject_action')" :aria-label="$t('owner_applications_page.reject_action')"><X aria-hidden="true" /></button>
                <span class="row-actions-divider"></span>
              </template>
              <button type="button" @click="openViewModal(g)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')"><Eye aria-hidden="true" /></button>
              <button type="button" @click="openEditModal(g)" class="action-btn action-btn--edit" :title="$t('common.edit')" :aria-label="$t('common.edit')"><Pencil aria-hidden="true" /></button>
              <span class="row-actions-divider"></span>
              <button type="button" @click="openDeleteModal(g)" :disabled="deletingId === g.id" class="action-btn action-btn--delete" :title="$t('common.delete')" :aria-label="$t('common.delete')">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === g.id" /><Trash2 aria-hidden="true" v-else />
              </button>
            </div>
          </div>
        </div>
      </div>
      </template>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
        <span>{{ $t("generators_management_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchGenerators(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
<ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
<ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />          
          </button>
          <button :aria-label="$t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchGenerators(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
<ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
<ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== MAP + STATUS CHART + LOWEST FUEL (جمب بعض بصف واحد، بنفس الطول) ===== -->
    <section class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 items-stretch">
      <div v-reveal class="glass-card p-4 flex flex-col">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("generators_management_page.generators_map_title") }}</h3>
          <span v-if="stats" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ stats.total }} {{ $t("generators_management_page.sites_label") }}</span>
        </div>
        <AdminGeneratorsMap />
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("generators_management_page.status_breakdown_title") }}</h3>
        <div v-if="isLoadingStats" class="flex-1 min-h-[10rem] thumb-loading rounded-lg"></div>
        <template v-else>
          <div class="flex-1 min-h-[10rem]"><Doughnut :data="statusChartData" :options="doughnutOptions" /></div>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10">
            <span v-for="item in statusLegendItems" :key="item.label" class="flex items-center gap-1.5 text-[10.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5]">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: item.color }"></span>
              {{ item.label }} <b class="text-[#3a3a38] dark:text-[#eef0ec]">{{ item.value }}</b>
              <span class="text-[#9a9d97] dark:text-[#8f938a]">({{ item.pct }}%)</span>
            </span>
          </div>
        </template>
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("generators_management_page.lowest_fuel_title") }}</h3>
        <div v-if="lowestFuelGenerators.length === 0" class="flex-1 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("generators_management_page.no_fuel_data") }}</div>
        <div v-else class="flex-1 space-y-2.5">
          <button
            v-for="g in lowestFuelGenerators" :key="g.id" type="button"
            class="flex items-center gap-2.5 w-full text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 rounded-lg p-1 -m-1 transition-colors"
            @click="openViewModal(g)"
          >
            <span class="text-[11px] font-bold truncate flex-1">{{ g.name }}</span>
            <div class="bar-track w-16"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
            <span class="text-[10.5px] font-bold w-9 text-end" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ===================== ADD / EDIT MODAL (بنفس هوية لوحة التحكم الرئيسية) — مكوّن مشترك ===================== -->
    <AdminGeneratorFormModal
      :open="isFormOpen"
      :generator="editingGenerator"
      :owners="owners"
      :is-saving="isSaving"
      :save-error="saveError"
      @close="closeForm"
      @submit="handleGeneratorFormSubmit"
    />

    <!-- ===================== VIEW MODAL (بنفس هوية لوحة التحكم الرئيسية) — مكوّن مشترك ===================== -->
    <GeneratorViewModal
      :open="isViewOpen"
      :generator="viewingGenerator"
      @close="isViewOpen = false"
      @edit="switchToEditFromView"
    />

    <!-- ===================== DELETE CONFIRM MODAL (بنفس هوية الموقع) ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isDeleteModalOpen && deletingGenerator" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeDeleteModal">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
          <!-- Header -->
          <div class="modal-head-brand modal-head-brand--danger">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Trash2 aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("dashboard.delete_generator_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ $t("dashboard.delete_generator_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="closeDeleteModal" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <!-- Body -->
          <div class="p-5 space-y-3.5 text-center">
            <div class="w-14 h-14 rounded-full bg-[#D9534F]/10 flex items-center justify-center mx-auto">
              <TriangleAlert class="text-[#D9534F] text-xl" aria-hidden="true" />
            </div>
            <p class="text-[12.5px] leading-relaxed text-[#4b4b4b] dark:text-[#d7d9d3]">
              {{ $t("generators_management_page.delete_generator_message", { name: deletingGenerator.name }) }}
            </p>
            <div v-if="deleteError" class="alert-box text-start">
              <CircleAlert class="shrink-0" aria-hidden="true" />
              <div>
                <p>{{ deleteError }}</p>
                <RouterLink :to="{ name: 'admin.subscriptions' }" class="text-[11px] font-bold underline mt-1 inline-block">
                  {{ $t("generators_management_page.view_subscriptions_link") }}
                </RouterLink>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer-brand">
            <button type="button" @click="closeDeleteModal" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="confirmDeleteGenerator" :disabled="deletingId === deletingGenerator.id" class="btn-fill-brand btn-fill-brand--danger">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === deletingGenerator.id" /><Trash2 aria-hidden="true" v-else />
              {{ $t("dashboard.confirm_delete") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===================== REJECT VERIFICATION MODAL ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isRejectModalOpen && rejectingGenerator" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeRejectModal">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
          <!-- Header -->
          <div class="modal-head-brand modal-head-brand--danger">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><X aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("generators_management_page.reject_generator_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ rejectingGenerator.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="closeRejectModal" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <!-- Body -->
          <div class="p-5 space-y-3">
            <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5]">
              {{ $t("generators_management_page.reject_notice_desc") }}
            </p>
            <div>
              <label class="field-label">{{ $t("generators_management_page.rejection_reason_field_label") }}</label>
              <textarea v-model="rejectReason" rows="4" maxlength="1000" class="field-input resize-none" :placeholder="$t('generators_management_page.rejection_reason_placeholder')"></textarea>
            </div>
            <div v-if="rejectFormError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ rejectFormError }}
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer-brand">
            <button type="button" @click="closeRejectModal" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="handleReject" :disabled="isRejecting || !rejectReason.trim()" class="btn-fill-brand btn-fill-brand--danger">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isRejecting" /><X aria-hidden="true" v-else />
              {{ isRejecting ? $t("generators_management_page.rejecting_ellipsis") : $t("generators_management_page.confirm_rejection_button") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>
  </div>
</template>