<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import generatorService from "@/services/generatorService";
import GeneratorScheduleBoard from "@/components/generators/GeneratorScheduleBoard.vue";
import GeneratorHealthReports from "@/components/generators/GeneratorHealthReports.vue";
import GeneratorTimeline from "@/components/generators/GeneratorTimeline.vue";
import { CalendarDays, CircleAlert, Lock, MapPin, PlugZap, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();
const generatorId = route.params.id;

const generator = ref(null);
const isLoading = ref(true);
const errorMessage = ref(null);
const isForbidden = ref(false);
const isNotFound = ref(false);

/* ---------------- حالة المولد (نفس هوية الداشبورد: status-chip + لون مطابق) ---------------- */
const STATUS_META = {
  active: { chip: "chip-success", color: "#28A745" },
  maintenance: { chip: "chip-warning", color: "#FFC107" },
  inactive: { chip: "chip-danger", color: "#D9534F" },
};
function statusLabel(status) {
  return t(`owner_generators.status.${status}`);
}
function scheduleLabel(schedule) {
  return t(`owner_generators.schedule.${schedule}`);
}

const canManageSchedule = computed(() => {
  if (!generator.value) return false;
  if (authStore.hasRole("admin")) return true;
  return (
    authStore.hasRole("generator_owner") &&
    generator.value.owner?.id === authStore.user?.id
  );
});

/* ---------------- بطاقات التفاصيل — بنفس نمط أيقونات الداشبورد الملوّنة ---------------- */
const DETAIL_ITEMS = computed(() => {
  if (!generator.value) return [];
  const g = generator.value;
  const items = [
    {
      key: "capacity",
      icon: "fa-bolt",
      color: "#52733D",
      label: t("owner_generators.detail.capacity"),
      value: `${g.capacity_kw ?? "-"} ${t("owner_generators.detail.capacity_unit_suffix")}`,
    },
    {
      key: "price",
      icon: "fa-money-bill-wave",
      color: "#8A6D1F",
      label: t("owner_generators.detail.price_per_kw"),
      value: `${g.price_per_kw ?? "-"} ${g.currency ?? ""}`,
    },
    {
      key: "schedule",
      icon: "fa-calendar-days",
      color: "#17A2B8",
      label: t("owner_generators.detail.operating_schedule"),
      value: scheduleLabel(g.operating_schedule),
    },
  ];
  if (g.operating_start_time && g.operating_end_time) {
    items.push({
      key: "hours",
      icon: "fa-clock",
      color: "#FFC107",
      label: t("owner_generators.detail.operating_hours"),
      value: `${g.operating_start_time} — ${g.operating_end_time}`,
    });
  }
  items.push({
    key: "owner",
    icon: "fa-user-tie",
    color: "#52733D",
    label: t("owner_generators.detail.owner"),
    value: g.owner?.name ?? "—",
  });
  if (g.active_subscriptions_count !== undefined) {
    items.push({
      key: "subscribers",
      icon: "fa-users",
      color: "#3E582E",
      label: t("owner_generators.detail.active_subscribers"),
      value: g.active_subscriptions_count,
    });
  }
  return items;
});

async function load() {
  isLoading.value = true;
  errorMessage.value = null;
  isForbidden.value = false;
  isNotFound.value = false;

  try {
    const { data } = await generatorService.show(generatorId);
    generator.value = data.data;
  } catch (err) {
    const status = err.response?.status;

    if (status === 403) {
      isForbidden.value = true;
    } else if (status === 404) {
      isNotFound.value = true;
    } else {
      errorMessage.value =
        err.response?.data?.message ?? t("owner_generators.detail.load_error");
    }
  } finally {
    isLoading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-5">
    <!-- ===== LOADING ===== -->
    <div v-if="isLoading" class="space-y-5">
      <div class="h-32 rounded-2xl thumb-loading"></div>
      <div class="grid grid-cols-2 gap-3">
        <div v-for="i in 4" :key="i" class="h-16 rounded-2xl thumb-loading"></div>
      </div>
    </div>

    <!-- ===== FORBIDDEN (403) ===== -->
    <div v-else-if="isForbidden" class="glass-card p-8 text-center">
      <div class="w-14 h-14 rounded-full bg-[#D9534F]/10 flex items-center justify-center mx-auto mb-3">
        <Lock class="text-[#D9534F] text-xl" aria-hidden="true" />
      </div>
      <h1 class="text-lg font-bold text-gray-700 dark:text-[#eceee8] mb-2">
        {{ t("owner_generators.detail.forbidden_title") }}
      </h1>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed max-w-sm mx-auto">
        {{ t("owner_generators.detail.forbidden_message") }}
      </p>
    </div>

    <!-- ===== NOT FOUND (404) ===== -->
    <div v-else-if="isNotFound" class="glass-card p-8 text-center">
      <div class="w-14 h-14 rounded-full bg-[#8A6D1F]/10 flex items-center justify-center mx-auto mb-3">
        <ZoomOut class="text-[#8A6D1F] text-xl" aria-hidden="true" />
      </div>
      <h1 class="text-lg font-bold text-gray-700 dark:text-[#eceee8] mb-2">
        {{ t("owner_generators.detail.not_found_title") }}
      </h1>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed max-w-sm mx-auto">
        {{ t("owner_generators.detail.not_found_message") }}
      </p>
    </div>

    <!-- ===== GENERIC ERROR ===== -->
    <div v-else-if="errorMessage" class="alert-box">
      <CircleAlert class="shrink-0" aria-hidden="true" />
      <div>{{ errorMessage }}</div>
    </div>

    <!-- ===== CONTENT ===== -->
    <template v-else-if="generator">
      <!-- Header Card -->
      <section class="glass-card p-6 relative overflow-hidden">
        <div class="absolute -start-16 -top-16 w-64 h-64 bg-[#D4AF37]/15 rounded-full blur-[90px] pointer-events-none"></div>
        <div class="relative flex items-start justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <span
              class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shrink-0"
              style="background:linear-gradient(135deg,#52733D,#3E582E)"
            >
              <PlugZap class="text-lg" aria-hidden="true" />
            </span>
            <div class="min-w-0">
              <h1 class="text-xl font-bold text-gray-700 dark:text-[#eceee8] truncate">
                {{ generator.name }}
              </h1>
              <p class="text-xs text-[#9a9d97] dark:text-[#8f938a] mt-0.5">
                {{ t("owner_generators.detail.generator_hash", { id: generator.id }) }}
              </p>
            </div>
          </div>
          <span
            class="status-chip shrink-0"
            :class="STATUS_META[generator.status]?.chip ?? 'chip-info'"
          >
            {{ statusLabel(generator.status) }}
          </span>
        </div>
      </section>

      <!-- Detail Grid -->
      <section class="glass-card p-5">
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="item in DETAIL_ITEMS"
            :key="item.key"
            class="flex items-center gap-2.5 p-2.5 rounded-lg bg-white/50 dark:bg-white/[0.04] border border-[#eee8da] dark:border-white/10"
          >
            <span
              class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0"
              :style="{ background: item.color }"
            >
              <AppIcon :name="item.icon" class="text-[11px]" />
            </span>
            <div class="min-w-0">
              <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mb-0.5">{{ item.label }}</p>
              <p class="text-[12px] font-bold text-gray-700 dark:text-[#eceee8] truncate">{{ item.value }}</p>
            </div>
          </div>
        </div>

        <!-- Location -->
        <div
          v-if="generator.location"
          class="mt-3 flex items-start gap-2.5 p-2.5 rounded-lg bg-white/50 dark:bg-white/[0.04] border border-[#eee8da] dark:border-white/10"
        >
          <span
            class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0"
            style="background:#D9534F"
          >
            <MapPin class="text-[11px]" aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mb-0.5">{{ t("owner_generators.detail.location") }}</p>
            <p class="text-[12px] font-bold text-gray-700 dark:text-[#eceee8]">
              {{ generator.location.city }}
              <span v-if="generator.location.neighborhood" class="font-normal text-[#6B6B6B] dark:text-[#a8aaa5]">
                — {{ generator.location.neighborhood }}
              </span>
            </p>
            <p v-if="generator.location.address" class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mt-0.5">
              {{ generator.location.address }}
            </p>
          </div>
        </div>
      </section>

      <!-- جدول تشغيل المولد المُعلَن -->
      <section class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3 flex items-center gap-2">
          <CalendarDays class="text-[#8A6D1F]" aria-hidden="true" />
          {{ t("owner_generators.detail.operating_schedule") }}
        </h3>
        <GeneratorScheduleBoard
          :generator-id="generator.id"
          :can-manage="canManageSchedule"
        />
      </section>

      <section class="glass-card p-4">
        <GeneratorHealthReports :generator-id="generator.id" />
      </section>

      <section class="glass-card p-4">
        <GeneratorTimeline :generator-id="generator.id" />
      </section>
    </template>
  </div>
</template>