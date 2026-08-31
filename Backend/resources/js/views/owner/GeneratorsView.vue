<script setup>
import { ref, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Chart as ChartJS, registerables } from "chart.js";
import { Doughnut } from "vue-chartjs";
import { useGeneratorsTable } from "@/composables/useGeneratorsTable";
import { useMaintenance } from "@/composables/useMaintenance";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import generatorService from "@/services/generatorService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import GeneratorCard from "@/components/generators/GeneratorCard.vue";
import GeneratorForm from "@/components/generators/GeneratorForm.vue";
import GeneratorViewModal from "@/components/generators/GeneratorViewModal.vue";
import { useLeafletMap } from "@/composables/useLeafletMap";
import { Activity, ChevronLeft, ChevronRight, CircleAlert, Eye, FileSpreadsheet, GripVertical, LoaderCircle, Pencil, PlugZap, Plus, Printer, QrCode, Save, Search, Sparkles, Table2, Trash2, TriangleAlert, X, Zap, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


ChartJS.register(...registerables);

const {
  generators,
  pagination,
  isLoading,
  error,
  search,
  statusFilter,
  sortBy,
  areaFilter,
  cities,
  isSaving,
  saveError,
  deletingId,
  deleteError,
  fetchCities,
  fetchGenerators,
  onSearchInput,
  onFilterChange,
  onSortChange,
  createGenerator,
  updateGenerator,
  deleteGenerator,
} = useGeneratorsTable({ perPage: 12 });

const toast = useToastStore();
const { t, locale } = useI18n();

/* ---------------- بطاقات KPI (نفس شكل صفحة إدارة المولدات عند الأدمن) ---------------- */
const stats = ref(null);
const isLoadingStats = ref(true);

async function fetchStats() {
  isLoadingStats.value = true;
  try {
    const { data } = await generatorService.stats();
    stats.value = data.data;
  } finally {
    isLoadingStats.value = false;
  }
}

function fmtMoney(n) {
  return "₪ " + Number(n ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}

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

/* ---------------- توزيع الحالة (من stats الحقيقية) ---------------- */
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

/* ---------------- أضعف 5 مولدات وقودًا (من الصفحة المحمّلة حالياً) ---------------- */
const lowestFuelGenerators = computed(() =>
  [...generators.value]
    .filter((g) => g.fuel_percentage !== null && g.fuel_percentage !== undefined)
    .sort((a, b) => a.fuel_percentage - b.fuel_percentage)
    .slice(0, 5),
);

/* ---------------- خريطة مصغّرة لمولدات المالك ---------------- */
const { containerRef: mapContainerRef, init: initMap, setMarkers, fitToBounds } = useLeafletMap({ zoom: 10 });
const mapPoints = ref([]);
const isLoadingMapPoints = ref(true);

async function fetchMapPoints() {
  isLoadingMapPoints.value = true;
  try {
    const { data } = await generatorService.mapPoints();
    mapPoints.value = data.data;
  } catch {
    mapPoints.value = [];
  } finally {
    isLoadingMapPoints.value = false;
  }
}

function fillMap() {
  const map = initMap();
  if (!map) return;
  const bounds = setMarkers(mapPoints.value, {
    getId: (item) => item.id,
    getLatLng: (item) => [item.lat, item.lng],
    popup: (item) => `<b>${item.name}</b>`,
  });
  fitToBounds(bounds);
}

/* ---------------- حالة/وقود: نفس دوال صفحة إدارة المولدات عند الأدمن ---------------- */
const STATUS_META = {
  active: { chip: "chip-success" },
  maintenance: { chip: "chip-warning" },
  inactive: { chip: "chip-danger" },
  pending_verification: { chip: "chip-info" },
  rejected: { chip: "chip-danger" },
};
function statusLabel(status) {
  return t(`status.${status}`, status);
}
const STATUS_PILLS = computed(() => [
  { v: "", l: t("owner_generators.filter_all") },
  { v: "active", l: t("owner_generators.filter_active") },
  { v: "maintenance", l: t("owner_generators.filter_maintenance") },
  { v: "inactive", l: t("owner_generators.filter_inactive") },
  { v: "pending_verification", l: t("status.pending_verification") },
  { v: "rejected", l: t("status.rejected") },
]);
function selectStatusFilter(value) {
  statusFilter.value = value;
  onFilterChange();
}

const areaOptions = computed(() => [
  { value: "", label: t("generators_management_page.all_areas") },
  ...cities.value.map((c) => ({ value: c, label: c })),
]);

function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}
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

/* ---------------- تصدير/طباعة ---------------- */
const exportUrl = computed(() =>
  generatorService.exportUrl({ search: search.value || undefined, status: statusFilter.value || undefined, city: areaFilter.value || undefined }),
);
function printPage() {
  window.print();
}

/* ---------------- نموذج الإضافة/التعديل (نفس فورم المالك الحالي — GeneratorForm.vue) ---------------- */
const isFormOpen = ref(false);
const editingGenerator = ref(null);

function openCreateForm() {
  editingGenerator.value = null;
  saveError.value = null;
  isFormOpen.value = true;
}
function openEditForm(generator) {
  editingGenerator.value = generator;
  saveError.value = null;
  isFormOpen.value = true;
}

async function handleFormSubmit(payload) {
  const name = payload.name;
  const isEditing = !!editingGenerator.value;
  const ok = isEditing
    ? await updateGenerator(editingGenerator.value.id, payload)
    : await createGenerator(payload);

  if (ok) {
    isFormOpen.value = false;
    await fetchStats();
    toast.show({
      type: "success",
      title: isEditing ? t("owner_generators.updated_toast_title") : t("owner_generators.created_toast_title"),
      message: isEditing
        ? t("owner_generators.updated_toast_message", { name })
        : t("owner_generators.created_toast_message", { name }),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_generators.save_failed_title"),
      message: saveError.value?.message ?? t("owner_generators.save_failed_message"),
    });
  }
}

/* ---------------- نافذة تأكيد الحذف (بنفس هوية صفحة إدارة المولدات عند الأدمن) ---------------- */
const isDeleteModalOpen = ref(false);
const deletingGenerator = ref(null);

function openDeleteModal(generator) {
  deletingGenerator.value = generator;
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
    await fetchStats();
    toast.show({
      type: "success",
      title: t("owner_generators.deleted_toast_title"),
      message: t("owner_generators.deleted_toast_message", { name }),
    });
  }
}

