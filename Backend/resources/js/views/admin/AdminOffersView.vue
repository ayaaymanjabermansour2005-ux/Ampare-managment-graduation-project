<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminOffers } from "@/composables/useAdminOffers";
import offerService from "@/services/offerService";
import { useConfirm } from "@/composables/useConfirm";
import { vReveal } from "@/directives/reveal";
import { useToastStore } from "@/stores/toast";
import { ArrowRight, Ban, ChevronLeft, ChevronRight, Eye, FileSpreadsheet, LoaderCircle, Printer, Search, Tags, Trash2, UserRound, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t, locale } = useI18n();
const { confirm } = useConfirm();
const toast = useToastStore();

const {
  offers, pagination, isLoading, error,
  search,
  cancellingId, cancelError,
  deletingId,
  fetchOffers, onSearchInput,
  cancelOffer, deleteOffer,
} = useAdminOffers();

/* ---------------- إصلاح: علامة النسبة المئوية كانت "%" ثابتة بالإنجليزي
   حتى بالواجهة العربية — نفس منهجية صفحة الأعطال (percentSign) ---------------- */
function percentSign() {
  return locale.value === "ar" ? "٪" : "%";
}
function discountLabel(o) {
  if (o.discount_type === "percentage") return `${o.discount_value}${percentSign()}`;
  return `${o.discount_value} ₪`;
}

const TARGET_LABELS = {
  all: "offers_page.target_everyone",
  beneficiary: "offers_page.target_beneficiary_group",
  selected: "offers_page.target_selected_subscribers",
};
function targetLabel(mode) {
  const key = TARGET_LABELS[mode];
  return key ? t(key) : mode;
}

function isExpired(o) {
  return o.end_date && new Date(o.end_date) < new Date();
}

/* ---------------- حالة العرض الفعلية ---------------- */
const STATUS_META = {
  active: { chip: "chip-success", key: "offers_page.status_active" },
  expired: { chip: "chip-warning", key: "subscriptions_page.derived_expired" },
  cancelled: { chip: "chip-danger", key: "offers_page.status_cancelled" },
};
function effectiveStatus(o) {
  if (o.status !== "active") return "cancelled";
  return isExpired(o) ? "expired" : "active";
}
function statusLabel(s) {
  const m = STATUS_META[s];
  return m ? t(m.key) : s;
}
const STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  ...Object.entries(STATUS_META).map(([value, m]) => ({ value, label: t(m.key) })),
]);
const statusFilter = ref("");

const filteredOffers = computed(() => {
  if (!statusFilter.value) return offers.value;
  return offers.value.filter((o) => effectiveStatus(o) === statusFilter.value);
});

/* ---------------- KPI Cards ----------------
 * "إجمالي العروض" دقيق دايمًا (من pagination.total)، وباقي البطاقات محسوبة من
 * offers.value يعني الصفحة المحمّلة حاليًا بس (نفس منهجية صفحة قراءات العدادات).
 */
const countOnPage = (status) => offers.value.filter((o) => effectiveStatus(o) === status).length;
const KPI_CARDS = computed(() => [
  { icon: "fa-tags", label: t("offers_page.total_offers"), value: pagination.value.total, c1: "#8A6D1F", c2: "#D4AF37" },
  { icon: "fa-circle-check", label: statusLabel("active") + t("common.this_page_suffix"), value: countOnPage("active"), c1: "#28A745", c2: "#1f7a37" },
  { icon: "fa-hourglass-half", label: statusLabel("expired") + t("common.this_page_suffix"), value: countOnPage("expired"), c1: "#FFC107", c2: "#a3760a" },
  { icon: "fa-ban", label: statusLabel("cancelled") + t("common.this_page_suffix"), value: countOnPage("cancelled"), c1: "#D9534F", c2: "#8A6D1F" },
]);

async function handleCancel(o) {
  const confirmed = await confirm({
    title: t("offers_page.cancel_offer_title"),
    message: t("offers_page.cancel_offer_message", { title: o.title }),
    confirmLabel: t("offers_page.cancel_offer_title"),
    variant: "danger",
  });
  if (!confirmed) return;
  await cancelOffer(o.id);
  if (cancelError.value) toast.show({ type: "danger", title: cancelError.value });
}

