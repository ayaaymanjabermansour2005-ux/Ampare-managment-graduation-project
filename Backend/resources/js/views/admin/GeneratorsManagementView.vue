<script setup>
import { computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { Chart as ChartJS, registerables } from "chart.js";
import { Doughnut } from "vue-chartjs";
import { useAdminGenerators } from "@/composables/useAdminGenerators";
import { vReveal } from "@/directives/reveal";
import AdminGeneratorsMap from "@/components/admin/AdminGeneratorsMap.vue";
import GeneratorsTablePanel from "@/components/generators/GeneratorsTablePanel.vue";
import { RouterLink } from "vue-router";
import { ChevronLeft, ChevronRight, Circle, PlugZap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";

ChartJS.register(...registerables);

const { t } = useI18n();

/* ---------------- بيانات مستقلة للخريطة/مخطط التوزيع/أقل مستوى وقود
   (منفصلة عمدًا عن حالة الجدول القابلة للفلترة/الفرز داخل GeneratorsTablePanel،
   حتى لا يتأثر ملخص النظام هنا ببحث/فلاتر المستخدم داخل الجدول) ---------------- */
const { generators, stats, isLoadingStats, loadAll } = useAdminGenerators();

const systemStatusInfo = computed(() => {
  if (!stats.value) return null;
  const criticalCount = stats.value.inactive + stats.value.rejected;
  if (criticalCount === 0) {
    return { label: t("common.system_running"), color: "#28A745" };
  }
  return {
    label: t("generators_management_page.generators_offline", { count: criticalCount }),
    color: "#D9534F",
  };
});

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

const lowestFuelGenerators = computed(() =>
  [...generators.value]
    .filter((g) => g.fuel_percentage !== null && g.fuel_percentage !== undefined)
    .sort((a, b) => a.fuel_percentage - b.fuel_percentage)
    .slice(0, 5),
);
function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}

onMounted(() => {
  loadAll();
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
      <div class="relative">
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
    </section>

    <!-- ===== الجدول الكامل (كاردات KPI + شريط الأدوات + الجدول) — مكوّن مشترك ===== -->
    <GeneratorsTablePanel />

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
          <RouterLink
            v-for="g in lowestFuelGenerators" :key="g.id"
            :to="{ name: 'generators.show', params: { id: g.id } }"
            class="flex items-center gap-2.5 w-full text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 rounded-lg p-1 -m-1 transition-colors"
          >
            <span class="text-[11px] font-bold truncate flex-1">{{ g.name }}</span>
            <div class="bar-track w-16"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
            <span class="text-[10.5px] font-bold w-9 text-end" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>