/* ---------------- نافذة QR (بدون تغيير) ---------------- */
const qrModal = ref({ open: false, loading: false, src: null, generatorName: "" });
async function handleShowQr(generator) {
  qrModal.value = { open: true, loading: true, src: null, generatorName: generator.name };
  try {
    const { data } = await generatorService.qrCode(generator.id);
    qrModal.value.src = data.data.qr;
  } finally {
    qrModal.value.loading = false;
  }
}

const isViewOpen = ref(false);
const viewingGenerator = ref(null);
function openViewModal(generator) {
  viewingGenerator.value = generator;
  isViewOpen.value = true;
}
function switchToEditFromView() {
  isViewOpen.value = false;
  openEditForm(viewingGenerator.value);
}

const {
  selectedGeneratorId: diagnosticGeneratorId,
  submitDiagnostic,
  isSubmittingDiagnostic,
  diagnosticError,
  isAnalyzing,
  analyzeError,
  analyzeReading,
} = useMaintenance();

const isDiagnosticModalOpen = ref(false);
const diagnosticTargetGenerator = ref(null);
const diagnosticSavedReading = ref(null);

function emptyDiagnosticForm() {
  return {
    oil_temperature: null,
    coolant_temperature: null,
    vibration_level: null,
    oil_pressure: null,
    battery_voltage: null,
    run_hours: null,
    noise_level: null,
    notes: "",
  };
}
const diagnosticForm = ref(emptyDiagnosticForm());

function openDiagnosticModal(generator) {
  diagnosticTargetGenerator.value = generator;
  diagnosticGeneratorId.value = generator.id;
  diagnosticForm.value = emptyDiagnosticForm();
  diagnosticError.value = null;
  diagnosticSavedReading.value = null;
  analyzeError.value = null;
  isDiagnosticModalOpen.value = true;
}

