<script setup>
import { ref, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { normalizeApiError } from "@/utils/normalizeApiError";
import generatorService from "@/services/generatorService";
import { CircleAlert, Clock, Lock, PlugZap, Wrench, ZoomOut } from "@lucide/vue";

const route = useRoute();
const { t } = useI18n();
const generatorId = route.params.id;

const generator = ref(null);
const openFaults = ref([]);
const recentTasks = ref([]);
const isLoading = ref(true);
const errorMessage = ref(null);
const isForbidden = ref(false);
const isNotFound = ref(false);

function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}

async function load() {
  isLoading.value = true;
  errorMessage.value = null;
  isForbidden.value = false;
  isNotFound.value = false;

  try {
    const { data } = await generatorService.quickScan(generatorId);
    generator.value = data.data.generator;
    openFaults.value = data.data.open_faults;
    recentTasks.value = data.data.recent_technician_tasks;
  } catch (err) {
    const status = err.response?.status;

    if (status === 403) {
      isForbidden.value = true;
    } else if (status === 404) {
      isNotFound.value = true;
    } else {
      errorMessage.value = normalizeApiError(err, t("quick_scan.load_error")).message;
    }
  } finally {
    isLoading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="max-w-lg mx-auto space-y-4">
    <!-- ===== LOADING ===== -->
    <div v-if="isLoading" class="space-y-4">
      <div class="h-24 rounded-2xl thumb-loading"></div>
      <div class="h-32 rounded-2xl thumb-loading"></div>
      <div class="h-32 rounded-2xl thumb-loading"></div>
    </div>

    <!-- ===== FORBIDDEN (403) ===== -->
    <div v-else-if="isForbidden" class="glass-card p-8 text-center">
      <div class="w-14 h-14 rounded-full bg-[#D9534F]/10 flex items-center justify-center mx-auto mb-3">
        <Lock class="text-[#D9534F] text-xl" aria-hidden="true" />
      </div>
      <h1 class="text-lg font-bold text-gray-700 dark:text-[#eceee8] mb-2">{{ t("quick_scan.forbidden_title") }}</h1>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed max-w-sm mx-auto">
        {{ t("quick_scan.forbidden_message") }}
      </p>
    </div>

    <!-- ===== NOT FOUND (404) ===== -->
    <div v-else-if="isNotFound" class="glass-card p-8 text-center">
      <div class="w-14 h-14 rounded-full bg-[#8A6D1F]/10 flex items-center justify-center mx-auto mb-3">
        <ZoomOut class="text-[#8A6D1F] text-xl" aria-hidden="true" />
      </div>
      <h1 class="text-lg font-bold text-gray-700 dark:text-[#eceee8] mb-2">{{ t("quick_scan.not_found_title") }}</h1>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed max-w-sm mx-auto">
        {{ t("quick_scan.not_found_message") }}
      </p>
    </div>

    <!-- ===== GENERIC ERROR ===== -->
    <div v-else-if="errorMessage" class="alert-box">
      <CircleAlert class="shrink-0" aria-hidden="true" />
      <div>{{ errorMessage }}</div>
    </div>

    <!-- ===== CONTENT ===== -->
    <template v-else-if="generator">
      <!-- بطاقة المولد -->
      <div class="glass-card p-5 relative overflow-hidden">
        <div class="flex items-center gap-3 mb-3">
          <span class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-lg shrink-0">
            <PlugZap aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <h1 class="text-base font-extrabold truncate">{{ generator.name }}</h1>
            <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ generator.code }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 text-[12px]">
          <div>
            <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("quick_scan.capacity_kw") }}:</span>
            <b class="ms-1">{{ generator.capacity_kw ?? "-" }} {{ t("quick_scan.kw_label") }}</b>
          </div>
          <div v-if="generator.fuel_percentage !== null && generator.fuel_percentage !== undefined">
            <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ t("quick_scan.fuel_level") }}:</span>
            <b class="ms-1" :style="{ color: fuelColor(generator.fuel_percentage) }">{{ generator.fuel_percentage }}%</b>
          </div>
        </div>

        <RouterLink
          :to="{ name: 'generators.show', params: { id: generator.id } }"
          class="mt-4 inline-flex items-center justify-center w-full text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5"
        >
          {{ t("quick_scan.view_full_details") }}
        </RouterLink>
      </div>

      <!-- الأعطال المفتوحة -->
      <div class="glass-card p-5">
        <h2 class="text-[13px] font-bold mb-3 flex items-center gap-1.5">
          <CircleAlert class="text-[#D9534F] text-[13px]" aria-hidden="true" /> {{ t("quick_scan.open_faults_title") }}
        </h2>
        <p v-if="openFaults.length === 0" class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">
          {{ t("quick_scan.no_open_faults") }}
        </p>
        <div v-else class="space-y-2">
          <div v-for="fault in openFaults" :key="fault.id" class="p-3 rounded-xl border border-[#eee8da] dark:border-white/5">
            <div class="flex items-center justify-between gap-2">
              <span class="text-[12px] font-bold truncate">{{ fault.title }}</span>
              <span class="status-chip chip-warning shrink-0">{{ t(`quick_scan.fault_status.${fault.status}`) }}</span>
            </div>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1 truncate">{{ fault.description }}</p>
            <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1">
              {{ t(`quick_scan.priority.${fault.priority}`) }} · {{ t("quick_scan.reported_at") }}: {{ fault.reported_at }}
            </p>
          </div>
        </div>
      </div>

      <!-- مهام الفنيين الأخيرة -->
      <div class="glass-card p-5">
        <h2 class="text-[13px] font-bold mb-3 flex items-center gap-1.5">
          <Wrench class="text-[#8A6D1F] text-[13px]" aria-hidden="true" /> {{ t("quick_scan.recent_tasks_title") }}
        </h2>
        <p v-if="recentTasks.length === 0" class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">
          {{ t("quick_scan.no_recent_tasks") }}
        </p>
        <div v-else class="space-y-2">
          <div v-for="task in recentTasks" :key="task.id" class="p-3 rounded-xl border border-[#eee8da] dark:border-white/5">
            <div class="flex items-center justify-between gap-2">
              <span class="text-[12px] font-bold">{{ task.type_label }}</span>
              <span class="status-chip chip-info shrink-0">{{ task.status_label }}</span>
            </div>
            <p v-if="task.technician?.name" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1 flex items-center gap-1">
              <Clock class="text-[9px]" aria-hidden="true" /> {{ task.technician.name }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