async function handleDelete(o) {
  const confirmed = await confirm({
    title: t("offers_page.delete_offer_title"),
    message: t("offers_page.delete_offer_message", { title: o.title }),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;
  await deleteOffer(o.id);
}

/* ---------------- نافذة عرض تفاصيل العرض ---------------- */
const viewingOffer = ref(null);
function openView(o) {
  viewingOffer.value = o;
}

const exportUrl = computed(() => offerService.exportUrl({ search: search.value }));

function handlePrint() {
  window.print();
}

onMounted(() => fetchOffers(1));
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("offers_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#D4AF37] to-[#8A6D1F] text-white flex items-center justify-center text-base">
              <Tags aria-hidden="true" />
            </span>
            {{ $t("offers_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("offers_page.subtitle") }}
          </p>
        </div>
        <div class="relative flex flex-wrap gap-2.5 print-hidden">
          <a
            :href="exportUrl"
            target="_blank"
            class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
          >
            <FileSpreadsheet aria-hidden="true" />
            {{ $t("offers_page.export_excel") }}
          </a>
          <button
            type="button"
            @click="handlePrint"
            class="icon-btn !w-10 !h-10 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="$t('offers_page.print')"
          >
            <Printer class="text-[13px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal class="print-hidden">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <div
          v-for="c in KPI_CARDS"
          :key="c.label"
          class="kpi-card glass-card hoverable"
          :style="{ '--kpi-color': c.c1, '--kpi-color2': c.c2 }"
        >
          <div class="kpi-icon mb-2.5"><AppIcon :name="c.icon" /></div>
          <div class="text-lg font-extrabold">{{ c.value }}</div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.label }}</div>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4 print-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="search" type="text" @input="onSearchInput"
            :placeholder="$t('offers_page.search_placeholder')"
            class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
          />
        </div>
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
            @click="statusFilter = pill.value"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >{{ pill.label }}</button>
        </div>
      </div>
    </section>

    <!-- ===== GRID ===== -->
    <section v-reveal>
      <div v-if="isLoading" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
        <div v-for="i in 6" :key="i" class="h-40 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else-if="error" class="glass-card p-8 text-center text-[12px] text-[#D9534F]">{{ error }}</div>
      <div v-else-if="filteredOffers.length === 0" class="glass-card p-10 text-center">
        <Tags class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("offers_page.no_matching_offers") }}</p>
      </div>
      <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
        <div v-for="o in filteredOffers" :key="o.id" class="glass-card hoverable p-4">
          <div class="flex items-start justify-between mb-2.5">
            <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#D4AF37] to-[#8A6D1F] text-white flex items-center justify-center shrink-0"><Tags class="text-[12px]" aria-hidden="true" /></span>
            <span class="status-chip" :class="STATUS_META[effectiveStatus(o)]?.chip">{{ statusLabel(effectiveStatus(o)) }}</span>
          </div>

          <button type="button" @click="openView(o)" class="text-start block w-full">
            <h3 class="font-bold text-[13px] mb-1 hover:text-[#8A6D1F] transition-colors">{{ o.title }}</h3>
          </button>
          <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-3 line-clamp-2">{{ o.description }}</p>

          <div class="flex items-center justify-between text-[11px] mb-3">
            <span class="font-extrabold text-[#8A6D1F]">{{ discountLabel(o) }} {{ $t("offers_page.off_suffix") }}</span>
            <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ targetLabel(o.target_mode) }}</span>
          </div>
          <div class="flex items-center justify-between text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-3">
            <span><UserRound aria-hidden="true" /> {{ o.owner?.name ?? "—" }}</span>
            <span dir="ltr">{{ o.start_date }} <ArrowRight aria-hidden="true" /> {{ o.end_date }}</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button" @click="openView(o)"
              class="w-9 h-9 rounded-full flex items-center justify-center text-[#17A2B8] hover:bg-[#17A2B8]/10 disabled:opacity-40 shrink-0"
              :title="$t('common.view')"
            >
              <Eye aria-hidden="true" style="font-size:11px" />
            </button>
            <button
              v-if="o.status === 'active'"
              type="button" @click="handleCancel(o)" :disabled="cancellingId === o.id"
              class="flex-1 text-[10.5px] font-bold px-3 py-2 rounded-full border border-[#D9534F]/40 text-[#D9534F] hover:bg-[#D9534F]/10 disabled:opacity-50"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === o.id" /><Ban aria-hidden="true" v-else />
              {{ $t("dashboard.cancel") }}
            </button>
            <button
              type="button" @click="handleDelete(o)" :disabled="deletingId === o.id"
              class="w-9 h-9 rounded-full flex items-center justify-center text-[#D9534F] hover:bg-[#D9534F]/10 disabled:opacity-40 shrink-0"
              :title="$t('common.delete')"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === o.id" style="font-size:11px" /><Trash2 aria-hidden="true" v-else style="font-size:11px" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-4 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
        <span>{{ $t("offers_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchOffers(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
          <button :aria-label="$t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchOffers(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===================== نافذة عرض تفاصيل العرض ===================== -->
    <Teleport to="body">
      <div
        v-if="viewingOffer"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
        @click.self="viewingOffer = null"
      >
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Tags aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ viewingOffer.title }}</h3>
                <p class="modal-head-brand__subtitle">{{ statusLabel(effectiveStatus(viewingOffer)) }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="viewingOffer = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5">
          <p v-if="viewingOffer.description" class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-4 leading-relaxed">
            {{ viewingOffer.description }}
          </p>

          <div class="glass-card p-4 space-y-2.5 text-[12px]">
            <div class="flex justify-between gap-3">
              <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("offers_page.discount_label") }}</span>
              <b class="text-[#8A6D1F]">{{ discountLabel(viewingOffer) }}</b>
            </div>
            <div class="flex justify-between gap-3">
              <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("offers_page.target_label") }}</span>
              <b>{{ targetLabel(viewingOffer.target_mode) }}</b>
            </div>
            <div class="flex justify-between gap-3">
              <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("offers_page.owner_label") }}</span>
              <b class="truncate max-w-[10rem]">{{ viewingOffer.owner?.name ?? "—" }}</b>
            </div>
            <div class="flex justify-between gap-3">
              <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscriptions_page.start_date_col") }}</span>
              <b>{{ viewingOffer.start_date ?? "—" }}</b>
            </div>
            <div class="flex justify-between gap-3">
              <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("offers_page.end_date_label") }}</span>
              <b>{{ viewingOffer.end_date ?? "—" }}</b>
            </div>
          </div>
          </div>

          <div class="modal-footer-brand">
            <button
              type="button"
              @click="viewingOffer = null"
              class="btn-outline-brand"
            >
              {{ $t("common.close") }}
            </button>
            <button
              v-if="viewingOffer.status === 'active'"
              type="button"
              @click="handleCancel(viewingOffer); viewingOffer = null"
              :disabled="cancellingId === viewingOffer.id"
              class="btn-fill-brand btn-fill-brand--danger"
            >
              <Ban aria-hidden="true" />
              {{ $t("offers_page.cancel_offer_title") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>