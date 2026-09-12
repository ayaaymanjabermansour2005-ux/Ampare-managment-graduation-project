<script setup>

import { ref, computed, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAdminGenerators } from "@/composables/useAdminGenerators";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";
import { useToastStore } from "@/stores/toast";
import { useConfirm } from "@/composables/useConfirm";
import { usePermissions } from "@/composables/usePermissions";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { printTable } from "@/utils/printTable";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import ColumnFilterPopover from "@/components/ui/ColumnFilterPopover.vue";
import AdminGeneratorFormModal from "@/components/generators/AdminGeneratorFormModal.vue";
import GeneratorViewModal from "@/components/generators/GeneratorViewModal.vue";
import { Check, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, Eye, FileSpreadsheet, GripVertical, LoaderCircle, Pencil, Plus, Printer, Search, Table2, Trash2, TriangleAlert, X, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";
import { vReveal } from "@/directives/reveal";

const props = defineProps({
  showKpis: { type: Boolean, default: true },
});

const { t, locale } = useI18n();
const route = useRoute();
const toast = useToastStore();
const { confirm: confirmDialog } = useConfirm();
const { can, hasRole } = usePermissions();

const {
  generators, pagination, isLoading, error,
  search, statusFilter, sortBy, areaFilter, ownerFilter, cities,
  capacityFilter, fuelFilter, subscribersFilter, revenueFilter,
  stats, isLoadingStats,
  isSaving, saveError,
  deletingId, deleteError,
  fetchGenerators, fetchCities, onSearchInput, onFilterChange, onSortChange,
  createGenerator, updateGenerator, deleteGenerator,
  loadAll,
} = useAdminGenerators();

/* FIX (تدقيق شامل — الجولة الخامسة): ما كان في catch — فشل هالطلب كان يطلع
 * Unhandled Promise Rejection بصمت، وفلتر/نموذج "المالك" (لازم لإنشاء مولد
 * كأدمن) يضل فاضي بدون أي إشارة للأدمن إنو في مشكلة.
 */
const owners = ref([]);
async function fetchOwners() {
  try {
    const { data } = await userService.list({ role: "generator_owner", per_page: 100 });
    const list = data.data.data ?? data.data;
    owners.value = [...list].sort((a, b) => a.name.localeCompare(b.name, locale.value === "ar" ? "ar" : "en"));
  } catch (err) {
    toast.show({ type: "danger", title: normalizeApiError(err, t("generators_management_page.owners_load_error")).message });
  }
}

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

/**
 * يبني كلاس CSS (شفافية + دوران) لأيقونة سهم الفرز الثابتة (ChevronDown) حسب
 * كون العمود الحالي هو المُفرَّز أم لا واتجاهه — وليس اسم أيقونة، يُستخدَم
 * حصرًا مع :class على <ChevronDown>، وليس مع :name على <AppIcon>.
 */
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

const viewMode = ref("table");

function fmtMoney(n) {
  return "₪ " + Number(n ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}
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
const FUEL_TYPE_KEYS = {
  diesel: "dashboard.fuel_diesel",
  gas: "dashboard.fuel_gas",
  petrol: "dashboard.fuel_petrol",
  dual: "dashboard.fuel_dual",
};
function fuelTypeLabel(g) {
  const key = FUEL_TYPE_KEYS[g.fuel_type];
  return key ? t(key) : (g.fuel_type_label ?? "-");
}

const KPI_CARDS = computed(() => {
  if (!stats.value) return [];
  const s = stats.value;
  return [
    { icon: "fa-plug-circle-bolt", label: t("generators_management_page.total_generators"), value: s.total, tone: "primary" },
    { icon: "fa-circle-check", label: t("status.active"), value: s.active, tone: "success" },
    { icon: "fa-screwdriver-wrench", label: t("status.maintenance"), value: s.maintenance, tone: "warning" },
    { icon: "fa-power-off", label: t("generators_management_page.inactive_rejected"), value: s.inactive + s.rejected, tone: "danger" },
    { icon: "fa-gas-pump", label: t("generators_management_page.avg_fuel_level"), value: s.avg_fuel_percentage !== null ? s.avg_fuel_percentage : "-", suffix: s.avg_fuel_percentage !== null ? "%" : "", tone: "info" },
    { icon: "fa-wallet", label: t("generators_management_page.total_monthly_revenue"), value: fmtMoney(s.total_monthly_revenue_ils), tone: "secondary" },
  ];
});

/* ---------------- نموذج الإضافة/التعديل ---------------- */
const isFormOpen = ref(false);
const editingGenerator = ref(null);

async function openAddModal() {
  editingGenerator.value = null;
  saveError.value = null;
  if (owners.value.length === 0) await fetchOwners();
  isFormOpen.value = true;
}
defineExpose({ openAddModal });

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

/* ---------------- نافذة العرض ---------------- */
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

/* ---------------- حذف مولد ---------------- */
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

/* ---------------- تصدير/طباعة — خاصان بهذا الجدول فقط ---------------- */
const exportUrl = computed(() =>
  generatorService.exportUrl({
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    city: areaFilter.value || undefined,
    owner_id: ownerFilter.value || undefined,
    capacity_min: capacityFilter.value.min || undefined,
    capacity_max: capacityFilter.value.max || undefined,
    fuel_min: fuelFilter.value.min || undefined,
    fuel_max: fuelFilter.value.max || undefined,
    subscribers_min: subscribersFilter.value.min || undefined,
    subscribers_max: subscribersFilter.value.max || undefined,
    revenue_min: revenueFilter.value.min || undefined,
    revenue_max: revenueFilter.value.max || undefined,
  }),
);
function printPage() {
  printTable({
    title: t("generators_management_page.title"),
    locale: locale.value,
    columns: [
      { label: t("dashboard.generator_col"), value: (g) => g.name },
      { label: t("dashboard.city_col"), value: (g) => g.location?.city ?? "-" },
      { label: t("dashboard.capacity_col"), value: (g) => `${g.capacity_kw ?? "-"} ${t("dashboard.kw_label")}` },
      { label: t("dashboard.fuel_type_label"), value: (g) => fuelTypeLabel(g) },
      { label: t("dashboard.subscribers_col"), value: (g) => g.active_subscriptions_count ?? 0 },
      { label: t("generators_management_page.monthly_revenue_col"), value: (g) => fmtMoney(g.monthly_revenue_ils) },
      { label: t("dashboard.status_col"), value: (g) => statusLabel(g.status) },
    ],
    rows: generators.value,
  });
}

onMounted(async () => {
  await Promise.all([loadAll(), fetchCities(), fetchOwners()]);

  const highlightId = route.query.highlight ? Number(route.query.highlight) : null;
  if (highlightId) {
    const match = generators.value.find((g) => g.id === highlightId);
    if (match) openViewModal(match);
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== KPI CARDS ===== -->
    <section v-if="showKpis" v-reveal>
      <div v-if="isLoadingStats" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3.5">
        <div v-for="i in 6" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone" :suffix="c.suffix ?? ''"
        />
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4 print-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
          <div class="relative flex-1 min-w-[160px] max-w-xs">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="search" type="text" @input="onSearchInput"
              :placeholder="t('generators_management_page.search_placeholder')"
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
          <button v-if="can('generators.create')" type="button" @click="openAddModal" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2">
            <Plus aria-hidden="true" /> {{ $t("owner_dashboard.add_generator") }}
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
      <div v-else-if="generators.length === 0" class="flex flex-col items-center justify-center text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("generators_management_page.no_matching_generators") }}</p>
      </div>

      <template v-else>
      <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[900px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('name')">
                  {{ $t("dashboard.generator_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('name')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.city_col") }}</th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1">
                  {{ $t("dashboard.capacity_col") }}
                  <ColumnFilterPopover v-model="capacityFilter" type="number" @update:modelValue="onFilterChange" @click.stop />
                </span>
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('fuel')">
                  {{ $t("dashboard.fuel_label") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('fuel')]" aria-hidden="true" />
                </span>
                <ColumnFilterPopover v-model="fuelFilter" type="number" @update:modelValue="onFilterChange" @click.stop class="ms-1" />
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('subs')">
                  {{ $t("dashboard.subscribers_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('subs')]" aria-hidden="true" />
                </span>
                <ColumnFilterPopover v-model="subscribersFilter" type="number" @update:modelValue="onFilterChange" @click.stop class="ms-1" />
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('rev')">
                  {{ $t("generators_management_page.monthly_revenue_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('rev')]" aria-hidden="true" />
                </span>
                <ColumnFilterPopover v-model="revenueFilter" type="number" @update:modelValue="onFilterChange" @click.stop class="ms-1" />
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
              <td class="py-2.5 px-3 w-32">
                <div class="text-[10px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] mb-1 text-center">{{ fuelTypeLabel(g) }}</div>
                <div v-if="g.fuel_percentage !== null" class="flex items-center justify-center gap-2">
                  <AppIcon :name="fuelIcon(g.fuel_percentage)" class="text-[10px]" v-if="fuelIcon(g.fuel_percentage)"
                    :style="{ color: fuelColor(g.fuel_percentage) }"
                    :title="fuelIconTitle(g.fuel_percentage)" />
                  <div class="bar-track w-14"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
                  <span class="text-[10.5px] font-bold w-8" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
                </div>
                <span v-else class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] block text-center">-</span>
              </td>
              <td class="py-2.5 px-3">{{ g.active_subscriptions_count ?? 0 }}</td>
              <td class="py-2.5 px-3 font-bold">{{ fmtMoney(g.monthly_revenue_ils) }}</td>
              <td class="py-2.5 px-3"><span class="status-chip" :class="STATUS_META[g.status]?.chip ?? 'chip-info'">{{ statusLabel(g.status) }}</span></td>
              <td class="py-2.5 px-3">
                <div class="row-actions justify-center">
                  <template v-if="g.status === 'pending_verification' && hasRole('admin')">
                    <button type="button" @click="handleVerify(g)" :disabled="isVerifying" class="action-btn action-btn--view !text-[#28A745]" :title="$t('generators_management_page.approve_action')" :aria-label="$t('generators_management_page.approve_action')"><Check aria-hidden="true" /></button>
                    <button type="button" @click="openRejectModal(g)" class="action-btn action-btn--delete" :title="$t('owner_applications_page.reject_action')" :aria-label="$t('owner_applications_page.reject_action')"><X aria-hidden="true" /></button>
                    <span class="row-actions-divider"></span>
                  </template>
                  <button type="button" @click="openViewModal(g)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')"><Eye aria-hidden="true" /></button>
                  <button v-if="can('generators.update')" type="button" @click="openEditModal(g)" class="action-btn action-btn--edit" :title="$t('common.edit')" :aria-label="$t('common.edit')"><Pencil aria-hidden="true" /></button>
                  <span class="row-actions-divider"></span>
                  <button v-if="can('generators.delete')" type="button" @click="openDeleteModal(g)" :disabled="deletingId === g.id" class="action-btn action-btn--delete" :title="$t('common.delete')" :aria-label="$t('common.delete')">
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
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.fuel_type_label") }}:</span> <b>{{ fuelTypeLabel(g) }}</b></div>
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
              <template v-if="g.status === 'pending_verification' && hasRole('admin')">
                <button type="button" @click="handleVerify(g)" :disabled="isVerifying" class="action-btn action-btn--view !text-[#28A745]" :title="$t('generators_management_page.approve_action')" :aria-label="$t('generators_management_page.approve_action')"><Check aria-hidden="true" /></button>
                <button type="button" @click="openRejectModal(g)" class="action-btn action-btn--delete" :title="$t('owner_applications_page.reject_action')" :aria-label="$t('owner_applications_page.reject_action')"><X aria-hidden="true" /></button>
                <span class="row-actions-divider"></span>
              </template>
              <button type="button" @click="openViewModal(g)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')"><Eye aria-hidden="true" /></button>
              <button v-if="can('generators.update')" type="button" @click="openEditModal(g)" class="action-btn action-btn--edit" :title="$t('common.edit')" :aria-label="$t('common.edit')"><Pencil aria-hidden="true" /></button>
              <span class="row-actions-divider"></span>
              <button v-if="can('generators.delete')" type="button" @click="openDeleteModal(g)" :disabled="deletingId === g.id" class="action-btn action-btn--delete" :title="$t('common.delete')" :aria-label="$t('common.delete')">
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

    <!-- ===================== ADD / EDIT MODAL ===================== -->
    <AdminGeneratorFormModal
      :open="isFormOpen"
      :generator="editingGenerator"
      :owners="owners"
      :is-saving="isSaving"
      :save-error="saveError"
      @close="closeForm"
      @submit="handleGeneratorFormSubmit"
    />

    <!-- ===================== VIEW MODAL ===================== -->
    <GeneratorViewModal
      :open="isViewOpen"
      :generator="viewingGenerator"
      @close="isViewOpen = false"
      @edit="switchToEditFromView"
    />

    <!-- ===================== DELETE CONFIRM MODAL ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isDeleteModalOpen && deletingGenerator" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeDeleteModal">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
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
