<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { reactive, ref, onMounted, watch, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useTechnicianMeterReadings } from "@/composables/useTechnicianMeterReadings";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Clock, Gauge, LoaderCircle, Save, WifiOff, X, Zap } from "@lucide/vue";

const { t } = useI18n();
const toast = useToastStore();

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

const meterImageFile = ref(null);
const meterImagePreview = ref(null);
const meterImageInput = ref(null);

function onMeterImageChange(e) {
  const file = e.target.files?.[0];
  if (!file) {
    meterImageFile.value = null;
    meterImagePreview.value = null;
    return;
  }
  meterImageFile.value = file;
  meterImagePreview.value = URL.createObjectURL(file);
}

function clearMeterImage() {
  meterImageFile.value = null;
  meterImagePreview.value = null;
  if (meterImageInput.value) meterImageInput.value.value = "";
}

function openReadingForm(subscription) {
  readingTarget.value = subscription;
  readingForm.current_reading = "";
  readingForm.reading_date = new Date().toISOString().slice(0, 10);
  submitError.value = null;
  submitResult.value = null;
  clearMeterImage();
}

async function handleSubmit() {
  isSubmitting.value = true;
  submitError.value = null;

  try {
    const result = await submitReading(
      {
        subscription_id: readingTarget.value.id,
        reading_date: readingForm.reading_date,
        current_reading: readingForm.current_reading,
      },
      meterImageFile.value,
    );

    submitResult.value = {
      success: true,
      isQueued: result.isQueued,
      status: result.status,
    };

    if (!result.isQueued) {
      if (result.attachmentError) {
        toast.show({
          type: "warning",
          title: t("owner_meter_readings.attachment_upload_error_title"),
          message: normalizeApiError(result.attachmentError, t("owner_meter_readings.attachment_upload_error")).message,
        });
      }
      clearMeterImage();
      setTimeout(() => {
        readingTarget.value = null;
      }, 1800);
    }
  } catch (err) {
    submitError.value = normalizeApiError(err, t("owner_meter_readings.submit_error")).message;
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
  <div class="space-y-5">
    <!-- ===== رأس الصفحة ===== -->
    <section v-reveal class="glass-card p-5 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-56 h-56 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[90px] pointer-events-none"></div>
      <div class="relative flex items-center gap-3">
        <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base shrink-0">
          <Gauge aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-0.5">
            {{ t("technician_meter_readings.eyebrow") }}
          </p>
          <h1 class="text-lg font-extrabold truncate">{{ t("technician_meter_readings.title") }}</h1>
        </div>
      </div>
    </section>

    <div v-if="isLoadingGenerators" class="h-14 rounded-2xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>

    <template v-else-if="generators.length === 0">
      <div class="glass-card border-dashed p-10 text-center text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
        {{ t("technician_meter_readings.no_generators_assigned") }}
      </div>
    </template>

    <template v-else>
      <section v-reveal v-if="generators.length > 1" class="glass-card p-4">
        <label class="field-label">{{ t("owner_meter_readings.generator_label") }}</label>
        <AppDropdownSelect v-model="selectedGeneratorId" :options="generatorSelectOptions" variant="field" width-class="w-full" match-trigger-width />
      </section>
      <div v-else class="flex items-center gap-2 bg-[#EBF1E7] dark:bg-white/5 rounded-xl px-4 py-2.5 text-[12.5px] font-bold text-[#3E582E] dark:text-[#a8d19a]">
        <Zap aria-hidden="true" />{{ generators[0].name }}
      </div>

      <div v-if="isLoadingSubscriptions" class="space-y-2">
        <div v-for="i in 4" :key="i" class="h-16 rounded-xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>
      </div>

      <div v-else-if="subscriptions.length === 0" class="glass-card border-dashed p-10 text-center text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
        {{ t("technician_meter_readings.no_active_subscribers") }}
      </div>

      <section v-reveal v-else class="glass-card divide-y divide-[#f0ece0] dark:divide-white/5 overflow-hidden">
        <button
          v-for="sub in subscriptions"
          :key="sub.id"
          type="button"
          @click="openReadingForm(sub)"
          class="w-full flex items-center gap-4 p-4 text-start hover:bg-[#EBF1E7]/50 dark:hover:bg-white/5 transition"
        >
          <div class="w-10 h-10 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center shrink-0 text-[#3E582E] dark:text-[#a8d19a] font-bold text-[13px]">
            {{ sub.subscriber?.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-bold text-[12.5px] truncate">{{ sub.subscriber?.name }}</p>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5" v-if="sub.subscriber_meter?.meter_number">
              {{ t("technician_meter_readings.meter_number_label", { number: sub.subscriber_meter.meter_number }) }}
            </p>
          </div>
          <Gauge class="text-[#c9c3b2] dark:text-white/20" aria-hidden="true" />
        </button>
      </section>
    </template>

    <!-- ===== نافذة تسجيل القراءة ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="readingTarget" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="readingTarget = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Gauge aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("technician_meter_readings.record_reading_title", { name: readingTarget.subscriber?.name }) }}</h3>
                  <p v-if="readingTarget.subscriber_meter?.meter_number" class="modal-head-brand__subtitle">
                    {{ t("technician_meter_readings.meter_number_label", { number: readingTarget.subscriber_meter.meter_number }) }}
                  </p>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="readingTarget = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <!-- حالة النجاح -->
            <div v-if="submitResult?.success && !submitResult.isQueued" class="p-6 text-center text-[12.5px] text-[#8A6D1F] dark:text-[#D4AF37]">
              <Clock class="text-2xl mb-2 block mx-auto" aria-hidden="true" />
              {{ t("technician_meter_readings.success_message") }}
            </div>

            <!-- حالة محفوظ أوفلاين -->
            <div v-else-if="submitResult?.success && submitResult.isQueued" class="p-5 space-y-3">
              <div class="text-center text-[12.5px] text-[#8A6D1F] dark:text-[#D4AF37] bg-[#8A6D1F]/10 border border-[#8A6D1F]/25 rounded-xl p-4">
                <WifiOff class="text-xl mb-1 block mx-auto" aria-hidden="true" />
                {{ t("owner_meter_readings.queued_offline_message") }}
              </div>
              <button type="button" @click="readingTarget = null" class="btn-outline-brand w-full justify-center">
                {{ t("technician_meter_readings.close_button") }}
              </button>
            </div>

            <!-- الفورم -->
            <form v-else @submit.prevent="handleSubmit" class="p-5 space-y-3.5">
              <div v-if="submitError" class="alert-box"><X class="shrink-0" aria-hidden="true" /> {{ submitError }}</div>

              <div>
                <label class="field-label">{{ t("owner_meter_readings.reading_date_label") }}</label>
                <input v-model="readingForm.reading_date" type="date" :max="new Date().toISOString().slice(0, 10)" required class="field-input" />
              </div>

              <div>
                <label class="field-label">{{ t("owner_meter_readings.current_reading_label") }}</label>
                <input v-model="readingForm.current_reading" type="number" step="0.01" min="0" required autofocus class="field-input font-mono" />
              </div>

              <div>
                <label class="field-label">{{ t("owner_meter_readings.meter_image_label") }}</label>
                <div v-if="!meterImagePreview" class="flex items-center gap-2">
                  <input
                    ref="meterImageInput"
                    type="file"
                    accept="image/*"
                    capture="environment"
                    @change="onMeterImageChange"
                    class="field-input !py-1.5 text-[11px]"
                  />
                </div>
                <div v-else class="flex items-center gap-2.5">
                  <img :src="meterImagePreview" class="w-14 h-14 rounded-xl object-cover border border-[#e7e2d6] dark:border-white/10" />
                  <button type="button" @click="clearMeterImage" class="text-[11px] font-bold text-[#D9534F] hover:underline">
                    {{ t("owner_meter_readings.remove_image") }}
                  </button>
                </div>
                <p class="text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("owner_meter_readings.meter_image_hint") }}</p>
              </div>

              <div class="modal-footer-brand !px-0 !pb-0 !border-0 !bg-transparent">
                <button type="button" @click="readingTarget = null" class="btn-outline-brand">{{ t("common.cancel") }}</button>
                <button type="submit" :disabled="isSubmitting" class="btn-fill-brand">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Save aria-hidden="true" v-else />
                  {{ isSubmitting ? t("owner_meter_readings.saving_ellipsis") : t("technician_meter_readings.save_reading_button") }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>