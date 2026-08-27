<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import TasksView from "./TasksView.vue";
import MeterReadingsView from "./MeterReadingsView.vue";
import TechnicianPaymentsView from "./TechnicianPaymentsView.vue";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const TABS = computed(() => [
  { key: "tasks", label: t("technician_portal.nav_tasks"), icon: "fa-list-check" },
  { key: "readings", label: t("technician_portal.nav_readings"), icon: "fa-gauge" },
  { key: "payments", label: t("technician_portal.nav_payments"), icon: "fa-wallet" },
]);

const activeTab = ref(
  TABS.value.some((tab) => tab.key === route.query.tab)
    ? route.query.tab
    : "tasks",
);

watch(activeTab, (val) => {
  router.replace({ query: { ...route.query, tab: val } });
});

const activeComponent = computed(() => {
  if (activeTab.value === "readings") return MeterReadingsView;
  if (activeTab.value === "payments") return TechnicianPaymentsView;
  return TasksView;
});
</script>

<template>
  <div class="space-y-4 pb-4">
    <!-- FIX: كانت sticky top-0 بنفس مستوى الهيدر الخارجي بالـ Layout (وهو
         كمان sticky top-0 لكن بـ z-30 أعلى)، فلما تنزل بالسكرول القائمة كانت
         تختفي فعليًا خلف الهيدر بدل ما تثبت تحته. أزلنا sticky بدل ما نربطها
         بارتفاع هيدر متغيّر (بيتغيّر مع ظهور شريط أوفلاين/مزامنة). -->
    <div class="flex gap-1 rounded-full bg-[#f4efe5]/70 dark:bg-white/5 p-1">
      <button
        v-for="tab in TABS"
        :key="tab.key"
        type="button"
        @click="activeTab = tab.key"
        class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-full text-xs font-semibold transition"
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
