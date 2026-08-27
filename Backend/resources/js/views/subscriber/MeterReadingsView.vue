<script setup>
import { computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useSubscriberMeterReadings } from "@/composables/useSubscriberMeterReadings";
import { vReveal } from "@/directives/reveal";
import { ChevronLeft, ChevronRight, Gauge, TriangleAlert } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();
const { readings, pagination, isLoading, error, fetchReadings } = useSubscriberMeterReadings();

onMounted(() => {
  fetchReadings(1);
});

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page) return;
  fetchReadings(page);
}

const STATUS_LABELS = computed(() => ({
  approved: t("meter_readings_page.status_approved"),
  pending_approval: t("meter_readings_page.status_pending_approval"),
  rejected: t("meter_readings_page.status_rejected"),
}));
const STATUS_TONES = {
  approved: "chip-success",
  pending_approval: "chip-warning",
  rejected: "chip-danger",
};

/* ===== لون ديناميكي حسب حالة القراءات ==================================
 * فيه قراءات مرفوضة → أحمر (أعلى أولوية تنبيه).
 * وإلا فيه قراءات بانتظار الاعتماد → ذهبي (تنبيه أخف).
 * وإلا (كل شي معتمد أو ما في قراءات أصلاً) → أخضر هوية الموقع.
 * ========================================================================= */
const hasRejected = computed(() => readings.value.some((r) => r.status === "rejected"));
const hasPending = computed(() => readings.value.some((r) => r.status === "pending_approval"));

const accent = computed(() => {
  if (hasRejected.value) return { hex: "#D9534F", glow: "rgba(217,83,79,0.22)", icon: "fa-triangle-exclamation" };
  if (hasPending.value) return { hex: "#D4AF37", glow: "rgba(212,175,55,0.22)", icon: "fa-hourglass-half" };
  return { hex: "#52733D", glow: "rgba(82,115,61,0.2)", icon: "fa-gauge" };
});

function formatDate(value) {
  if (!value) return "—";
  return new Date(value).toLocaleDateString("ar", { year: "numeric", month: "short", day: "numeric" });
}
</script>

<template>
  <div class="space-y-6">
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div
        class="absolute -start-16 -top-16 w-72 h-72 rounded-full blur-[100px] pointer-events-none transition-colors duration-500"
        :style="{ background: accent.glow }"
      ></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("common.account_breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("subscriber_meter_readings_page.breadcrumb") }}</span>
      </nav>

      <div class="relative flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl lg:text-2xl font-extrabold flex items-center gap-2.5">
          <span
            class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-base transition-colors duration-500"
            :style="{ background: `linear-gradient(135deg, ${accent.hex}, #3E582E)` }"
          >
            <AppIcon :name="accent.icon" />
          </span>
          {{ t("subscriber_meter_readings_page.breadcrumb") }}
        </h1>

        <div v-if="hasRejected" class="status-chip chip-danger">
          <span>{{ t("subscriber_meter_readings_page.alert_rejected") }}</span>
        </div>
        <div v-else-if="hasPending" class="status-chip chip-warning">
          <span>{{ t("subscriber_meter_readings_page.alert_pending") }}</span>
        </div>

        <div class="text-[11.5px] font-bold text-[#9a9d97]">
          {{ t("subscriber_meter_readings_page.total_label") }}
          <span class="text-[#52733D] dark:text-[#8cc35a]">{{ pagination.total }}</span>
        </div>
      </div>
    </section>

    <div v-if="isLoading" class="glass-card h-40 thumb-loading"></div>

    <section v-else-if="error" v-reveal class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
      <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
      {{ error }}
    </section>

    <section
      v-else-if="readings.length === 0"
      v-reveal
      class="glass-card p-10 text-center max-w-md mx-auto"
    >
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <Gauge aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("subscriber_meter_readings_page.empty_title") }}</h3>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
        {{ t("subscriber_meter_readings_page.empty_message") }}
      </p>
    </section>

    <section v-else v-reveal class="glass-card p-5 lg:p-6 overflow-hidden">
      <div class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12.5px]">
          <thead>
            <tr class="text-[11px] uppercase tracking-wide text-[#9a9d97] border-b border-[#f0ece0] dark:border-white/10">
              <th class="text-start font-bold py-2.5 px-3">{{ t("subscriber_meter_readings_page.col_date") }}</th>
              <th class="text-start font-bold py-2.5 px-3">{{ t("subscriber_meter_readings_page.col_previous") }}</th>
              <th class="text-start font-bold py-2.5 px-3">{{ t("subscriber_meter_readings_page.col_current") }}</th>
              <th class="text-start font-bold py-2.5 px-3">{{ t("subscriber_meter_readings_page.col_consumed") }}</th>
              <th class="text-start font-bold py-2.5 px-3">{{ t("subscriber_meter_readings_page.col_status") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="r in readings"
              :key="r.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0"
              :class="{ 'bg-[#D9534F]/[0.04] dark:bg-[#D9534F]/[0.06]': r.status === 'rejected' }"
            >
              <td class="py-3 px-3 font-data whitespace-nowrap">{{ formatDate(r.reading_date ?? r.created_at) }}</td>
              <td class="py-3 px-3 font-data">{{ r.previous_reading ?? "—" }}</td>
              <td class="py-3 px-3 font-data font-bold">{{ r.current_reading ?? "—" }}</td>
              <td class="py-3 px-3 font-data">
                {{ r.consumed_kw ?? (r.current_reading != null && r.previous_reading != null
                  ? (r.current_reading - r.previous_reading)
                  : "—") }}
                <span class="text-[10px] text-[#9a9d97]">kW</span>
              </td>
              <td class="py-3 px-3">
                <span class="status-chip" :class="STATUS_TONES[r.status] ?? 'chip-neutral'">
                  {{ STATUS_LABELS[r.status] ?? r.status }}
                </span>
                <p v-if="r.status === 'rejected' && r.rejection_reason" class="text-[10.5px] text-[#D9534F] mt-1">
                  {{ r.rejection_reason }}
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pagination.last_page > 1"
        class="flex items-center justify-center gap-2 pt-5 mt-2 border-t border-[#f0ece0] dark:border-white/10"
      >
        <button
          type="button"
          class="action-btn action-btn--view"
          :disabled="pagination.current_page === 1"
          @click="goToPage(pagination.current_page - 1)"
        >
          <ChevronRight class="rtl:rotate-0 ltr:rotate-180" aria-hidden="true" />
        </button>
        <span class="text-[12px] font-bold px-2">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button
          type="button"
          class="action-btn action-btn--view"
          :disabled="pagination.current_page === pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
        >
          <ChevronLeft class="rtl:rotate-0 ltr:rotate-180" aria-hidden="true" />
        </button>
      </div>
    </section>
  </div>
</template>