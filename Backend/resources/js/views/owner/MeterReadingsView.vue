<script setup>
import { reactive, ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useOwnerMeterReadings } from "@/composables/useOwnerMeterReadings";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Ban, ChartColumn, Check, ChevronLeft, ChevronRight, CircleAlert, ClockAlert, FileSpreadsheet, Gauge, GripVertical, Image, ListChecks, LoaderCircle, Plus, Printer, Save, Search, Send, Table2, TriangleAlert, WifiOff, X } from "@lucide/vue";


const {
  readings,
  pagination,
  isLoading,
  error,
  search,
  statusFilter,
  fetchReadings,
  onSearchInput,
  onFilterChange,
  generators,
  loadGenerators,
  subscriptionsForGenerator,
  isLoadingSubscriptions,
  loadSubscriptionsFor,
  isSubmitting,
  submitError,
  submitReading,
  approvingId,
  approveError,
  approveReading,
  rejectingId,
  rejectError,
  rejectReading,
  overdueSubscribers,
  isLoadingOverdue,
  loadOverdueSubscribers,
  subscriberHistory,
  isLoadingHistory,
  loadSubscriberHistory,
} = useOwnerMeterReadings();

const { confirm } = useConfirm();
const toast = useToastStore();
const { t, locale } = useI18n();
const route = useRoute();

const STATUS_META = {
  pending_approval: { chip: "chip-warning", color: "#FFC107" },
  approved: { chip: "chip-success", color: "#28A745" },
  rejected: { chip: "chip-danger", color: "#D9534F" },
};

function readingStatusLabel(status) {
  return t(`owner_meter_readings.status.${status}`, status);
}

const STATUS_PILLS = computed(() => [
  { v: "", l: t("common.all") },
  { v: "pending_approval", l: t("owner_meter_readings.status.pending_approval") },
  { v: "approved", l: t("owner_meter_readings.status.approved") },
  { v: "rejected", l: t("owner_meter_readings.status.rejected") },
]);

function selectStatusFilter(value) {
  statusFilter.value = value;
  onFilterChange();
}

const viewMode = ref("list"); // 'list' | 'grid'

function buildReceiptHtml(reading) {
  const statusLabel = readingStatusLabel(reading.status);
  const isRtl = locale.value === "ar";
  return `
    <html dir="${isRtl ? "rtl" : "ltr"}" lang="${locale.value}">
      <head>
        <meta charset="utf-8" />
        <title>${t("owner_meter_readings.receipt_title")}</title>
        <style>
          body { font-family: Tahoma, Arial, sans-serif; padding: 32px; color: #222; }
          h1 { font-size: 18px; margin-bottom: 4px; }
          .sub { color: #777; font-size: 12px; margin-bottom: 20px; }
          table { width: 100%; border-collapse: collapse; font-size: 13px; }
          td { padding: 8px 6px; border-bottom: 1px solid #eee; }
          td:first-child { color: #777; width: 40%; }
          td:last-child { font-weight: bold; }
        </style>
      </head>
      <body>
        <h1>${t("owner_meter_readings.receipt_title")}</h1>
        <div class="sub">${t("owner_meter_readings.receipt_reading_hash", { id: reading.id })}</div>
        <table>
          <tr><td>${t("owner_meter_readings.subscriber_col")}</td><td>${reading.subscriber?.name ?? "-"}</td></tr>
          <tr><td>${t("owner_meter_readings.date_col")}</td><td>${reading.reading_date}</td></tr>
          <tr><td>${t("owner_meter_readings.previous_col")}</td><td>${reading.previous_reading}</td></tr>
          <tr><td>${t("owner_meter_readings.current_col")}</td><td>${reading.current_reading}</td></tr>
          <tr><td>${t("owner_meter_readings.consumed_col")}</td><td>${reading.consumed_kw} kW</td></tr>
          <tr><td>${t("owner_meter_readings.status_col")}</td><td>${statusLabel}</td></tr>
        </table>
      </body>
    </html>
  `;
}

function printReading(reading) {
  const printWindow = window.open("", "_blank", "width=480,height=640");
  if (!printWindow) return;
  printWindow.document.write(buildReceiptHtml(reading));
  printWindow.document.close();
  printWindow.focus();
  printWindow.onload = () => printWindow.print();
}

