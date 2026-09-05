<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { usePermissions } from "@/composables/usePermissions";
import { normalizeApiError } from "@/utils/normalizeApiError";
import generatorDiagnosticService from "@/services/generatorDiagnosticService";
import { useToastStore } from "@/stores/toast";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Activity, CircleAlert, LoaderCircle, Plus, WandSparkles } from "@lucide/vue";

/**
 * FIX (تدقيق شامل للوحة الأدمن): مكوّن مشترك قابل لإعادة الاستخدام لقراءات
 * تشخيص المحرك + تحليلها بالذكاء الاصطناعي، بحقول تطابق فعليًا
 * StoreGeneratorDiagnosticReadingRequest. مُستخدَم بصفحة تفاصيل المولد
 * المشتركة (لوحة الأدمن) وبصفحة "مولداتي" لمالك المولد — النموذج القديم
 * هناك (useMaintenance.submitDiagnostic، أزيل نهائيًا) كان يبعت أسماء حقول
 * مختلفة تمامًا (oil_temperature/coolant_temperature/oil_pressure/
 * battery_voltage/run_hours، وvibration_level كرقم) فكان يرجع 422 دايمًا.
 */
const props = defineProps({
  generatorId: { type: [Number, String], required: true },
  canManage: { type: Boolean, default: false },
});

const { t } = useI18n();
const { can } = usePermissions();
const toast = useToastStore();
const canRecord = props.canManage && can("generators.record");

const readings = ref([]);
const isLoading = ref(true);
const loadError = ref(null);

async function load() {
  isLoading.value = true;
  loadError.value = null;
  try {
    const { data } = await generatorDiagnosticService.list(props.generatorId);
    readings.value = data.data ?? [];
  } catch (err) {
    loadError.value = normalizeApiError(err, t("generator_diagnostics.load_error")).message;
  } finally {
    isLoading.value = false;
  }
}

const SMOKE_LEVEL_OPTIONS = computed(() => [
  { value: "none", label: t("generator_diagnostics.smoke_none") },
  { value: "light", label: t("generator_diagnostics.smoke_light") },
  { value: "heavy", label: t("generator_diagnostics.smoke_heavy") },
]);
const VIBRATION_LEVEL_OPTIONS = computed(() => [
  { value: "normal", label: t("generator_diagnostics.vibration_normal") },
  { value: "abnormal", label: t("generator_diagnostics.vibration_abnormal") },
]);

const isFormOpen = ref(false);
function emptyForm() {
  return {
    operating_hours: "",
    temperature_celsius: "",
    oil_level_percent: "",
    load_percent: "",
    voltage: "",
    frequency_hz: "",
    smoke_level: "",
    vibration_level: "",
    notes: "",
    reading_date: new Date().toISOString().slice(0, 10),
  };
}
const form = reactive(emptyForm());
const isSubmitting = ref(false);
const submitError = ref(null);

async function submitReading() {
  isSubmitting.value = true;
  submitError.value = null;
  try {
    const payload = Object.fromEntries(
      Object.entries(form).filter(([, v]) => v !== null && v !== ""),
    );
    const { data } = await generatorDiagnosticService.create(props.generatorId, payload);
    readings.value.unshift(data.data);
    Object.assign(form, emptyForm());
    isFormOpen.value = false;
    toast.show({ type: "success", title: t("generator_diagnostics.saved_toast_title"), message: t("generator_diagnostics.saved_toast_message") });
  } catch (err) {
    submitError.value = normalizeApiError(err, t("generator_diagnostics.save_failed_message")).message;
  } finally {
    isSubmitting.value = false;
  }
}

const analyzingId = ref(null);
async function analyzeReading(reading) {
  analyzingId.value = reading.id;
  try {
    await generatorDiagnosticService.analyze(reading.id);
    reading.has_analysis = true;
    toast.show({ type: "success", title: t("generator_diagnostics.analyze_success_title"), message: t("generator_diagnostics.analyze_success_message") });
  } catch (err) {
    toast.show({ type: "danger", title: t("generator_diagnostics.analyze_failed_title"), message: normalizeApiError(err, t("generator_diagnostics.analyze_failed_message")).message });
  } finally {
    analyzingId.value = null;
  }
}

onMounted(load);
</script>

