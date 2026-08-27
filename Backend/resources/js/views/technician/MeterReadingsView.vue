<script setup>
import { reactive, ref, onMounted, watch, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useTechnicianMeterReadings } from "@/composables/useTechnicianMeterReadings";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Clock, Gauge, WifiOff, Zap } from "@lucide/vue";

const { t } = useI18n();

const {
  generators,
  selectedGeneratorId,
  isLoadingGenerators,
  loadGenerators,
  subscriptions,
  isLoadingSubscriptions,
  loadSubscriptions,
  submitReading,
} = useTechnicianMeterReadings();
const generatorSelectOptions = computed(() => generators.value.map((g) => ({ value: g.id, label: g.name })));

const readingTarget = ref(null);
const readingForm = reactive({
  current_reading: "",
  reading_date: new Date().toISOString().slice(0, 10),
});
const isSubmitting = ref(false);
const submitError = ref(null);
const submitResult = ref(null);

function openReadingForm(subscription) {
  readingTarget.value = subscription;
  readingForm.current_reading = "";
  readingForm.reading_date = new Date().toISOString().slice(0, 10);
  submitError.value = null;
  submitResult.value = null;
}

async function handleSubmit() {
  isSubmitting.value = true;
  submitError.value = null;

  try {
    const result = await submitReading({
      subscription_id: readingTarget.value.id,
      reading_date: readingForm.reading_date,
      current_reading: readingForm.current_reading,
    });

    submitResult.value = {
      success: true,
      isQueued: result.isQueued,
      status: result.status,
    };

    if (!result.isQueued) {
      setTimeout(() => {
        readingTarget.value = null;
      }, 1800);
    }
  } catch (err) {
    submitError.value = err.response?.data?.message ?? t("owner_meter_readings.submit_error");
  } finally {
    isSubmitting.value = false;
  }
}

watch(selectedGeneratorId, () => loadSubscriptions());

onMounted(async () => {
  await loadGenerators();
  await loadSubscriptions();
});
</script>

