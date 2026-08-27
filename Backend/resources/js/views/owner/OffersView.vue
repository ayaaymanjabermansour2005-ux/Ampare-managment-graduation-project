<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useOwnerOffers } from "@/composables/useOwnerOffers";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Ban, ChevronLeft, ChevronRight, CircleAlert, GripVertical, LoaderCircle, Pencil, Plus, Printer, Save, Search, Table2, Tags, X, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const {
  offers,
  pagination,
  isLoading,
  error,
  search,
  fetchOffers,
  onSearchInput,
  mySubscribers,
  loadMySubscribers,
  isSaving,
  saveError,
  createOffer,
  updateOffer,
  cancellingId,
  cancelError,
  cancelOffer,
} = useOwnerOffers();

const { confirm } = useConfirm();
const toast = useToastStore();
const { t } = useI18n();

const DISCOUNT_TYPE_OPTIONS = computed(() => [
  { value: "percentage", label: t("owner_offers.discount_percentage") },
  { value: "fixed", label: t("owner_offers.discount_fixed") },
]);
const TARGET_MODE_OPTIONS = computed(() => [
  { value: "all", label: t("owner_offers.target.all") },
  { value: "beneficiary", label: t("owner_offers.target.beneficiary") },
  { value: "selected", label: t("owner_offers.target.selected") },
]);
const BENEFICIARY_TYPE_OPTIONS = computed(() => [
  { value: "normal", label: t("owner_offers.beneficiary.normal") },
  { value: "special", label: t("owner_offers.beneficiary.special") },
]);

const STATUS_META = { active: "chip-success", cancelled: "chip-danger", expired: "chip-info" };
function statusLabel(status) {
  return t(`owner_offers.status.${status}`, status);
}
function targetLabel(mode) {
  return t(`owner_offers.target.${mode}`, mode);
}
function beneficiaryLabel(type) {
  return t(`owner_offers.beneficiary.${type}`, type);
}

/* ---------------- فلترة حسب الحالة (على العروض المُحمّلة بالصفحة الحالية) ---------------- */
const statusFilter = ref("");
const STATUS_PILLS = computed(() => [
  { v: "", l: t("common.all") },
  { v: "active", l: statusLabel("active") },
  { v: "expired", l: statusLabel("expired") },
  { v: "cancelled", l: statusLabel("cancelled") },
]);
const filteredOffers = computed(() =>
  statusFilter.value ? offers.value.filter((o) => o.status === statusFilter.value) : offers.value,
);

/* ---------------- بطاقات KPI (على العروض المُحمّلة بالصفحة الحالية) ---------------- */
const KPI_CARDS = computed(() => {
  const total = offers.value.length;
  const active = offers.value.filter((o) => o.status === "active").length;
  const expired = offers.value.filter((o) => o.status === "expired").length;
  const cancelled = offers.value.filter((o) => o.status === "cancelled").length;
  return [
    { icon: "fa-tags", label: t("owner_offers.total_offers"), value: total, sub: t("common.this_page_suffix"), c1: "#52733D", c2: "#3E582E" },
    { icon: "fa-circle-check", label: statusLabel("active"), value: active, sub: total > 0 ? `${Math.round((active / total) * 100)}%` : "-", c1: "#28A745", c2: "#1f7a37" },
    { icon: "fa-hourglass-end", label: statusLabel("expired"), value: expired, sub: t("common.this_page_suffix"), c1: "#17A2B8", c2: "#117d8f" },
    { icon: "fa-ban", label: statusLabel("cancelled"), value: cancelled, sub: t("common.this_page_suffix"), c1: "#D9534F", c2: "#8A6D1F" },
  ];
});

/* ---------------- عرض جدول/شبكة ---------------- */
const viewMode = ref("table");

/* ---------------- طباعة ---------------- */
function printPage() {
  window.print();
}

/* ===== إنشاء عرض ===== */
const showForm = ref(false);
const form = reactive({
  title: "",
  description: "",
  discount_type: "percentage",
  discount_value: "",
  target_mode: "all",
  beneficiary_type: "normal",
  subscriber_ids: [],
  start_date: new Date().toISOString().slice(0, 10),
  end_date: "",
});