<template>
  <div class="space-y-3.5">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <h3 class="text-[13.5px] font-bold flex items-center gap-2">
        <Activity class="text-[#17A2B8]" aria-hidden="true" />
        {{ t("generator_diagnostics.title") }}
      </h3>
      <button v-if="canRecord" type="button" @click="isFormOpen = !isFormOpen" class="px-3 py-1.5 rounded-full text-[11px] font-bold bg-[#8A6D1F]/10 text-[#8A6D1F] flex items-center gap-1">
        <Plus class="text-[10px]" aria-hidden="true" /> {{ t("common.add") }}
      </button>
    </div>

    <div v-if="loadError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ loadError }}</div>

    <form v-if="isFormOpen" @submit.prevent="submitReading" class="glass-card p-3.5 space-y-2.5">
      <div v-if="submitError" class="text-[11px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-2.5 py-1.5">{{ submitError }}</div>
      <div class="grid sm:grid-cols-3 gap-2.5">
        <div>
          <label class="field-label">{{ t("generator_diagnostics.operating_hours_label") }}</label>
          <input v-model="form.operating_hours" type="number" min="0" step="0.1" required class="field-input" dir="ltr" />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.temperature_label") }}</label>
          <input v-model="form.temperature_celsius" type="number" step="0.1" class="field-input" dir="ltr" />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.oil_level_label") }}</label>
          <input v-model="form.oil_level_percent" type="number" min="0" max="100" step="0.1" class="field-input" dir="ltr" />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.load_percent_label") }}</label>
          <input v-model="form.load_percent" type="number" min="0" max="100" step="0.1" class="field-input" dir="ltr" />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.voltage_label") }}</label>
          <input v-model="form.voltage" type="number" min="0" step="0.1" class="field-input" dir="ltr" />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.frequency_label") }}</label>
          <input v-model="form.frequency_hz" type="number" min="0" step="0.1" class="field-input" dir="ltr" />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.smoke_level_label") }}</label>
          <AppDropdownSelect
            v-model="form.smoke_level"
            :options="SMOKE_LEVEL_OPTIONS"
            :placeholder="'—'"
            variant="field" width-class="w-full" match-trigger-width
          />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.vibration_label") }}</label>
          <AppDropdownSelect
            v-model="form.vibration_level"
            :options="VIBRATION_LEVEL_OPTIONS"
            :placeholder="'—'"
            variant="field" width-class="w-full" match-trigger-width
          />
        </div>
        <div>
          <label class="field-label">{{ t("generator_diagnostics.reading_date_label") }}</label>
          <input v-model="form.reading_date" type="date" required class="field-input" />
        </div>
      </div>
      <textarea v-model="form.notes" rows="2" maxlength="500" :placeholder="t('generator_diagnostics.notes_placeholder')" class="field-input resize-none"></textarea>
      <div class="flex gap-2">
        <button type="submit" :disabled="isSubmitting" class="btn-fill-brand">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" />
          {{ isSubmitting ? t("generator_diagnostics.saving") : t("generator_diagnostics.save") }}
        </button>
        <button type="button" @click="isFormOpen = false" class="btn-outline-brand">{{ t("generator_diagnostics.cancel") }}</button>
      </div>
    </form>

    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 2" :key="i" class="h-16 rounded-xl thumb-loading"></div>
    </div>
    <div v-else-if="!readings.length" class="text-center py-8 text-[11.5px] text-[#9a9d97]">{{ t("generator_diagnostics.no_readings") }}</div>
    <div v-else class="space-y-1.5">
      <div v-for="r in readings" :key="r.id" class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-white/50 dark:bg-white/[0.04] border border-[#eee8da] dark:border-white/10">
        <div class="min-w-0">
          <p class="text-[12px] font-bold">{{ t("generator_diagnostics.operating_hours_label") }}: {{ r.operating_hours }}</p>
          <p class="text-[10px] text-[#9a9d97]">
            {{ r.reading_date }}
            <span v-if="r.temperature_celsius !== null"> · {{ r.temperature_celsius }}°C</span>
            <span v-if="r.vibration_level"> · {{ t(`generator_diagnostics.vibration_${r.vibration_level}`) }}</span>
          </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <span v-if="r.has_analysis" class="status-chip chip-success">{{ t("generator_diagnostics.analyzed_badge") }}</span>
          <button
            v-else-if="canRecord" type="button" @click="analyzeReading(r)" :disabled="analyzingId === r.id"
            class="action-btn action-btn--view !text-[#17A2B8]" :title="t('generator_diagnostics.analyze_button')"
          >
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="analyzingId === r.id" /><WandSparkles v-else aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
