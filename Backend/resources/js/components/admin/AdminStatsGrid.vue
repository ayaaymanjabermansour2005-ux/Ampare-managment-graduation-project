<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminDashboard } from "@/composables/usePlatformFinancialStats";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t } = useI18n();
const { stats, isLoading, error, fetchStats } = useAdminDashboard();

// ملاحظة إصلاح: كنا نبني اسم الكلاس ديناميكيًا (`text-${color}-500`) وهاد بيخلي
// Tailwind JIT ما يقدر يكتشف الكلاس وقت الـ build فينحذف من الـ CSS النهائي.
// الحل: نستخدم قيمة hex مباشرة عبر style بدل اسم كلاس متغيّر.
const CARDS = [
  { key: "invoices_issued_count", labelKey: "admin_stats_grid.invoices_issued", icon: "fa-file-invoice", color: "#17A2B8" },
  { key: "invoices_paid_count", labelKey: "admin_stats_grid.invoices_paid", icon: "fa-circle-check", color: "#28A745" },
  { key: "invoices_overdue_count", labelKey: "admin_stats_grid.invoices_overdue", icon: "fa-triangle-exclamation", color: "#D9534F" },
  { key: "open_faults_count", labelKey: "admin_stats_grid.open_faults", icon: "fa-bolt", color: "#FFC107" },
  { key: "new_service_requests_count", labelKey: "admin_stats_grid.new_requests", icon: "fa-hand-holding-hand", color: "#FFC107" },
];

onMounted(() => fetchStats());
</script>

<template>
  <div class="space-y-3.5">
    <h2 class="text-[12.5px] font-extrabold text-[#3E582E] dark:text-[#8cc35a]">{{ t("admin_stats_grid.section_title") }}</h2>

    <div v-if="error" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3.5 py-2.5">{{ error }}</div>

    <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <div v-for="i in 5" :key="i" class="h-24 rounded-2xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>
    </div>

    <template v-else-if="stats">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div
          v-for="card in CARDS"
          :key="card.key"
          class="glass-card !bg-white/90 dark:!bg-[#1c1e20]/90 p-4 hover:-translate-y-0.5 transition-transform duration-300"
        >
          <div class="flex items-center gap-2 mb-2">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] shrink-0" :style="{ background: `${card.color}1A`, color: card.color }">
              <AppIcon :name="card.icon" />
            </span>
            <p class="text-[10.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5] truncate">{{ t(card.labelKey) }}</p>
          </div>
          <p class="font-mono text-xl font-extrabold">{{ stats[card.key] }}</p>
        </div>
      </div>

      <div class="glass-card !bg-gradient-to-l !from-[#3E582E]/10 !via-[#52733D]/10 !to-[#8A6D1F]/10 dark:!from-[#3E582E]/25 dark:!via-[#52733D]/20 dark:!to-[#8A6D1F]/20 p-5 flex items-center justify-between">
        <p class="text-[12.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("admin_stats_grid.total_revenue_label") }}</p>
        <p class="font-mono text-2xl font-extrabold text-[#3E582E] dark:text-[#8cc35a]">{{ stats.total_revenue_ils.toFixed(2) }} ₪</p>
      </div>
    </template>
  </div>
</template>