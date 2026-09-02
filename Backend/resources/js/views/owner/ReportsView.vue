<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useCommissionReports } from "@/composables/useCommissionReports";
import { usePermissions } from "@/composables/usePermissions";
import platformCommissionService from "@/services/platformCommissionService";
import ownerMonthlyReportService from "@/services/ownerMonthlyReportService";
import { vReveal } from "@/directives/reveal";
import { FileDown, FileText, Landmark, X } from "@lucide/vue";

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
const { t } = useI18n();

function statusLabel(status) {
  return t(`owner_reports.status.${status}`, status);
}

const STATUS_CHIP = {
  earned: "chip-warning",
  paid: "chip-success",
};

onMounted(() => {
  fetchCommissions();
  fetchSummary();
});
</script>

<template>
  <div class="space-y-5">
    <!-- ===== رأس الصفحة ===== -->
    <section v-reveal class="glass-card p-5 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-64 h-64 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base shrink-0">
            <Landmark aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-0.5">{{ t("owner_reports.eyebrow") }}</p>
            <h1 class="text-lg font-extrabold truncate">{{ t("owner_reports.title") }}</h1>
            <p class="text-[12px] mt-1">
              <span class="font-mono font-extrabold text-[#8A6D1F] dark:text-[#D4AF37]">{{ totalEarned.toFixed(2) }} ₪</span>
              <span class="text-[#6B6B6B] dark:text-[#a8aaa5]"> {{ t("owner_reports.currently_due") }}</span>
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2.5 shrink-0">
          <a v-if="hasRole('generator_owner')" :href="ownerMonthlyReportService.downloadUrl()" target="_blank" class="btn-fill-brand">
            <FileText aria-hidden="true" />
            {{ t("owner_reports.my_monthly_report_pdf") }}
          </a>
          <a v-if="hasRole('admin')" :href="platformCommissionService.downloadReportPdfUrl()" target="_blank" class="btn-fill-brand">
            <FileDown aria-hidden="true" />
            {{ t("owner_reports.download_full_report_pdf") }}
          </a>
        </div>
      </div>
    </section>

    <div v-if="markError" class="alert-box"><X class="shrink-0" aria-hidden="true" /> {{ markError }}</div>

    <!-- ===== حالة التحميل ===== -->
    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 6" :key="i" class="h-16 rounded-xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>
    </div>

    <!-- ===== حالة الخطأ ===== -->
    <div v-else-if="error" class="glass-card p-6 text-center text-[12.5px] text-[#D9534F]">
      {{ error }}
    </div>

    <!-- ===== حالة فارغة ===== -->
    <div v-else-if="commissions.length === 0" class="glass-card border-dashed p-12 text-center text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
      {{ t("owner_reports.empty") }}
    </div>

    <!-- ===== جدول العمولات ===== -->
    <section v-reveal v-else class="glass-card overflow-hidden">
      <table class="w-full text-[12.5px]">
        <thead>
          <tr class="border-b border-[#f0ece0] dark:border-white/5 text-[11px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5]">
            <th v-if="hasRole('admin')" class="text-start font-bold px-4 py-3">{{ t("owner_reports.owner_col") }}</th>
            <th class="text-start font-bold px-4 py-3">{{ t("owner_reports.generator_col") }}</th>
            <th class="text-start font-bold px-4 py-3">{{ t("owner_reports.commission_rate_col") }}</th>
            <th class="text-start font-bold px-4 py-3">{{ t("owner_reports.amount_col") }}</th>
            <th class="text-start font-bold px-4 py-3">{{ t("owner_reports.status_col") }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f0ece0] dark:divide-white/5">
          <tr
            v-for="commission in commissions"
            :key="commission.id"
            class="hover:bg-[#f4efe5]/40 dark:hover:bg-white/[.03] transition"
            :class="{ 'opacity-50 pointer-events-none': markingId === commission.id }"
          >
            <td v-if="hasRole('admin')" class="px-4 py-3 font-bold">{{ commission.owner?.name ?? "—" }}</td>
            <td class="px-4 py-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ commission.generator_name ?? "—" }}</td>
            <td class="px-4 py-3 font-mono text-[#6B6B6B] dark:text-[#a8aaa5]">{{ commission.commission_rate }}%</td>
            <td class="px-4 py-3 font-mono font-bold">{{ commission.commission_amount.toFixed(2) }} ₪</td>
            <td class="px-4 py-3">
              <span class="status-chip" :class="STATUS_CHIP[commission.status]">{{ statusLabel(commission.status) }}</span>
            </td>
            <td class="px-4 py-3 text-end">
              <button
                v-if="hasRole('admin') && commission.status === 'earned'"
                type="button"
                @click="markAsPaid(commission.id)"
                class="text-[11.5px] font-bold text-[#3E582E] dark:text-[#8cc35a] hover:underline"
              >
                {{ t("owner_reports.mark_paid") }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- ===== ترقيم الصفحات ===== -->
    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 pt-1">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchCommissions(page)"
        class="w-9 h-9 rounded-full text-[12.5px] font-mono font-bold transition"
        :class="
          page === pagination.current_page
            ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
            : 'bg-[#f4efe5]/70 dark:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/10'
        "
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>