<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import generatorService from '@/services/generatorService';
import AppIcon from "@/components/ui/AppIcon.vue";

const props = defineProps({ generatorId: { type: [Number, String], required: true } });

const { t } = useI18n();

const events = ref([]);
const isLoading = ref(true);

const TYPE_ICONS = {
    fault: 'fa-triangle-exclamation text-danger',
    diagnostic: 'fa-gauge text-secondary-600',
    health_report: 'fa-heart-pulse text-success',
    schedule: 'fa-clock text-primary-500',
};

onMounted(async () => {
    const { data } = await generatorService.timeline(props.generatorId);
    events.value = data.data;
    isLoading.value = false;
});
</script>

<template>
    <div class="space-y-3">
        <h3 class="font-semibold text-gray-700 dark:text-gray-300">{{ t('generator_timeline.title') }}</h3>
        <div v-if="isLoading" class="h-32 rounded-card bg-gray-50 dark:bg-white/5 animate-pulse"></div>
        <div v-else-if="events.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">{{ t('generator_timeline.empty') }}</div>
        <div v-else class="space-y-2 border-s-2 border-border dark:border-white/10 ps-4">
            <div v-for="(event, i) in events" :key="i" class="relative">
                <span class="absolute -start-[21px] top-1 w-2.5 h-2.5 rounded-full bg-primary-500"></span>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><AppIcon :name="`fa-solid ${TYPE_ICONS[event.type]} me-1`" />{{ event.title }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ event.description }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ event.date }}</p>
            </div>
        </div>
    </div>
</template>