<template>
  <div class="max-w-lg space-y-6">
    <div>
      <p class="text-xs font-medium text-secondary-600 tracking-wide mb-1">
        {{ t("technician_meter_readings.eyebrow") }}
      </p>
      <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ t("technician_meter_readings.title") }}</h1>
    </div>

    <div
      v-if="isLoadingGenerators"
      class="h-12 rounded-lg bg-gray-50 dark:bg-white/5 animate-pulse"
    ></div>

    <template v-else-if="generators.length === 0">
      <div
        class="text-center py-12 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10 text-sm text-gray-500 dark:text-gray-400"
      >
        {{ t("technician_meter_readings.no_generators_assigned") }}
      </div>
    </template>

    <template v-else>
      <div v-if="generators.length > 1">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5"
          >{{ t("owner_meter_readings.generator_label") }}</label
        >
        <AppDropdownSelect
          v-model="selectedGeneratorId"
          :options="generatorSelectOptions"
          variant="field" width-class="w-full" match-trigger-width
        />
      </div>
      <div
        v-else
        class="bg-primary-50 dark:bg-primary-500/10 rounded-lg px-4 py-2.5 text-sm text-primary-700 dark:text-primary-300 font-medium"
      >
        <Zap class="me-1.5" aria-hidden="true" />{{ generators[0].name }}
      </div>

      <div v-if="isLoadingSubscriptions" class="space-y-2">
        <div
          v-for="i in 4"
          :key="i"
          class="h-16 rounded-lg bg-gray-50 dark:bg-white/5 animate-pulse"
        ></div>
      </div>

      <div
        v-else-if="subscriptions.length === 0"
        class="text-center py-10 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10 text-sm text-gray-500 dark:text-gray-400"
      >
        {{ t("technician_meter_readings.no_active_subscribers") }}
      </div>

      <div
        v-else
        class="bg-surface dark:bg-[#1c1e20] rounded-card border border-border dark:border-white/10 divide-y divide-border dark:divide-white/10 overflow-hidden"
      >
        <button
          v-for="sub in subscriptions"
          :key="sub.id"
          type="button"
          @click="openReadingForm(sub)"
          class="w-full flex items-center gap-4 p-4 text-start hover:bg-primary-50/40 dark:hover:bg-white/5 transition"
        >
          <div
            class="w-10 h-10 rounded-full bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center shrink-0 text-primary-600 dark:text-primary-300 font-semibold text-sm"
          >
            {{ sub.subscriber?.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-700 dark:text-gray-200 truncate">
              {{ sub.subscriber?.name }}
            </p>
            <p
              class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
              v-if="sub.subscriber_meter?.meter_number"
            >
              {{ t("technician_meter_readings.meter_number_label", { number: sub.subscriber_meter.meter_number }) }}
            </p>
          </div>
          <Gauge class="text-gray-300 dark:text-gray-600" aria-hidden="true" />
        </button>
      </div>
    </template>

    <!-- فورم تسجيل القراءة -->
    <Teleport to="body">
      <div
        v-if="readingTarget"
        class="fixed inset-0 z-50 bg-gray-700/40 backdrop-blur-[2px] flex items-center justify-center p-4"
        @click.self="readingTarget = null"
      >
        <div class="bg-surface dark:bg-[#1c1e20] rounded-card p-6 max-w-sm w-full max-h-[90vh] overflow-y-auto">
          <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-1">
            {{ t("technician_meter_readings.record_reading_title", { name: readingTarget.subscriber?.name }) }}
          </h3>
          <p
            class="text-xs text-gray-500 dark:text-gray-400 mb-4"
            v-if="readingTarget.subscriber_meter?.meter_number"
          >
            {{ t("technician_meter_readings.meter_number_label", { number: readingTarget.subscriber_meter.meter_number }) }}
          </p>

          <!-- حالة النجاح -->
          <div
            v-if="submitResult?.success && !submitResult.isQueued"
            class="bg-warning-bg text-warning text-sm rounded-lg p-4 text-center"
          >
            <Clock class="text-xl mb-1 block" aria-hidden="true" />
            {{ t("technician_meter_readings.success_message") }}
          </div>

          <!-- حالة محفوظ أوفلاين -->
          <div
            v-else-if="submitResult?.success && submitResult.isQueued"
            class="space-y-3"
          >
            <div
              class="bg-warning-bg text-warning text-sm rounded-lg p-4 text-center"
            >
              <WifiOff class="text-xl mb-1 block" aria-hidden="true" />
              {{ t("owner_meter_readings.queued_offline_message") }}
            </div>
            <button
              type="button"
              @click="readingTarget = null"
              class="w-full py-2 rounded-lg text-sm text-gray-600 dark:text-gray-300 border border-border dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition"
            >
              {{ t("technician_meter_readings.close_button") }}
            </button>
          </div>

          <!-- الفورم -->
          <form v-else @submit.prevent="handleSubmit" class="space-y-4">
            <div
              v-if="submitError"
              class="bg-danger-bg text-danger text-sm rounded-lg p-3"
            >
              {{ submitError }}
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5"
                >{{ t("owner_meter_readings.reading_date_label") }}</label
              >
              <input
                v-model="readingForm.reading_date"
                type="date"
                :max="new Date().toISOString().slice(0, 10)"
                required
                class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5"
                >{{ t("owner_meter_readings.current_reading_label") }}</label
              >
              <input
                v-model="readingForm.current_reading"
                type="number"
                step="0.01"
                min="0"
                required
                autofocus
                class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm font-mono text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              />
            </div>

            <div class="flex gap-3">
              <button
                type="button"
                @click="readingTarget = null"
                class="flex-1 py-2.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 border border-border dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition"
              >
                {{ t("common.cancel") }}
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 disabled:opacity-50 transition"
              >
                {{ isSubmitting ? t("owner_meter_readings.saving_ellipsis") : t("technician_meter_readings.save_reading_button") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
