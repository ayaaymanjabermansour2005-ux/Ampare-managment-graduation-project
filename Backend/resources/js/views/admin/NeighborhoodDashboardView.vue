<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useNeighborhoodDashboard } from "@/composables/useNeighborhoodDashboard";
import { vReveal } from "@/directives/reveal";
import { ChevronLeft, ChevronRight, CircleAlert, MapPinned } from "@lucide/vue";

const { t } = useI18n();

const { neighborhoods, isLoading, error, fetchSummary } =
  useNeighborhoodDashboard();

onMounted(() => fetchSummary());
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ t("common.home") }}</span>
        <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("neighborhood_dashboard_page.title") }}</span>
      </nav>
      <div class="relative">
        <span class="inline-flex items-center gap-2 text-[11px] font-bold text-[#52733D] dark:text-[#8cc35a] bg-[#EBF1E7] dark:bg-white/5 border border-[#D4AF37]/30 rounded-full px-3 py-1.5 w-fit mb-2.5">
          <MapPinned class="text-[#8A6D1F]" aria-hidden="true" />
          {{ t("neighborhood_dashboard_page.eyebrow") }}
        </span>
        <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
            <MapPinned aria-hidden="true" />
          </span>
          {{ t("neighborhood_dashboard_page.title") }}
        </h1>
      </div>
    </section>

    <!-- ===== ERROR ===== -->
    <section
      v-if="error"
      v-reveal
      class="glass-card p-6 text-center text-[12.5px] text-[#D9534F] border border-[#D9534F]/30"
    >
      <CircleAlert class="me-1.5" aria-hidden="true" />{{ error }}
    </section>

    <!-- ===== LOADING ===== -->
    <section
      v-if="isLoading"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
    >
      <div
        v-for="i in 6"
        :key="i"
        class="h-32 rounded-2xl thumb-loading"
      ></div>
    </section>

    <!-- ===== EMPTY STATE ===== -->
    <section
      v-else-if="!error && neighborhoods.length === 0"
      v-reveal
      class="glass-card p-12 text-center"
    >
      <MapPinned class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
      <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("settings_page.no_neighborhoods_yet") }}</p>
    </section>

    <!-- ===== GRID ===== -->
    <section v-else-if="!error" v-reveal class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="n in neighborhoods"
        :key="n.neighborhood_id"
        class="glass-card hoverable p-5"
      >
        <h3 class="font-bold text-[13.5px] mb-3 truncate">
          {{ n.neighborhood_name }}
        </h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("neighborhood_dashboard_page.generators_label") }}</p>
            <p class="font-mono font-semibold text-[#8A6D1F] dark:text-[#D4AF37]">
              {{ n.active_generators_count
              }}<span class="text-xs text-[#9a9d97] dark:text-[#8f938a]">
                / {{ n.generators_count }}</span
              >
            </p>
          </div>
          <div>
            <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("neighborhood_dashboard_page.active_subscriptions_label") }}</p>
            <p class="font-mono font-semibold text-[#3a3a38] dark:text-[#eef0ec]">
              {{ n.active_subscriptions_count }}
            </p>
          </div>
          <div class="col-span-2">
            <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("neighborhood_dashboard_page.open_faults_label") }}</p>
            <p
              class="font-mono font-semibold"
              :class="n.open_faults_count > 0 ? 'text-[#D9534F]' : 'text-[#28A745]'"
            >
              {{ n.open_faults_count }}
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>