async function sendReading(reading) {
  const statusLabel = readingStatusLabel(reading.status);
  const summary = t("owner_meter_readings.receipt_share_text", {
    name: reading.subscriber?.name ?? "-",
    date: reading.reading_date,
    current: reading.current_reading,
    consumed: reading.consumed_kw,
    status: statusLabel,
  });

  if (navigator.share) {
    try {
      await navigator.share({ title: t("owner_meter_readings.receipt_title"), text: summary });
      return;
    } catch {
    }
  }

  const phone = reading.subscriber?.phone;
  if (phone) {
    const url = `https://wa.me/${phone.replace(/[^0-9]/g, "")}?text=${encodeURIComponent(summary)}`;
    window.open(url, "_blank");
  } else {
    toast.show({
      type: "info",
      title: t("owner_meter_readings.receipt_title"),
      message: summary,
    });
  }
}

const ANOMALY_THRESHOLD = 0.4;

function anomalyInfo(reading) {
  const avg = Number(reading.average_monthly_kw ?? 0);
  const consumed = Number(reading.consumed_kw ?? 0);
  if (!avg || avg <= 0) return null;
  const diffRatio = (consumed - avg) / avg;
  if (Math.abs(diffRatio) < ANOMALY_THRESHOLD) return null;
  return {
    direction: diffRatio > 0 ? "high" : "low",
    percent: Math.round(Math.abs(diffRatio) * 100),
  };
}

const isFormOpen = ref(false);
const selectedGeneratorId = ref(null);
const readingForm = reactive({
  subscription_id: "",
  current_reading: "",
  reading_date: new Date().toISOString().slice(0, 10),
});
let idempotencyKey = crypto.randomUUID();
const queuedMessage = ref(null);

// ---- رفع صورة العداد (اختياري) ----
const meterImageFile = ref(null);
const meterImagePreview = ref(null);
const meterImageInput = ref(null);

function onMeterImageChange(e) {
  const file = e.target.files?.[0];
  if (!file) {
    meterImageFile.value = null;
    meterImagePreview.value = null;
    return;
  }
  meterImageFile.value = file;
  meterImagePreview.value = URL.createObjectURL(file);
}

function clearMeterImage() {
  meterImageFile.value = null;
  meterImagePreview.value = null;
  if (meterImageInput.value) meterImageInput.value.value = "";
}

// خيارات AppDropdownSelect: المولد / المشترك (بنفس هوية صفحة المشتركين)
const generatorDropdownOptions = computed(() =>
  generators.value.map((g) => ({ value: g.id, label: g.name })),
);
const subscriberDropdownOptions = computed(() =>
  subscriptionsForGenerator.value.map((s) => ({ value: s.id, label: s.subscriber?.name })),
);

async function openForm() {
  isFormOpen.value = true;
  queuedMessage.value = null;
  submitError.value = null;
  clearMeterImage();
  if (generators.value.length === 0) await loadGenerators();
  if (generators.value.length) {
    selectedGeneratorId.value = generators.value[0].id;
    await loadSubscriptionsFor(selectedGeneratorId.value);
  }
}

async function handleGeneratorChange() {
  readingForm.subscription_id = "";
  await loadSubscriptionsFor(selectedGeneratorId.value);
}

async function handleSubmit() {
  const result = await submitReading(
    { ...readingForm },
    idempotencyKey,
    meterImageFile.value,
  );
  if (result.success) {
    if (result.isQueued) {
      queuedMessage.value = t("owner_meter_readings.queued_offline_message");
    } else {
      idempotencyKey = crypto.randomUUID();
      isFormOpen.value = false;
      readingForm.subscription_id = "";
      readingForm.current_reading = "";
      clearMeterImage();
      toast.show({
        type: "success",
        title: t("owner_meter_readings.recorded_toast_title"),
        message: t("owner_meter_readings.recorded_toast_message"),
      });
    }
  } else {
    toast.show({
      type: "danger",
      title: t("owner_meter_readings.submit_failed_title"),
      message: submitError.value ?? t("owner_meter_readings.submit_error"),
    });
  }
}

async function handleApprove(reading) {
  const confirmed = await confirm({
    title: t("owner_meter_readings.approve_confirm_title"),
    message: t("owner_meter_readings.approve_confirm_message", { name: reading.subscriber?.name ?? "-" }),
    confirmLabel: t("owner_meter_readings.approve_action"),
    variant: "default",
  });
  if (!confirmed) return;

  const ok = await approveReading(reading.id);
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_meter_readings.approved_toast_title"),
      message: t("owner_meter_readings.approved_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_meter_readings.approve_failed_title"),
      message: approveError.value ?? t("owner_meter_readings.approve_error"),
    });
  }
}

