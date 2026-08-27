<script setup>
import { onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCommissionReports } from "@/composables/useCommissionReports";
import { usePermissions } from "@/composables/usePermissions";
import platformCommissionService from "@/services/platformCommissionService";
import { vReveal } from "@/directives/reveal";
import { ChevronLeft, ChevronRight, CircleAlert, FileDown, HandCoins, PlugZap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();

const {
  commissions,
  pagination,
  isLoading,
  error,
  totalEarned,
  fetchSummary,
  fetchCommissions,
  markingId,
  markError,
  markAsPaid,
} = useCommissionReports();
const { hasRole } = usePermissions();

const STATUS_META = {
  earned: { chip: "chip-warning", key: "reports_page.status_earned" },
  paid: { chip: "chip-success", key: "dashboard.paid" },
};
function statusLabel(s) {
  const m = STATUS_META[s];
  return m ? t(m.key) : s;
}

/* ---------------- KPI Cards (نفس منهجية صفحة الأعطال — الإجمالي دقيق، الباقي لهذه الصفحة فقط) ---------------- */
const countOnPage = (status) => commissions.value.filter((c) => c.status === status).length;
const paidAmountOnPage = computed(() =>
  commissions.value.filter((c) => c.status === "paid").reduce((sum, c) => sum + c.commission_amount, 0)
);
const KPI_CARDS = computed(() => [
  { icon: "fa-sack-dollar", label: t("reports_page.total_currently_earned"), value: `${totalEarned.value.toFixed(2)} ₪`, c1: "#8A6D1F", c2: "#5c4a15" },
  { icon: "fa-hourglass-half", label: statusLabel("earned") + t("common.this_page_suffix"), value: countOnPage("earned"), c1: "#FFC107", c2: "#a3760a" },
  { icon: "fa-circle-check", label: statusLabel("paid") + t("common.this_page_suffix"), value: countOnPage("paid"), c1: "#28A745", c2: "#1f7a37" },
  { icon: "fa-coins", label: t("reports_page.paid_amount_this_page"), value: `${paidAmountOnPage.value.toFixed(2)} ₪`, c1: "#3E582E", c2: "#52733D" },
]);

/* ---------------- Pagination بأزرار محدودة (نفس منهجية صفحة الأعطال) ---------------- */
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

onMounted(() => {
  fetchCommissions();
  fetchSummary();
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
        <ChevronRight class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronLeft class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("reports_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#5c4a15] text-white flex items-center justify-center text-base">
              <HandCoins aria-hidden="true" />
            </span>
            {{ $t("reports_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("reports_page.subtitle") }}
          </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <a
            :href="platformCommissionService.exportUrl()"
            target="_blank"
            class="btn-outline-brand relative inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-[12.5px] font-bold shrink-0"
          >
            <FileDown class="text-[11px]" aria-hidden="true" />
            {{ $t("reports_page.export_excel") }}
          </a>
          <a
            v-if="hasRole('admin')"
            :href="platformCommissionService.downloadReportPdfUrl()"
            target="_blank"
            class="btn-fill relative inline-flex items-center gap-2 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white px-4 py-2.5 rounded-full text-[12.5px] font-bold shadow-md shrink-0"
          >
            <FileDown class="text-[11px]" aria-hidden="true" />
            {{ $t("reports_page.download_full_report") }}
          </a>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
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

    <div v-if="markError" v-reveal class="glass-card p-4 text-[12.5px] text-[#D9534F] border border-[#D9534F]/30">
      <CircleAlert class="me-1.5" aria-hidden="true" />{{ markError }}
    </div>

    <!-- ===== LIST ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ $t("reports_page.commissions_list") }}</h3>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="commissions.length === 0" class="text-center py-12">
        <HandCoins class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("reports_page.no_commissions_yet") }}</p>
      </div>

      <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
        <div
          v-for="commission in commissions"
          :key="commission.id"
          class="flex items-center gap-3.5 py-3.5 px-1.5"
          :class="{ 'opacity-50 pointer-events-none': markingId === commission.id }"
        >
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8A6D1F] to-[#5c4a15] flex items-center justify-center shrink-0 text-white">
            <PlugZap class="text-[12px]" aria-hidden="true" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-[12.5px] font-bold truncate">{{ commission.generator_name ?? "—" }}</p>
              <span class="status-chip shrink-0" :class="STATUS_META[commission.status]?.chip">{{ statusLabel(commission.status) }}</span>
            </div>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 truncate">
              <template v-if="hasRole('admin')">{{ commission.owner?.name ?? "—" }} · </template>
              {{ $t("owners_page.commission_col") }} {{ commission.commission_rate }}%
            </p>
          </div>

          <div class="text-end shrink-0">
            <p class="text-[13px] font-extrabold font-mono">{{ commission.commission_amount.toFixed(2) }} ₪</p>
            <button
              v-if="hasRole('admin') && commission.status === 'earned'"
              type="button"
              @click="markAsPaid(commission.id)"
              class="text-[11px] font-bold text-[#3E582E] dark:text-[#a8d19a] hover:underline mt-0.5"
            >
              {{ $t("reports_page.mark_as_paid") }}
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination بأزرار محدودة -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("article_comments_page.pagination_text", { current: pagination.current_page, last: pagination.last_page }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="pagination.current_page <= 1"
            @click="fetchCommissions(pagination.current_page - 1)"
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
              @click="fetchCommissions(page)"
              class="w-7 h-7 rounded-lg text-[11px] font-mono font-bold transition-colors"
              :class="
                page === pagination.current_page
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                  : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
              "
            >
              {{ page }}
            </button>
          </template>

          <button :aria-label="$t('common.next_page')"
            type="button"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchCommissions(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>
  </div>
</template>