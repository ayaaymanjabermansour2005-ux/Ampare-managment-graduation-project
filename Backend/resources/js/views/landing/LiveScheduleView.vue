<script setup>
import { ref, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import neighborhoodService from "@/services/neighborhoodService";
import { useLiveSchedule } from "@/composables/useLiveSchedule";
import { Clock, Zap } from "@lucide/vue";

const { t } = useI18n();

const neighborhoods = ref([]);
const selectedNeighborhood = ref(null);
const { activeNow, upcoming, isLoading, error, fetchSchedule } = useLiveSchedule();

watch(selectedNeighborhood, () => fetchSchedule(selectedNeighborhood.value));

onMounted(async () => {
    const { data } = await neighborhoodService.list();
    neighborhoods.value = data.data;
    await fetchSchedule();
});
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-12 space-y-6">
        <div class="text-center">
            <p class="text-xs font-medium text-secondary-600 tracking-wide mb-2">{{ t("live_schedule_page.eyebrow") }}</p>
            <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ t("live_schedule_page.title") }}</h1>
        </div>

        <select v-model="selectedNeighborhood" class="w-full rounded-lg border border-border px-3.5 py-2.5 text-sm">
            <option :value="null">{{ t("live_schedule_page.all_neighborhoods") }}</option>
            <option v-for="n in neighborhoods" :key="n.id" :value="n.id">{{ n.name }}</option>
        </select>

        <div v-if="error" class="bg-danger-bg text-danger text-sm rounded-lg p-4">{{ error }}</div>
        <div v-if="isLoading" class="h-40 rounded-card bg-gray-50 dark:bg-white/5 animate-pulse"></div>

        <template v-else>
            <div>
                <h2 class="text-sm font-semibold text-success mb-2"><Zap aria-hidden="true" /> {{ t("live_schedule_page.active_now_title") }}</h2>
                <div v-if="activeNow.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10">
                    {{ t("live_schedule_page.no_active_generators") }}
                </div>
                <div v-for="(item, i) in activeNow" :key="i" class="bg-success-bg rounded-card p-3 mb-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ item.generator_name }} — {{ item.neighborhood }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ item.starts_at }} — {{ item.ends_at }}</p>
                </div>
            </div>

            <div v-if="upcoming.length > 0">
                <h2 class="text-sm font-semibold text-secondary-600 mb-2"><Clock aria-hidden="true" /> {{ t("live_schedule_page.upcoming_title") }}</h2>
                <div v-for="(item, i) in upcoming" :key="i" class="bg-surface dark:bg-[#1c1e20] border border-border dark:border-white/10 rounded-card p-3 mb-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ item.generator_name }} — {{ item.neighborhood }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ item.starts_at }} — {{ item.ends_at }}</p>
                </div>
            </div>
        </template>
    </div>
</template>