// ===================== رفض القراءة مع سبب =====================
const isRejectModalOpen = ref(false);
const rejectTarget = ref(null);
const rejectReason = ref("");

function openRejectModal(reading) {
  rejectTarget.value = reading;
  rejectReason.value = "";
  isRejectModalOpen.value = true;
}

async function confirmReject() {
  if (!rejectTarget.value || !rejectReason.value.trim()) return;
  const ok = await rejectReading(rejectTarget.value.id, rejectReason.value.trim());
  if (ok) {
    isRejectModalOpen.value = false;
    toast.show({
      type: "success",
      title: t("owner_meter_readings.rejected_toast_title"),
      message: t("owner_meter_readings.rejected_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_meter_readings.reject_failed_title"),
      message: rejectError.value ?? t("owner_meter_readings.reject_error"),
    });
  }
}

// ===================== تصدير Excel/CSV للقراءات المعروضة =====================
function exportCsv() {
  if (!readings.value.length) return;

  const headers = [
    t("owner_meter_readings.subscriber_col"),
    t("owner_meter_readings.date_col"),
    t("owner_meter_readings.previous_col"),
    t("owner_meter_readings.current_col"),
    t("owner_meter_readings.consumed_col"),
    t("owner_meter_readings.status_col"),
  ];

  const escapeCell = (val) => `"${String(val ?? "-").replace(/"/g, '""')}"`;

  const rows = readings.value.map((r) =>
    [
      r.subscriber?.name,
      r.reading_date,
      r.previous_reading,
      r.current_reading,
      `${r.consumed_kw} kW`,
      readingStatusLabel(r.status),
    ]
      .map(escapeCell)
      .join(","),
  );

  const csvContent = "\uFEFF" + [headers.map(escapeCell).join(","), ...rows].join("\r\n");
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `meter-readings-${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

// ===================== طباعة الصفحة كاملة (زر أيقونة بالتولبار، بالإضافة لطباعة كل قراءة لحالها) =====================
function printPage() {
  window.print();
}

// ===================== قراءة جماعية سريعة =====================
const isBulkFormOpen = ref(false);
const bulkGeneratorId = ref(null);
const bulkRows = ref([]); // [{ subscription_id, subscriber_name, current_reading, previous_reading }]
const isBulkSubmitting = ref(false);
const bulkErrors = ref({}); // subscription_id -> error message

const bulkGeneratorDropdownOptions = generatorDropdownOptions;

async function openBulkForm() {
  isBulkFormOpen.value = true;
  bulkErrors.value = {};
  if (generators.value.length === 0) await loadGenerators();
  if (generators.value.length) {
    bulkGeneratorId.value = generators.value[0].id;
    await handleBulkGeneratorChange();
  }
}

async function handleBulkGeneratorChange() {
  await loadSubscriptionsFor(bulkGeneratorId.value);
  bulkRows.value = subscriptionsForGenerator.value.map((s) => ({
    subscription_id: s.id,
    subscriber_name: s.subscriber?.name ?? "-",
    previous_reading: s.last_reading ?? "-",
    current_reading: "",
  }));
}

async function submitBulkReadings() {
  const rowsToSubmit = bulkRows.value.filter((r) => String(r.current_reading).trim() !== "");
  if (!rowsToSubmit.length) return;

  isBulkSubmitting.value = true;
  bulkErrors.value = {};
  const today = new Date().toISOString().slice(0, 10);
  let successCount = 0;

  for (const row of rowsToSubmit) {
    const key = crypto.randomUUID();
    const result = await submitReading(
      {
        subscription_id: row.subscription_id,
        current_reading: row.current_reading,
        reading_date: today,
      },
      key,
    );
    if (result.success && !result.isQueued) {
      successCount += 1;
    } else {
      bulkErrors.value[row.subscription_id] =
        submitError.value ?? t("owner_meter_readings.submit_error");
    }
  }

  isBulkSubmitting.value = false;

  if (successCount > 0) {
    toast.show({
      type: "success",
      title: t("owner_meter_readings.bulk_recorded_toast_title"),
      message: t("owner_meter_readings.bulk_recorded_toast_message", { count: successCount }),
    });
  }

  if (!Object.keys(bulkErrors.value).length) {
    isBulkFormOpen.value = false;
    fetchReadings(pagination.value?.current_page ?? 1);
  }
}

// ===================== تفاصيل المشترك + رسم بياني للاستهلاك =====================
const isDetailsOpen = ref(false);
const detailsReading = ref(null);

function openDetails(reading) {
  detailsReading.value = reading;
  isDetailsOpen.value = true;
  loadSubscriberHistory(reading.subscription_id ?? reading.subscriber?.subscription_id);
}

const detailsHistory = computed(() => subscriberHistory.value ?? []);
const detailsChartMax = computed(() => {
  if (!detailsHistory.value.length) return 0;
  return Math.max(...detailsHistory.value.map((h) => Number(h.consumed_kw) || 0), 1);
});

function barHeight(kw) {
  const max = detailsChartMax.value || 1;
  return Math.max((Number(kw) / max) * 100, 4);
}

onMounted(() => {
  // يسمح بفتح هذه الصفحة مع بحث مسبَق التعبئة (مثلاً من زر "قراءات المشترك"
  // بصفحة المشتركين): ?search=اسم المشترك
  if (route.query.search) search.value = String(route.query.search);
  fetchReadings();
  if (typeof loadOverdueSubscribers === "function") loadOverdueSubscribers();
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_meter_readings.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">{{ t("owner_meter_readings.eyebrow") }}</p>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <Gauge aria-hidden="true" />
            </span>
            {{ t("owner_meter_readings.title") }}
          </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button" @click="exportCsv" :disabled="!readings.length"
            class="btn-outline-brand !text-[12px] !px-3.5 !py-2.5 flex items-center gap-2 disabled:opacity-40"
          >
            <FileSpreadsheet aria-hidden="true" />
            {{ t("owner_meter_readings.export_button") }}
          </button>
          <button
            type="button" @click="openBulkForm"
            class="btn-outline-brand !text-[12px] !px-3.5 !py-2.5 flex items-center gap-2"
          >
            <ListChecks aria-hidden="true" />
            {{ t("owner_meter_readings.bulk_button") }}
          </button>
          <button
            type="button" @click="openForm"
            class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
          >
            <Plus aria-hidden="true" />
            {{ t("owner_meter_readings.add_button") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== تنبيه قراءات متأخرة ===== -->
    <section
      v-reveal
      v-if="overdueSubscribers && overdueSubscribers.length"
      class="glass-card p-4 !border-[#D9534F]/30 !bg-[#D9534F]/5"
    >
      <div class="flex items-center gap-2 mb-2.5">
        <TriangleAlert class="text-[#D9534F]" aria-hidden="true" />
        <h3 class="text-[12.5px] font-bold text-[#D9534F]">
          {{ t("owner_meter_readings.overdue_title", { count: overdueSubscribers.length }) }}
        </h3>
      </div>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="sub in overdueSubscribers" :key="sub.id"
          class="text-[11px] font-semibold px-3 py-1.5 rounded-full bg-[#D9534F]/10 text-[#D9534F] flex items-center gap-1.5"
        >
          <ClockAlert class="text-[10px]" aria-hidden="true" />
          {{ sub.name }}
          <span v-if="sub.generator_name" class="text-[#D9534F]/70">· {{ sub.generator_name }}</span>
        </span>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative flex-1 min-w-[220px] max-w-xs">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
          <input
            v-model="search" type="text" @input="onSearchInput"
            :placeholder="t('owner_meter_readings.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>

        <!-- ===== كبسولة الحالات + تبويب عرض جدول/شبكة سوا بمجموعة وحدة (زي صفحة إدارة المولدات) ===== -->
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in STATUS_PILLS" :key="pill.v" type="button"
              @click="selectStatusFilter(pill.v)"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === pill.v ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.l }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <!-- ===== تبويب عرض جدول/شبكة — مطابق حرفيًا لنفس التبويب بصفحة إدارة المولدات (icon-btn + fa-table-list) ===== -->
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button
              :aria-label="t('owner_meter_readings.view_list')"
              type="button" @click="viewMode = 'list'"
              :title="t('owner_meter_readings.view_list')"
              class="icon-btn !w-8 !h-8"
              :class="{ '!bg-white dark:!bg-white/10': viewMode === 'list' }"
            >
              <Table2 class="text-[11px]" aria-hidden="true" />
            </button>
            <button
              :aria-label="t('owner_meter_readings.view_grid')"
              type="button" @click="viewMode = 'grid'"
              :title="t('owner_meter_readings.view_grid')"
              class="icon-btn !w-8 !h-8"
              :class="{ '!bg-white dark:!bg-white/10': viewMode === 'grid' }"
            >
              <GripVertical class="text-[11px]" aria-hidden="true" />
            </button>
          </div>

          <!-- ===== تصدير CSV / طباعة كأيقونات — مطابق لنفس مكان أيقونات التصدير/الطباعة بصفحة إدارة المولدات ===== -->
          <button
            type="button"
            @click="exportCsv"
            :disabled="!readings.length"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5 disabled:opacity-40"
            :title="t('owner_meter_readings.export_button')"
          >
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </button>
          <button
            type="button"
            @click="printPage"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="t('owner_applications_page.print')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div v-if="approveError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ approveError }}</div>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="readings.length === 0" class="text-center py-10">
        <Gauge class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97]">{{ t("owner_meter_readings.no_matching_readings") }}</p>
      </div>

      <div v-else-if="viewMode === 'list'" class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[900px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_meter_readings.subscriber_col") }}</th>
              <th class="py-2.5 px-3">{{ t("owner_meter_readings.date_col") }}</th>
              <th class="py-2.5 px-3">{{ t("owner_meter_readings.previous_col") }}</th>
              <th class="py-2.5 px-3">{{ t("owner_meter_readings.current_col") }}</th>
              <th class="py-2.5 px-3">{{ t("owner_meter_readings.consumed_col") }}</th>
              <th class="py-2.5 px-3">{{ t("owner_meter_readings.image_col") }}</th>
              <th class="py-2.5 px-3">{{ t("owner_meter_readings.status_col") }}</th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_meter_readings.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="reading in readings" :key="reading.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
              :class="{ 'opacity-50 pointer-events-none': approvingId === reading.id || rejectingId === reading.id }"
            >
              <td class="py-2.5 px-3 font-bold">
                <button type="button" class="hover:underline decoration-dotted" @click="openDetails(reading)">
                  {{ reading.subscriber?.name ?? "-" }}
                </button>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ reading.reading_date }}</td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ reading.previous_reading }}</td>
              <td class="py-2.5 px-3 font-bold" dir="ltr">{{ reading.current_reading }}</td>
              <td class="py-2.5 px-3">
                <div class="flex items-center justify-center gap-1.5">
                  <span class="font-bold text-[#52733D] dark:text-[#8cc35a]" dir="ltr">{{ reading.consumed_kw }} kW</span>
                  <span
                    v-if="anomalyInfo(reading)"
                    class="text-[#D9534F]"
                    :title="anomalyInfo(reading).direction === 'high'
                      ? t('owner_meter_readings.anomaly_high', { percent: anomalyInfo(reading).percent })
                      : t('owner_meter_readings.anomaly_low', { percent: anomalyInfo(reading).percent })"
                  >
                    <TriangleAlert class="text-[11px]" aria-hidden="true" />
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-3">
                <a
                  v-if="reading.meter_image_url" :href="reading.meter_image_url" target="_blank" rel="noopener"
                  class="text-[#52733D] dark:text-[#8cc35a]"
                  :title="t('owner_meter_readings.view_image')"
                >
                  <Image aria-hidden="true" />
                </a>
                <span v-else class="text-[10.5px] text-[#9a9d97]">-</span>
              </td>
              <td class="py-2.5 px-3">
                <div class="flex items-center justify-center gap-1.5">
                  <span class="status-chip" :class="STATUS_META[reading.status]?.chip ?? 'chip-info'">{{ readingStatusLabel(reading.status) }}</span>
                  <button type="button" @click="printReading(reading)" :title="t('owner_meter_readings.print_action')" class="w-6 h-6 rounded-md flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#EBF1E7] dark:hover:bg-white/10">
                    <Printer class="text-[11px]" aria-hidden="true" />
                  </button>
                  <button type="button" @click="sendReading(reading)" :title="t('owner_meter_readings.send_action')" class="w-6 h-6 rounded-md flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#EBF1E7] dark:hover:bg-white/10">
                    <Send class="text-[11px]" aria-hidden="true" />
                  </button>
                </div>
              </td>
              <td class="py-2.5 px-3">
                <div v-if="reading.status === 'pending_approval'" class="flex items-center justify-center gap-1.5">
                  <button
                    type="button"
                    @click="handleApprove(reading)"
                    class="action-btn action-btn--approve"
                    :title="t('owner_meter_readings.approve_action')"
                  >
                    <Check aria-hidden="true" />
                  </button>
                  <button
                    type="button"
                    @click="openRejectModal(reading)"
                    class="action-btn action-btn--reject"
                    :title="t('owner_meter_readings.reject_action')"
                  >
                    <X aria-hidden="true" />
                  </button>
                </div>
                <span v-else class="text-[10.5px] text-[#9a9d97]">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ===== GRID VIEW ===== -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="reading in readings" :key="reading.id"
          class="rounded-xl border border-[#e7e2d6] dark:border-white/10 bg-white/60 dark:bg-white/5 p-3.5 space-y-2.5"
          :class="{ 'opacity-50 pointer-events-none': approvingId === reading.id || rejectingId === reading.id }"
        >
          <div class="flex items-start justify-between gap-2">
            <button type="button" class="font-bold text-[12.5px] hover:underline decoration-dotted text-start" @click="openDetails(reading)">
              {{ reading.subscriber?.name ?? "-" }}
            </button>
            <span class="status-chip shrink-0" :class="STATUS_META[reading.status]?.chip ?? 'chip-info'">{{ readingStatusLabel(reading.status) }}</span>
          </div>

          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-[11px]">
            <div>
              <span class="text-[#9a9d97]">{{ t("owner_meter_readings.date_col") }}: </span>
              <span dir="ltr">{{ reading.reading_date }}</span>
            </div>
            <div>
              <span class="text-[#9a9d97]">{{ t("owner_meter_readings.previous_col") }}: </span>
              <span dir="ltr">{{ reading.previous_reading }}</span>
            </div>
            <div>
              <span class="text-[#9a9d97]">{{ t("owner_meter_readings.current_col") }}: </span>
              <span class="font-bold" dir="ltr">{{ reading.current_reading }}</span>
            </div>
            <div class="flex items-center gap-1">
              <span class="text-[#9a9d97]">{{ t("owner_meter_readings.consumed_col") }}: </span>
              <span class="font-bold text-[#52733D] dark:text-[#8cc35a]" dir="ltr">{{ reading.consumed_kw }} kW</span>
              <TriangleAlert class="text-[#D9534F] text-[10px]" aria-hidden="true" v-if="anomalyInfo(reading)" 
                :title="anomalyInfo(reading).direction === 'high'
                  ? t('owner_meter_readings.anomaly_high', { percent: anomalyInfo(reading).percent })
                  : t('owner_meter_readings.anomaly_low', { percent: anomalyInfo(reading).percent })" />
            </div>
          </div>

          <a
            v-if="reading.meter_image_url" :href="reading.meter_image_url" target="_blank" rel="noopener"
            class="inline-flex items-center gap-1.5 text-[11px] text-[#52733D] dark:text-[#8cc35a]"
          >
            <Image aria-hidden="true" />{{ t("owner_meter_readings.view_image") }}
          </a>

          <div class="flex items-center justify-between gap-2 pt-2 border-t border-[#f0ece0] dark:border-white/10">
            <div class="flex items-center gap-1.5">
              <button type="button" @click="printReading(reading)" :title="t('owner_meter_readings.print_action')" class="w-7 h-7 rounded-md flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#EBF1E7] dark:hover:bg-white/10">
                <Printer class="text-[11px]" aria-hidden="true" />
              </button>
              <button type="button" @click="sendReading(reading)" :title="t('owner_meter_readings.send_action')" class="w-7 h-7 rounded-md flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#EBF1E7] dark:hover:bg-white/10">
                <Send class="text-[11px]" aria-hidden="true" />
              </button>
            </div>
            <div v-if="reading.status === 'pending_approval'" class="flex items-center gap-1.5">
              <button type="button" @click="handleApprove(reading)" class="action-btn action-btn--approve" :title="t('owner_meter_readings.approve_action')">
                <Check aria-hidden="true" />
              </button>
              <button type="button" @click="openRejectModal(reading)" class="action-btn action-btn--reject" :title="t('owner_meter_readings.reject_action')">
                <X aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
        <span>{{ t("owner_meter_readings.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchReadings(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
          <button :aria-label="t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchReadings(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
        </div>
      </div>
    </section>

    <!-- ===== ADD MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isFormOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isFormOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Gauge aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_meter_readings.add_modal_title") }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="isFormOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="queuedMessage" class="alert-box !text-[#a3760a] !bg-[#FFC107]/10 !border-[#FFC107]/25 text-center flex-col">
                <WifiOff class="text-lg mb-1 block" aria-hidden="true" />{{ queuedMessage }}
              </div>
              <div v-if="submitError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ submitError }}</div>

              <form v-if="!queuedMessage" @submit.prevent="handleSubmit" class="space-y-3">
                <div v-if="generators.length > 1">
                  <label class="field-label">{{ t("owner_meter_readings.generator_label") }}</label>
                  <AppDropdownSelect
                    v-model="selectedGeneratorId"
                    @update:model-value="handleGeneratorChange"
                    :options="generatorDropdownOptions"
                    variant="field"
                    width-class="w-full"
                  />
                </div>

                <div>
                  <label class="field-label">{{ t("owner_meter_readings.subscriber_label") }}</label>
                  <AppDropdownSelect
                    v-model="readingForm.subscription_id"
                    :options="subscriberDropdownOptions"
                    :placeholder="isLoadingSubscriptions ? t('common.loading') : t('owner_meter_readings.choose_subscriber')"
                    :disabled="isLoadingSubscriptions"
                    variant="field"
                    width-class="w-full"
                  />
                </div>

                <div>
                  <label class="field-label">{{ t("owner_meter_readings.reading_date_label") }}</label>
                  <input v-model="readingForm.reading_date" type="date" :max="new Date().toISOString().slice(0, 10)" required class="field-input" />
                </div>

                <div>
                  <label class="field-label">{{ t("owner_meter_readings.current_reading_label") }}</label>
                  <input v-model="readingForm.current_reading" type="number" step="0.01" min="0" required class="field-input font-mono" />
                </div>

                <div>
                  <label class="field-label">{{ t("owner_meter_readings.meter_image_label") }}</label>
                  <div v-if="!meterImagePreview" class="flex items-center gap-2">
                    <input
                      ref="meterImageInput" type="file" accept="image/*" capture="environment"
                      @change="onMeterImageChange"
                      class="field-input !py-1.5 text-[11px]"
                    />
                  </div>
                  <div v-else class="flex items-center gap-2.5">
                    <img :src="meterImagePreview" class="w-14 h-14 rounded-lg object-cover border border-[#e7e2d6]" />
                    <button type="button" @click="clearMeterImage" class="text-[11px] font-bold text-[#D9534F] hover:underline">
                      {{ t("owner_meter_readings.remove_image") }}
                    </button>
                  </div>
                  <p class="text-[10px] text-[#9a9d97] mt-1">{{ t("owner_meter_readings.meter_image_hint") }}</p>
                </div>
              </form>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="isFormOpen = false" class="btn-outline-brand">{{ queuedMessage ? t("common.close") : t("owner_meter_readings.cancel") }}</button>
              <button v-if="!queuedMessage" type="button" @click="handleSubmit" :disabled="isSubmitting || !readingForm.subscription_id" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Save aria-hidden="true" v-else />
                {{ isSubmitting ? t("owner_meter_readings.saving_ellipsis") : t("owner_meter_readings.save") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== REJECT MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isRejectModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isRejectModalOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Ban aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_meter_readings.reject_modal_title") }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="isRejectModalOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>
            <div class="p-5 space-y-3">
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5]">
                {{ t("owner_meter_readings.reject_confirm_message", { name: rejectTarget?.subscriber?.name ?? "-" }) }}
              </p>
              <div v-if="rejectError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ rejectError }}</div>
              <div>
                <label class="field-label">{{ t("owner_meter_readings.reject_reason_label") }}</label>
                <textarea
                  v-model="rejectReason" rows="3" required
                  :placeholder="t('owner_meter_readings.reject_reason_placeholder')"
                  class="field-input resize-none"
                ></textarea>
              </div>
            </div>
            <div class="modal-footer-brand">
              <button type="button" @click="isRejectModalOpen = false" class="btn-outline-brand">{{ t("owner_meter_readings.cancel") }}</button>
              <button
                type="button" @click="confirmReject"
                :disabled="!rejectReason.trim() || rejectingId === rejectTarget?.id"
                class="btn-fill-brand !bg-[#D9534F] !from-[#D9534F] !to-[#D9534F]"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="rejectingId === rejectTarget?.id" /><Ban aria-hidden="true" v-else />
                {{ t("owner_meter_readings.reject_action") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== BULK ENTRY MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isBulkFormOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isBulkFormOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><ListChecks aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_meter_readings.bulk_modal_title") }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="isBulkFormOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5 max-h-[70vh] overflow-y-auto">
              <div>
                <label class="field-label">{{ t("owner_meter_readings.generator_label") }}</label>
                <div class="w-full">
                  <AppDropdownSelect
                    v-model="bulkGeneratorId"
                    @update:model-value="handleBulkGeneratorChange"
                    :options="bulkGeneratorDropdownOptions"
                    variant="field"
                    width-class="w-full"
                    class="w-full"
                  />
                </div>
              </div>

              <div v-if="isLoadingSubscriptions" class="space-y-2">
                <div v-for="i in 4" :key="i" class="h-10 rounded-lg thumb-loading"></div>
              </div>

              <div v-else-if="!bulkRows.length" class="text-center py-6 text-[12px] text-[#9a9d97]">
                {{ t("owner_meter_readings.no_subscribers_for_generator") }}
              </div>

              <table v-else class="w-full text-[12px] border-separate border-spacing-0">
                <colgroup>
                  <col class="w-[42%]" />
                  <col class="w-[23%]" />
                  <col class="w-[35%]" />
                </colgroup>
                <thead>
                  <tr class="text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                    <th class="py-2.5 px-3 rounded-s-lg text-start">{{ t("owner_meter_readings.subscriber_col") }}</th>
                    <th class="py-2.5 px-3 text-center">{{ t("owner_meter_readings.previous_col") }}</th>
                    <th class="py-2.5 px-3 rounded-e-lg text-center">{{ t("owner_meter_readings.current_col") }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in bulkRows" :key="row.subscription_id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0">
                    <td class="py-2 px-3 font-bold text-start truncate">{{ row.subscriber_name }}</td>
                    <td class="py-2 px-3 text-center text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ row.previous_reading }}</td>
                    <td class="py-2 px-3">
                      <input
                        v-model="row.current_reading" type="number" step="0.01" min="0"
                        class="field-input font-mono !py-1.5 !px-2.5 w-full text-center"
                        dir="ltr"
                      />
                      <p v-if="bulkErrors[row.subscription_id]" class="text-[10px] text-[#D9534F] mt-1 text-center">{{ bulkErrors[row.subscription_id] }}</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="isBulkFormOpen = false" class="btn-outline-brand">{{ t("owner_meter_readings.cancel") }}</button>
              <button
                type="button" @click="submitBulkReadings"
                :disabled="isBulkSubmitting || !bulkRows.some((r) => String(r.current_reading).trim() !== '')"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isBulkSubmitting" /><Save aria-hidden="true" v-else />
                {{ isBulkSubmitting ? t("owner_meter_readings.saving_ellipsis") : t("owner_meter_readings.save_all") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== DETAILS / CHART MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isDetailsOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isDetailsOpen = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><ChartColumn aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ detailsReading?.subscriber?.name ?? "-" }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="isDetailsOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>
            <div class="p-5">
              <p class="text-[11.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] mb-3">
                {{ t("owner_meter_readings.monthly_consumption_title") }}
              </p>
              <div v-if="isLoadingHistory" class="flex items-end gap-2 h-40 px-1">
                <div v-for="i in 6" :key="i" class="flex-1 h-full rounded-t-md thumb-loading"></div>
              </div>
              <div v-else-if="detailsHistory.length" class="flex items-end gap-2 h-40 px-1">
                <div v-for="h in detailsHistory" :key="h.month" class="flex-1 flex flex-col items-center justify-end h-full gap-1">
                  <span class="text-[10px] font-bold text-[#52733D] dark:text-[#8cc35a]">{{ h.consumed_kw }}</span>
                  <div
                    class="w-full rounded-t-md bg-gradient-to-t from-[#3E582E] to-[#8cc35a]"
                    :style="{ height: barHeight(h.consumed_kw) + '%' }"
                  ></div>
                  <span class="text-[9.5px] text-[#9a9d97]" dir="ltr">{{ h.month }}</span>
                </div>
              </div>
              <div v-else class="text-center py-8 text-[12px] text-[#9a9d97]">
                {{ t("owner_meter_readings.no_history_data") }}
              </div>
            </div>
            <div class="modal-footer-brand">
              <button type="button" @click="isDetailsOpen = false" class="btn-outline-brand">{{ t("common.close") }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>