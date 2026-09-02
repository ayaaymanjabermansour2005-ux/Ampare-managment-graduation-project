<script setup>
import { ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import TasksView from "./TasksView.vue";
import MeterReadingsView from "./MeterReadingsView.vue";
import TechnicianPaymentsView from "./TechnicianPaymentsView.vue";

const route = useRoute();
const router = useRouter();

const TAB_KEYS = ["tasks", "readings", "payments"];

const activeTab = ref(
  TAB_KEYS.includes(route.query.tab) ? route.query.tab : "tasks",
);

watch(activeTab, (val) => {
  router.replace({ query: { ...route.query, tab: val } });
});

watch(
  () => route.query.tab,
  (val) => {
    if (TAB_KEYS.includes(val) && val !== activeTab.value) {
      activeTab.value = val;
    }
  },
);

const activeComponent = computed(() => {
  if (activeTab.value === "readings") return MeterReadingsView;
  if (activeTab.value === "payments") return TechnicianPaymentsView;
  return TasksView;
});
</script>

<template>
  <div class="space-y-4 pb-4">
    <div v-reveal class="flex gap-1 rounded-full bg-[#f4efe5]/70 dark:bg-white/5 p-1">
      <button
        v-for="tab in TABS"
        :key="tab.key"
        type="button"
        @click="activeTab = tab.key"
        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-full text-xs font-bold transition-colors"
        :class="
          activeTab === tab.key
            ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
            : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/10'
        "
      >
        <AppIcon :name="tab.icon" />
        {{ tab.label }}
      </button>
    </div>

    <component :is="activeComponent" />
  </div>
</template>