async function submitDiagnosticForm() {
  // نستبعد الحقول الفارغة (null/"") لأنه مو إلزامي تعبئة كل قراءة — الفني/المالك
  // بيعبّي بس القراءات المتوفرة عنده وقت الفحص الميداني.
  const payload = Object.fromEntries(
    Object.entries(diagnosticForm.value).filter(([, v]) => v !== null && v !== ""),
  );

  const reading = await submitDiagnostic(payload);
  if (reading) {
    diagnosticSavedReading.value = reading;
    toast.show({
      type: "success",
      title: t("generator_diagnostics.saved_toast_title"),
      message: t("generator_diagnostics.saved_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("generator_diagnostics.save_failed_title"),
      message: diagnosticError.value?.message ?? t("generator_diagnostics.save_failed_message"),
    });
  }
}

async function runAnalyzeReading() {
  if (!diagnosticSavedReading.value) return;
  const ok = await analyzeReading(diagnosticSavedReading.value.id);
  if (ok) {
    isDiagnosticModalOpen.value = false;
    toast.show({
      type: "success",
      title: t("generator_diagnostics.analyze_success_title"),
      message: t("generator_diagnostics.analyze_success_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("generator_diagnostics.analyze_failed_title"),
      message: analyzeError.value?.message ?? t("generator_diagnostics.analyze_failed_message"),
    });
  }
}

onMounted(async () => {
  await Promise.all([fetchGenerators(1), fetchCities(), fetchStats()]);
  fetchMapPoints().then(() => fillMap());
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_generators.breadcrumb") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">{{ t("owner_generators.eyebrow") }}</p>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <PlugZap aria-hidden="true" />
            </span>
            {{ t("owner_generators.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab]">
            {{ t("owner_generators.subtitle_running", { active: stats?.active ?? 0, total: stats?.total ?? generators.length }) }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2.5">
          <a :href="exportUrl" target="_blank" rel="noopener" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
            <FileSpreadsheet aria-hidden="true" /> {{ t("owner_applications_page.export_excel_title") }}
          </a>
          <button type="button" @click="printPage" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
            <Printer aria-hidden="true" /> {{ t("owner_applications_page.print") }}
          </button>
          <button
            v-if="generators.length === 1"
            type="button" @click="openDiagnosticModal(generators[0])"
            class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#17A2B8]/50 text-[#0f6c7d] dark:text-[#7fdcec] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
            :title="t('generator_diagnostics.tooltip')"
          >
            <Activity aria-hidden="true" />
            {{ t("generator_diagnostics.title") }}
          </button>
          <button
            type="button" @click="openCreateForm"
            class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
          >
            <Plus aria-hidden="true" />
            {{ t("owner_generators.add_button") }}
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
          <div class="text-[10px] text-[#9a9d97] mt-1.5">{{ c.sub }}</div>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
          <div class="relative flex-1 min-w-[160px] max-w-xs">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
            <input
              v-model="search" type="text" @input="onSearchInput"
              :placeholder="t('owner_generators.search_placeholder')"
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
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="opt in STATUS_PILLS" :key="opt.v" type="button"
              @click="selectStatusFilter(opt.v)"
              class="shrink-0 px-3.5 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === opt.v ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >
              {{ opt.l }}
            </button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button :aria-label="t('common.view_as_table')" type="button" @click="viewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'table' }"><Table2 class="text-[11px]" aria-hidden="true" /></button>
            <button :aria-label="t('common.view_as_grid')" type="button" @click="viewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'grid' }"><GripVertical class="text-[11px]" aria-hidden="true" /></button>
          </div>
        </div>
      </div>
    </section>

    <!-- رسالة خطأ الحذف -->
    <div v-if="deleteError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-3">
      {{ deleteError }}
    </div>

    <!-- ===== TABLE / GRID ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-12 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="generators.length === 0 && !search && !statusFilter" class="text-center py-16">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center">
          <Zap class="text-2xl text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" />
        </div>
        <h3 class="text-[15px] font-bold mb-1.5">{{ t("owner_generators.empty_title") }}</h3>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-5">{{ t("owner_generators.empty_desc") }}</p>
        <button
          type="button" @click="openCreateForm"
          class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md inline-flex items-center gap-2"
        >
          {{ t("owner_generators.empty_button") }}
        </button>
      </div>

      <div v-else-if="generators.length === 0" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97]">{{ t("owner_generators.no_results") }}</p>
      </div>

      <template v-else>
        <!-- عرض جدول: مطابق لصفحة إدارة المولدات عند الأدمن -->
        <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[860px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg cursor-pointer select-none" @click="toggleSort('name')">
                  {{ t("dashboard.generator_col") }}
                  <AppIcon :name="sortIconClass('name')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3">{{ t("dashboard.city_col") }}</th>
                <th class="py-2.5 px-3">{{ t("dashboard.capacity_col") }}</th>
                <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('fuel')">
                  {{ t("dashboard.fuel_label") }}
                  <AppIcon :name="sortIconClass('fuel')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('subs')">
                  {{ t("dashboard.subscribers_col") }}
                  <AppIcon :name="sortIconClass('subs')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('rev')">
                  {{ t("generators_management_page.monthly_revenue_col") }}
                  <AppIcon :name="sortIconClass('rev')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3">{{ t("dashboard.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_generators.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="g in generators" :key="g.id"
                class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
                :class="{ 'opacity-40 pointer-events-none': deletingId === g.id }"
              >
                <td class="py-2.5 px-3">
                  <div class="font-bold truncate max-w-[9rem] mx-auto">{{ g.name }}</div>
                  <div class="text-[10px] text-[#9a9d97]">{{ g.code }}</div>
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ g.location?.city ?? "-" }}</td>
                <td class="py-2.5 px-3">{{ g.capacity_kw ?? "-" }} kW</td>
                <td class="py-2.5 px-3 w-28">
                  <div v-if="g.fuel_percentage !== null" class="flex items-center justify-center gap-2">
                    <AppIcon :name="fuelIcon(g.fuel_percentage)" class="text-[10px]" v-if="fuelIcon(g.fuel_percentage)" 
                       :style="{ color: fuelColor(g.fuel_percentage) }"
                      :title="fuelIconTitle(g.fuel_percentage)" />
                    <div class="bar-track w-14"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
                    <span class="text-[10.5px] font-bold w-8" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
                  </div>
                  <span v-else class="text-[10.5px] text-[#9a9d97]">-</span>
                </td>
                <td class="py-2.5 px-3">{{ g.active_subscriptions_count ?? 0 }}</td>
                <td class="py-2.5 px-3 font-bold">{{ fmtMoney(g.monthly_revenue_ils) }}</td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="STATUS_META[g.status]?.chip ?? 'chip-info'">{{ statusLabel(g.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div class="row-actions">
                    <button type="button" @click="openViewModal(g)" class="action-btn action-btn--view" :title="t('common.view')"><Eye aria-hidden="true" /></button>
                    <button type="button" @click="handleShowQr(g)" class="action-btn action-btn--view" :title="t('owner_generators.show_qr')"><QrCode aria-hidden="true" /></button>
                    <button type="button" @click="openDiagnosticModal(g)" class="action-btn action-btn--view !text-[#17A2B8]" :title="t('generator_diagnostics.title')"><Activity aria-hidden="true" /></button>
                    <button type="button" @click="openEditForm(g)" class="action-btn action-btn--edit" :title="t('common.edit')"><Pencil aria-hidden="true" /></button>
                    <span class="row-actions-divider"></span>
                    <button type="button" @click="openDeleteModal(g)" :disabled="deletingId === g.id" class="action-btn action-btn--delete" :title="t('common.delete')">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === g.id" /><Trash2 aria-hidden="true" v-else />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- عرض شبكي: نفس GeneratorCard السابق -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <GeneratorCard
            v-for="generator in generators"
            :key="generator.id"
            :generator="generator"
            :class="{ 'opacity-40 pointer-events-none': deletingId === generator.id }"
            @edit="openEditForm"
            @delete="openDeleteModal"
            @show-qr="handleShowQr"
            @view-details="openViewModal"
            @diagnostics="openDiagnosticModal"
          />
        </div>
      </template>

      <!-- ترقيم الصفحات -->
      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
        <span>{{ t("owner_generators.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchGenerators(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchGenerators(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
        </div>
      </div>
    </section>

    <!-- ===== خريطة + توزيع حالة + أضعف وقود (نفس تخطيط صفحة إدارة المولدات) ===== -->
    <section class="grid md:grid-cols-2 xl:grid-cols-3 gap-4 items-stretch">
      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("generators_management_page.my_generators_map_title") }}</h3>
        <div ref="mapContainerRef" class="flex-1 min-h-[16rem] rounded-xl overflow-hidden"></div>
        <p v-if="!isLoadingMapPoints && mapPoints.length === 0" class="text-[11px] text-[#9a9d97] text-center mt-2">
          {{ t("generators_map.no_coordinates") }}
        </p>
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("generators_management_page.status_breakdown_title") }}</h3>
        <div v-if="isLoadingStats" class="flex-1 min-h-[10rem] thumb-loading rounded-lg"></div>
        <template v-else>
          <div class="flex-1 min-h-[10rem]"><Doughnut :data="statusChartData" :options="doughnutOptions" /></div>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10">
            <span v-for="item in statusLegendItems" :key="item.label" class="flex items-center gap-1.5 text-[10.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5]">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: item.color }"></span>
              {{ item.label }} <b class="text-[#3a3a38] dark:text-[#eef0ec]">{{ item.value }}</b>
              <span class="text-[#9a9d97]">({{ item.pct }}%)</span>
            </span>
          </div>
        </template>
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("generators_management_page.lowest_fuel_title") }}</h3>
        <div v-if="lowestFuelGenerators.length === 0" class="flex-1 flex items-center justify-center text-[11px] text-[#9a9d97]">{{ t("generators_management_page.no_fuel_data") }}</div>
        <div v-else class="flex-1 space-y-2.5">
          <button
            v-for="g in lowestFuelGenerators" :key="g.id" type="button"
            class="flex items-center gap-2.5 w-full text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 rounded-lg p-1.5 -m-1 transition-colors"
            @click="openViewModal(g)"
          >
            <span class="text-[11px] font-bold truncate flex-1">{{ g.name }}</span>
            <div class="bar-track w-16"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
            <span class="text-[10.5px] font-bold w-9 text-end" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ===== نافذة عرض التفاصيل (نفس هوية صفحة إدارة المولدات عند الأدمن) — مكوّن مشترك ===== -->
    <GeneratorViewModal
      :open="isViewOpen"
      :generator="viewingGenerator"
      @close="isViewOpen = false"
      @edit="switchToEditFromView"
    />

    <!-- لوحة الإضافة/التعديل (نفس فورم المالك القديم — GeneratorForm.vue) -->
    <GeneratorForm
      :open="isFormOpen"
      :generator="editingGenerator"
      :is-saving="isSaving"
      :server-error="saveError"
      @close="isFormOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- ===== DELETE CONFIRM MODAL (بنفس هوية صفحة إدارة المولدات عند الأدمن) ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isDeleteModalOpen && deletingGenerator" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeDeleteModal">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--danger">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Trash2 aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_generators.delete_title", { name: deletingGenerator.name }) }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="closeDeleteModal" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5 text-center">
              <div class="w-14 h-14 rounded-full bg-[#D9534F]/10 flex items-center justify-center mx-auto">
                <TriangleAlert class="text-[#D9534F] text-xl" aria-hidden="true" />
              </div>
              <p class="text-[12.5px] leading-relaxed text-[#4b4b4b] dark:text-[#d7d9d3]">
                {{ t("owner_generators.delete_message") }}
              </p>
              <div v-if="deleteError" class="alert-box text-start">
                <CircleAlert class="shrink-0" aria-hidden="true" />
                <span>{{ deleteError }}</span>
              </div>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="closeDeleteModal" class="btn-outline-brand">{{ t("owner_generators.cancel") }}</button>
              <button type="button" @click="confirmDeleteGenerator" :disabled="deletingId === deletingGenerator.id" class="btn-fill-brand btn-fill-brand--danger">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === deletingGenerator.id" /><Trash2 aria-hidden="true" v-else />
                {{ t("owner_generators.delete_confirm") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- نافذة QR (بدون تغيير) -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="qrModal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="qrModal.open = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-xs shadow-2xl overflow-hidden">
            <div class="modal-head-brand modal-head-brand--gold">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><QrCode aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title truncate">{{ qrModal.generatorName }}</h3>
                </div>
              </div>
              <button type="button" class="modal-head-brand__close" :aria-label="t('owner_generators.close')" @click="qrModal.open = false">
                <X aria-hidden="true" />
              </button>
            </div>
            <div class="p-5 text-center">
              <p class="text-[11px] text-[#9a9d97] mb-4">{{ t("owner_generators.qr_scan_hint") }}</p>
              <div class="rounded-2xl bg-[#EBF1E7] dark:bg-white/5 p-4 flex items-center justify-center min-h-[180px]">
                <LoaderCircle class="text-2xl text-[#8A6D1F] animate-spin" aria-hidden="true" v-if="qrModal.loading" />
                <img v-else-if="qrModal.src" :src="qrModal.src" :alt="t('owner_generators.qr_code')" class="w-40 h-40" />
              </div>
            </div>
            <div class="modal-footer-brand !justify-center">
              <button type="button" class="btn-outline-brand w-full" @click="qrModal.open = false">{{ t("owner_generators.close") }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== نافذة إدخال البيانات التشخيصية (تغذية تنبؤ الأعطال بالذكاء الاصطناعي) ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isDiagnosticModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isDiagnosticModalOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl max-h-[90vh] overflow-y-auto">
            <div class="modal-head-brand modal-head-brand--blue">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Activity aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title truncate">
                    {{ t("generator_diagnostics.title") }} — {{ diagnosticTargetGenerator?.name }}
                  </h3>
                  <p class="modal-head-brand__subtitle">
                    {{ t("generator_diagnostics.modal_subtitle") }}
                  </p>
                </div>
              </div>
              <button type="button" @click="isDiagnosticModalOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <form v-if="!diagnosticSavedReading" @submit.prevent="submitDiagnosticForm" class="p-5 space-y-3.5">
              <div v-if="diagnosticError?.message" class="alert-box">
                <CircleAlert class="shrink-0" aria-hidden="true" /> {{ diagnosticError.message }}
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="field-label">{{ t("generator_diagnostics.oil_temp_label") }}</label>
                  <input v-model.number="diagnosticForm.oil_temperature" type="number" step="0.1" class="field-input" dir="ltr" />
                </div>
                <div>
                  <label class="field-label">{{ t("generator_diagnostics.coolant_temp_label") }}</label>
                  <input v-model.number="diagnosticForm.coolant_temperature" type="number" step="0.1" class="field-input" dir="ltr" />
                </div>
                <div>
                  <label class="field-label">{{ t("generator_diagnostics.vibration_label") }}</label>
                  <input v-model.number="diagnosticForm.vibration_level" type="number" step="0.01" class="field-input" dir="ltr" />
                </div>
                <div>
                  <label class="field-label">{{ t("generator_diagnostics.oil_pressure_label") }}</label>
                  <input v-model.number="diagnosticForm.oil_pressure" type="number" step="0.1" class="field-input" dir="ltr" />
                </div>
                <div>
                  <label class="field-label">{{ t("generator_diagnostics.battery_voltage_label") }}</label>
                  <input v-model.number="diagnosticForm.battery_voltage" type="number" step="0.1" class="field-input" dir="ltr" />
                </div>
                <div>
                  <label class="field-label">{{ t("generator_diagnostics.run_hours_label") }}</label>
                  <input v-model.number="diagnosticForm.run_hours" type="number" step="1" class="field-input" dir="ltr" />
                </div>
                <div class="col-span-2">
                  <label class="field-label">
                    {{ t("generator_diagnostics.noise_level_label") }}
                    <span class="text-[#9a9d97] font-normal">({{ t("generator_diagnostics.optional_label") }})</span>
                  </label>
                  <input v-model.number="diagnosticForm.noise_level" type="number" step="0.1" class="field-input" dir="ltr" />
                </div>
              </div>

              <div>
                <label class="field-label">{{ t("generator_diagnostics.notes_label") }}</label>
                <textarea
                  v-model="diagnosticForm.notes" rows="2" maxlength="1000" class="field-input resize-none"
                  :placeholder="t('generator_diagnostics.notes_placeholder')"
                ></textarea>
              </div>

              <p class="text-[10.5px] text-[#9a9d97]">
                {{ t("generator_diagnostics.hint") }}
              </p>

              <div class="modal-footer-brand !px-0 !pb-0">
                <button type="button" @click="isDiagnosticModalOpen = false" class="btn-outline-brand">{{ t("generator_diagnostics.cancel") }}</button>
                <button type="submit" :disabled="isSubmittingDiagnostic" class="btn-fill-brand">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmittingDiagnostic" /><Save aria-hidden="true" v-else />
                  {{ isSubmittingDiagnostic ? t("generator_diagnostics.saving") : t("generator_diagnostics.save") }}
                </button>
              </div>
            </form>

            <div v-else class="p-5 space-y-3.5">
              <div v-if="analyzeError?.message" class="alert-box">
                <CircleAlert class="shrink-0" aria-hidden="true" /> {{ analyzeError.message }}
              </div>
              <p class="text-sm text-[#5b5e59] dark:text-[#c9cbc6]">
                {{ t("generator_diagnostics.analyze_prompt") }}
              </p>
              <div class="modal-footer-brand !px-0 !pb-0">
                <button type="button" @click="isDiagnosticModalOpen = false" class="btn-outline-brand">{{ t("generator_diagnostics.close") }}</button>
                <button type="button" :disabled="isAnalyzing" @click="runAnalyzeReading" class="btn-fill-brand">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isAnalyzing" /><Sparkles aria-hidden="true" v-else />
                  {{ isAnalyzing ? t("generator_diagnostics.analyzing") : t("generator_diagnostics.analyze_button") }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>