function openForm() {
  showForm.value = true;
  saveError.value = null;
  if (mySubscribers.value.length === 0) loadMySubscribers();
  Object.assign(form, {
    title: "",
    description: "",
    discount_type: "percentage",
    discount_value: "",
    target_mode: "all",
    beneficiary_type: "normal",
    subscriber_ids: [],
    start_date: new Date().toISOString().slice(0, 10),
    end_date: "",
  });
}

async function handleSubmit() {
  const payload = { ...form };
  if (payload.target_mode !== "beneficiary") delete payload.beneficiary_type;
  if (payload.target_mode !== "selected") delete payload.subscriber_ids;

  const ok = await createOffer(payload);
  if (ok) {
    showForm.value = false;
    toast.show({
      type: "success",
      title: t("owner_offers.created_toast_title"),
      message: t("owner_offers.created_toast_message", { title: form.title }),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_offers.save_failed_title"),
      message: saveError.value?.message ?? t("owner_offers.create_error"),
    });
  }
}

/* ===== تعديل عرض (الحقول المسموح تعديلها فقط: العنوان/الوصف/قيمة الخصم/التواريخ) ===== */
const editingOffer = ref(null);
const editForm = reactive({ title: "", description: "", discount_value: "", start_date: "", end_date: "" });

function openEdit(offer) {
  editingOffer.value = offer;
  saveError.value = null;
  Object.assign(editForm, {
    title: offer.title,
    description: offer.description ?? "",
    discount_value: offer.discount_value,
    start_date: offer.start_date,
    end_date: offer.end_date,
  });
}

async function handleSaveEdit() {
  const ok = await updateOffer(editingOffer.value.id, { ...editForm });
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_offers.updated_toast_title"),
      message: t("owner_offers.updated_toast_message"),
    });
    editingOffer.value = null;
  } else {
    toast.show({
      type: "danger",
      title: t("owner_offers.save_failed_title"),
      message: saveError.value?.message ?? t("owner_offers.update_error"),
    });
  }
}

async function handleCancel(offer) {
  const confirmed = await confirm({
    title: t("owner_offers.cancel_confirm_title", { title: offer.title }),
    message: t("owner_offers.cancel_confirm_message"),
    confirmLabel: t("owner_offers.cancel_action"),
    variant: "danger",
  });
  if (!confirmed) return;

  const ok = await cancelOffer(offer.id);
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_offers.cancelled_toast_title"),
      message: t("owner_offers.cancelled_toast_message", { title: offer.title }),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_offers.cancel_failed_title"),
      message: cancelError.value ?? t("owner_offers.cancel_error"),
    });
  }
}

