<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useCommissionReports } from "@/composables/useCommissionReports";
import { usePermissions } from "@/composables/usePermissions";
import platformCommissionService from "@/services/platformCommissionService";
import ownerMonthlyReportService from "@/services/ownerMonthlyReportService";
import { FileDown, FileText } from "@lucide/vue";

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
const STATUS_TONES = {
  earned: "bg-warning-bg text-warning",
  paid: "bg-success-bg text-success",
};

onMounted(() => {
  fetchCommissions();
  fetchSummary();
});
</script>

<template>
  <div class="space-y-6">
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
    >
      <div>
        <p class="text-xs font-medium text-secondary-600 tracking-wide mb-1">
          {{ t("owner_reports.eyebrow") }}
        </p>
        <h1 class="text-2xl font-semibold text-gray-700 dark:text-[#eceee8]">{{ t("owner_reports.title") }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          <span class="font-mono font-data font-medium text-warning"
            >{{ totalEarned.toFixed(2) }} ₪</span
          >
          {{ t("owner_reports.currently_due") }}
        </p>
      </div>
      <div class="flex flex-wrap gap-2.5">
        <a
          v-if="hasRole('generator_owner')"
          :href="ownerMonthlyReportService.downloadUrl()"
          target="_blank"
          class="inline-flex items-center gap-2 bg-primary-500 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-600 transition"
        >
          <FileText class="text-xs" aria-hidden="true" />
          {{ t("owner_reports.my_monthly_report_pdf") }}
        </a>
        <a
          v-if="hasRole('admin')"
          :href="platformCommissionService.downloadReportPdfUrl()"
          target="_blank"
          class="inline-flex items-center gap-2 bg-primary-500 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-600 transition"
        >
          <FileDown class="text-xs" aria-hidden="true" />
          {{ t("owner_reports.download_full_report_pdf") }}
        </a>
      </div>
    </div>

    <div
      v-if="markError"
      class="bg-danger-bg text-danger text-sm rounded-lg p-3"
    >
      {{ markError }}
    </div>

    <div v-if="isLoading" class="space-y-2">
      <div
        v-for="i in 6"
        :key="i"
        class="h-16 rounded-lg bg-gray-50 dark:bg-white/5 animate-pulse"
      ></div>
    </div>

    <div
      v-else-if="error"
      class="bg-danger-bg text-danger text-sm rounded-lg p-6 text-center"
    >
      {{ error }}
    </div>

    <div
      v-else-if="commissions.length === 0"
      class="text-center py-16 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10 text-sm text-gray-500 dark:text-gray-400"
    >
      {{ t("owner_reports.empty") }}
    </div>

    <div
      v-else
      class="bg-surface dark:bg-[#1c1e20] rounded-card border border-border dark:border-white/10 overflow-hidden"
    >
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border dark:border-white/10 text-xs text-gray-500 dark:text-gray-400">
            <th
              v-if="hasRole('admin')"
              class="text-start font-medium px-4 py-3"
            >
              {{ t("owner_reports.owner_col") }}
            </th>
            <th class="text-start font-medium px-4 py-3">{{ t("owner_reports.generator_col") }}</th>
            <th class="text-start font-medium px-4 py-3">{{ t("owner_reports.commission_rate_col") }}</th>
            <th class="text-start font-medium px-4 py-3">{{ t("owner_reports.amount_col") }}</th>
            <th class="text-start font-medium px-4 py-3">{{ t("owner_reports.status_col") }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border dark:divide-white/10">
          <tr
            v-for="commission in commissions"
            :key="commission.id"
            :class="{
              'opacity-50 pointer-events-none': markingId === commission.id,
            }"
          >
            <td
              v-if="hasRole('admin')"
              class="px-4 py-3 font-medium text-gray-700 dark:text-[#eceee8]"
            >
              {{ commission.owner?.name ?? "—" }}
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
              {{ commission.generator_name ?? "—" }}
            </td>
            <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400">
              {{ commission.commission_rate }}%
            </td>
            <td
              class="px-4 py-3 font-mono font-data font-semibold text-gray-700 dark:text-[#eceee8]"
            >
              {{ commission.commission_amount.toFixed(2) }} ₪
            </td>
            <td class="px-4 py-3">
              <span
                class="text-[11px] rounded-full px-2 py-0.5"
                :class="STATUS_TONES[commission.status]"
              >
                {{ statusLabel(commission.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-end">
              <button
                v-if="hasRole('admin') && commission.status === 'earned'"
                type="button"
                @click="markAsPaid(commission.id)"
                class="text-xs text-primary-600 font-medium hover:underline"
              >
                {{ t("owner_reports.mark_paid") }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 pt-2">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchCommissions(page)"
        class="w-9 h-9 rounded-lg text-sm font-mono font-data transition"
        :class="
          page === pagination.current_page
            ? 'bg-primary-500 text-white'
            : 'bg-surface dark:bg-[#1c1e20] border border-border dark:border-white/10 text-gray-600 dark:text-gray-300 hover:border-primary-300'
        "
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>
