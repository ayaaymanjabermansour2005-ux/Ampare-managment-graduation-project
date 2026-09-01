<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import neighborhoodService from "@/services/neighborhoodService";
import { useLiveSchedule } from "@/composables/useLiveSchedule";
import { House, Clock, Zap } from "@lucide/vue";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";

const { t } = useI18n();

const neighborhoods = ref([]);
const selectedNeighborhood = ref(null);
const neighborhoodOptions = computed(() => [
  { value: null, label: t("live_schedule_page.all_neighborhoods") },
  ...neighborhoods.value.map((n) => ({ value: n.id, label: n.name })),
]);
const { activeNow, upcoming, isLoading, error, fetchSchedule } = useLiveSchedule();

watch(selectedNeighborhood, () => fetchSchedule(selectedNeighborhood.value));

onMounted(async () => {
  const { data } = await neighborhoodService.list();
  neighborhoods.value = data.data;
  await fetchSchedule();
});
</script>

<template>
  <div class="max-w-2xl mx-auto px-5 sm:px-8 pt-28 sm:pt-32 pb-16 space-y-6">
    <RouterLink
      :to="{ name: 'landing.home' }"
      class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5] bg-[#f4efe5] dark:bg-white/5 border border-[#8A6D1F]/30 dark:border-[#F4E0A5]/25 hover:bg-[#eee5cf] dark:hover:bg-white/10 transition-colors"
    >
      <House aria-hidden="true" />
      {{ t("landing.articles_page.back_to_home") }}
    </RouterLink>

    <div class="text-center">
      <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("live_schedule_page.eyebrow") }}</span>
      <h1 class="text-2xl sm:text-3xl font-extrabold mt-2">{{ t("live_schedule_page.title") }}</h1>
    </div>

    <AppDropdownSelect
      v-model="selectedNeighborhood"
      :options="neighborhoodOptions"
      variant="field"
      width-class="w-full"
      :match-trigger-width="true"
    />

    <div v-if="error" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-4 text-center">{{ error }}</div>
    <div v-if="isLoading" class="glass-card h-40 animate-pulse"></div>

    <template v-else>
      <div>
        <h2 class="text-[13px] font-bold text-[#52733D] dark:text-[#8cc35a] mb-2.5 flex items-center gap-1.5">
          <Zap aria-hidden="true" /> {{ t("live_schedule_page.active_now_title") }}
        </h2>
        <div
          v-if="activeNow.length === 0"
          class="glass-card text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] py-6 text-center"
        >
          {{ t("live_schedule_page.no_active_generators") }}
        </div>
        <div
          v-for="(item, i) in activeNow"
          :key="i"
          class="glass-card !bg-[#10B981]/10 dark:!bg-[#10B981]/10 p-3.5 mb-2.5"
        >
          <p class="text-[12.5px] font-bold">{{ item.generator_name }} — {{ item.neighborhood }}</p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] font-mono" dir="ltr">{{ item.starts_at }} — {{ item.ends_at }}</p>
        </div>
      </div>

      <div v-if="upcoming.length > 0">
        <h2 class="text-[13px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5] mb-2.5 flex items-center gap-1.5">
          <Clock aria-hidden="true" /> {{ t("live_schedule_page.upcoming_title") }}
        </h2>
        <div v-for="(item, i) in upcoming" :key="i" class="glass-card p-3.5 mb-2.5">
          <p class="text-[12.5px] font-bold">{{ item.generator_name }} — {{ item.neighborhood }}</p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] font-mono" dir="ltr">{{ item.starts_at }} — {{ item.ends_at }}</p>
        </div>
      </div>
    </template>
  </div>
</template>