onMounted(() => fetchOffers());
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_offers.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">{{ t("owner_offers.eyebrow") }}</p>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <Tags aria-hidden="true" />
            </span>
            {{ t("owner_offers.title") }}
          </h1>
        </div>
        <button
          type="button" @click="openForm"
          class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
        >
          <Plus aria-hidden="true" />
          {{ t("owner_offers.add_button") }}
        </button>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div v-if="isLoading" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div v-for="i in 4" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
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
              :placeholder="t('owner_offers.search_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in STATUS_PILLS" :key="pill.v" type="button"
              @click="statusFilter = pill.v"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === pill.v ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.l }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button :aria-label="t('common.view_as_table')" type="button" @click="viewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'table' }"><Table2 class="text-[11px]" aria-hidden="true" /></button>
            <button :aria-label="t('common.view_as_grid')" type="button" @click="viewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'grid' }"><GripVertical class="text-[11px]" aria-hidden="true" /></button>
          </div>
          <button
            type="button" @click="printPage"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="t('owner_applications_page.print')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE / GRID ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 4" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="offers.length === 0" class="text-center py-12">
        <Tags class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97]">{{ t("owner_offers.no_offers") }}</p>
      </div>

      <div v-else-if="filteredOffers.length === 0" class="text-center py-12">
        <ZoomOut class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97]">{{ t("owner_offers.no_matching_offers") }}</p>
      </div>

      <template v-else>
        <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[860px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_offers.title_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_offers.discount_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_offers.target_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_offers.period_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_offers.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_offers.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="offer in filteredOffers" :key="offer.id"
                class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center hover:bg-[#f4efe5]/40 dark:hover:bg-white/5 transition-colors"
                :class="{ 'opacity-50 pointer-events-none': cancellingId === offer.id }"
              >
                <td class="py-2.5 px-3">
                  <div class="font-bold">{{ offer.title }}</div>
                  <div v-if="offer.description" class="text-[10px] text-[#9a9d97] truncate max-w-[14rem] mx-auto">{{ offer.description }}</div>
                </td>
                <td class="py-2.5 px-3 font-bold" dir="ltr">
                  {{ offer.discount_value }}{{ offer.discount_type === "percentage" ? "%" : " ₪" }}
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">
                  {{ targetLabel(offer.target_mode) }}
                  <span v-if="offer.beneficiary_type">({{ beneficiaryLabel(offer.beneficiary_type) }})</span>
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ offer.start_date }} — {{ offer.end_date }}</td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="STATUS_META[offer.status] ?? 'chip-info'">{{ statusLabel(offer.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div class="row-actions">
                    <template v-if="offer.status === 'active'">
                      <button type="button" @click="openEdit(offer)" class="action-btn action-btn--edit" :title="t('common.edit')"><Pencil aria-hidden="true" /></button>
                      <span class="row-actions-divider"></span>
                      <button type="button" @click="handleCancel(offer)" :disabled="cancellingId === offer.id" class="action-btn action-btn--delete" :title="t('owner_offers.cancel_action')">
                        <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === offer.id" /><Ban aria-hidden="true" v-else />
                      </button>
                    </template>
                    <span v-else class="text-[10.5px] text-[#9a9d97]">-</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <div
            v-for="offer in filteredOffers" :key="offer.id"
            class="glass-card p-3.5"
            :class="{ 'opacity-50 pointer-events-none': cancellingId === offer.id }"
          >
            <div class="flex items-start justify-between mb-2.5">
              <div class="min-w-0">
                <div class="font-bold text-[12.5px] truncate">{{ offer.title }}</div>
                <div v-if="offer.description" class="text-[10px] text-[#9a9d97] truncate">{{ offer.description }}</div>
              </div>
              <span class="status-chip shrink-0" :class="STATUS_META[offer.status] ?? 'chip-info'">{{ statusLabel(offer.status) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] mb-2.5">
              <div>
                <span class="text-[#9a9d97]">{{ t("owner_offers.discount_col") }}:</span>
                <b dir="ltr">{{ offer.discount_value }}{{ offer.discount_type === "percentage" ? "%" : " ₪" }}</b>
              </div>
              <div>
                <span class="text-[#9a9d97]">{{ t("owner_offers.target_col") }}:</span>
                <b>{{ targetLabel(offer.target_mode) }}</b>
              </div>
            </div>
            <div class="text-[10.5px] text-[#9a9d97] mb-3" dir="ltr">{{ offer.start_date }} — {{ offer.end_date }}</div>
            <div class="flex items-center justify-end">
              <div class="row-actions">
                <template v-if="offer.status === 'active'">
                  <button type="button" @click="openEdit(offer)" class="action-btn action-btn--edit" :title="t('common.edit')"><Pencil aria-hidden="true" /></button>
                  <span class="row-actions-divider"></span>
                  <button type="button" @click="handleCancel(offer)" :disabled="cancellingId === offer.id" class="action-btn action-btn--delete" :title="t('owner_offers.cancel_action')">
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === offer.id" /><Ban aria-hidden="true" v-else />
                  </button>
                </template>
                <span v-else class="text-[10.5px] text-[#9a9d97]">-</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
        <span>{{ t("owner_offers.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchOffers(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchOffers(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
        </div>
      </div>
    </section>

    <!-- ===== CREATE MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showForm = false">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Tags aria-hidden="true" /></span>
                <div class="min-w-0"><h3 class="modal-head-brand__title">{{ t("owner_offers.add_modal_title") }}</h3></div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="showForm = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <form @submit.prevent="handleSubmit" class="p-5 space-y-3.5">
              <div v-if="saveError?.message" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveError.message }}</div>

              <div>
                <label class="field-label">{{ t("owner_offers.title_label") }}</label>
                <input v-model="form.title" type="text" required maxlength="191" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t("owner_offers.description_label") }} <span class="text-[#9a9d97] font-normal">({{ t("owner_technicians.optional") }})</span></label>
                <textarea v-model="form.description" rows="2" maxlength="2000" class="field-input resize-none"></textarea>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="field-label">{{ t("owner_offers.discount_type_label") }}</label>
                  <AppDropdownSelect
                    v-model="form.discount_type"
                    :options="DISCOUNT_TYPE_OPTIONS"
                    variant="field" width-class="w-full" match-trigger-width
                  />
                </div>
                <div>
                  <label class="field-label">{{ t("owner_offers.discount_value_label") }}</label>
                  <input
                    v-model="form.discount_value" type="number" step="0.01" min="0.01"
                    :max="form.discount_type === 'percentage' ? 100 : undefined"
                    required class="field-input" dir="ltr"
                  />
                  <p v-if="saveError?.errors?.discount_value" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.discount_value[0] }}</p>
                </div>
              </div>

              <div>
                <label class="field-label">{{ t("owner_offers.target_label") }}</label>
                <AppDropdownSelect
                  v-model="form.target_mode"
                  :options="TARGET_MODE_OPTIONS"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>

              <div v-if="form.target_mode === 'beneficiary'">
                <label class="field-label">{{ t("owner_offers.beneficiary_label") }}</label>
                <AppDropdownSelect
                  v-model="form.beneficiary_type"
                  :options="BENEFICIARY_TYPE_OPTIONS"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>

              <div v-if="form.target_mode === 'selected'">
                <label class="field-label">{{ t("owner_offers.select_subscribers_label") }}</label>
                <div class="border border-[#e7e2d6] dark:border-white/10 rounded-lg divide-y divide-[#e7e2d6] dark:divide-white/10 max-h-40 overflow-y-auto">
                  <label v-for="s in mySubscribers" :key="s.id" class="flex items-center gap-2 p-2.5 cursor-pointer hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition-colors">
                    <input v-model="form.subscriber_ids" type="checkbox" :value="s.id" class="accent-[#52733D]" />
                    <span class="text-[12px]">{{ s.name }}</span>
                  </label>
                </div>
                <p v-if="saveError?.errors?.subscriber_ids" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.subscriber_ids[0] }}</p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="field-label">{{ t("owner_offers.start_date_label") }}</label>
                  <input v-model="form.start_date" type="date" required class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ t("owner_offers.end_date_label") }}</label>
                  <input v-model="form.end_date" type="date" required class="field-input" />
                </div>
              </div>

              <div class="modal-footer-brand !px-0 !pb-0">
                <button type="button" @click="showForm = false" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
                <button type="submit" :disabled="isSaving" class="btn-fill-brand">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Plus aria-hidden="true" v-else />
                  {{ isSaving ? t("owner_technicians.adding_ellipsis") : t("owner_offers.create_button") }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===== EDIT MODAL ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="editingOffer" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="editingOffer = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--gold">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Pencil aria-hidden="true" /></span>
                <div class="min-w-0"><h3 class="modal-head-brand__title truncate">{{ editingOffer.title }}</h3></div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="editingOffer = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="saveError?.message" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveError.message }}</div>

              <div>
                <label class="field-label">{{ t("owner_offers.title_label") }}</label>
                <input v-model="editForm.title" type="text" maxlength="191" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t("owner_offers.description_label") }}</label>
                <textarea v-model="editForm.description" rows="2" maxlength="2000" class="field-input resize-none"></textarea>
              </div>
              <div>
                <label class="field-label">{{ t("owner_offers.discount_value_label") }}</label>
                <input v-model="editForm.discount_value" type="number" step="0.01" min="0.01" class="field-input" dir="ltr" />
                <p v-if="saveError?.errors?.discount_value" class="text-[11px] text-[#D9534F] mt-1">{{ saveError.errors.discount_value[0] }}</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="field-label">{{ t("owner_offers.start_date_label") }}</label>
                  <input v-model="editForm.start_date" type="date" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ t("owner_offers.end_date_label") }}</label>
                  <input v-model="editForm.end_date" type="date" class="field-input" />
                </div>
              </div>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="editingOffer = null" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
              <button type="button" @click="handleSaveEdit" :disabled="isSaving" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
                {{ isSaving ? t("owner_technicians.saving_ellipsis") : t("owner_technicians.save") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>