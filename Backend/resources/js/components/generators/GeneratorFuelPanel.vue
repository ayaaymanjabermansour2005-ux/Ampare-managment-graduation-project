<script setup>
import { onMounted, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { usePermissions } from "@/composables/usePermissions";
import { normalizeApiError } from "@/utils/normalizeApiError";
import fuelService from "@/services/fuelService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleAlert, Droplet, Fuel, LoaderCircle, Plus } from "@lucide/vue";

/**
 * FIX (تدقيق شامل للوحة الأدمن): تتبّع الوقود (شراء/قراءة الخزان) كان جاهزًا
 * بالكامل بالباك اند (FuelController + FuelService + Policy، صلاحية
 * generators.record) بدون أي واجهة تستخدمه إطلاقًا — لا بلوحة الأدمن ولا حتى
 * بلوحة مالك المولد (useMaintenance.submitFuelPurchase/submitFuelReading
 * كانتا معرَّفتين وغير مستخدَمتين). مكوّن مستقل قابل لإعادة الاستخدام —
 * بنفس نمط GeneratorHealthReports/GeneratorTimeline — عشان يظهر بصفحة تفاصيل
 * المولد المشتركة بين كل الأدوار.
 */
const props = defineProps({
  generatorId: { type: [Number, String], required: true },
  canManage: { type: Boolean, default: false },
});

const { t } = useI18n();
const { can } = usePermissions();
const canRecord = props.canManage && can("generators.record");

const purchases = ref([]);
const readings = ref([]);
const status = ref(null);
const isLoading = ref(true);
const loadError = ref(null);

async function load() {
  isLoading.value = true;
  loadError.value = null;
  try {
    const [purchasesRes, readingsRes, statusRes] = await Promise.all([
      fuelService.purchases(props.generatorId),
      fuelService.readings(props.generatorId),
      fuelService.status(props.generatorId).catch(() => null),
    ]);
    purchases.value = purchasesRes.data.data ?? [];
    readings.value = readingsRes.data.data ?? [];
    status.value = statusRes?.data?.data ?? null;
  } catch (err) {
    loadError.value = normalizeApiError(err, t("generator_fuel.load_error")).message;
  } finally {
    isLoading.value = false;
  }
}

const activeTab = ref("readings"); // 'readings' | 'purchases'

/* ---------------- تسجيل قراءة خزان جديدة ---------------- */
const isReadingFormOpen = ref(false);
function emptyReadingForm() {
  return { tank_level_liters: "", meter_hours: "", reading_date: new Date().toISOString().slice(0, 10), notes: "" };
}
const readingForm = reactive(emptyReadingForm());
const isSubmittingReading = ref(false);
const readingFormError = ref(null);

async function submitReading() {
  isSubmittingReading.value = true;
  readingFormError.value = null;
  try {
    const formData = new FormData();
    Object.entries(readingForm).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== "") formData.append(key, value);
    });
    const { data } = await fuelService.storeReading(props.generatorId, formData);
    readings.value.unshift(data.data);
    Object.assign(readingForm, emptyReadingForm());
    isReadingFormOpen.value = false;
    fuelService.status(props.generatorId).then((res) => (status.value = res.data.data)).catch(() => {});
  } catch (err) {
    readingFormError.value = normalizeApiError(err, t("generator_fuel.save_error")).message;
  } finally {
    isSubmittingReading.value = false;
  }
}

/* ---------------- تسجيل عملية شراء وقود جديدة ---------------- */
const CURRENCY_OPTIONS = [
  { value: "ILS", label: "ILS" },
  { value: "USD", label: "USD" },
];
const isPurchaseFormOpen = ref(false);
function emptyPurchaseForm() {
  return { liters: "", cost_amount: "", currency: "ILS", purchased_at: new Date().toISOString().slice(0, 10), notes: "" };
}
const purchaseForm = reactive(emptyPurchaseForm());
const isSubmittingPurchase = ref(false);
const purchaseFormError = ref(null);

async function submitPurchase() {
  isSubmittingPurchase.value = true;
  purchaseFormError.value = null;
  try {
    const formData = new FormData();
    Object.entries(purchaseForm).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== "") formData.append(key, value);
    });
    const { data } = await fuelService.storePurchase(props.generatorId, formData);
    purchases.value.unshift(data.data);
    Object.assign(purchaseForm, emptyPurchaseForm());
    isPurchaseFormOpen.value = false;
  } catch (err) {
    purchaseFormError.value = normalizeApiError(err, t("generator_fuel.save_error")).message;
  } finally {
    isSubmittingPurchase.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="space-y-3.5">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <h3 class="text-[13.5px] font-bold flex items-center gap-2">
        <Fuel class="text-[#8A6D1F]" aria-hidden="true" />
        {{ t("generator_fuel.title") }}
      </h3>
      <span v-if="status?.is_low" class="status-chip chip-danger">
        <CircleAlert aria-hidden="true" /> {{ t("generator_fuel.low_level_warning") }}
      </span>
    </div>

    <div v-if="loadError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ loadError }}</div>

    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 2" :key="i" class="h-16 rounded-xl thumb-loading"></div>
    </div>

    <template v-else>
      <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 w-fit">
        <button type="button" @click="activeTab = 'readings'" class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors" :class="activeTab === 'readings' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'">
          {{ t("generator_fuel.tab_readings") }}
        </button>
        <button type="button" @click="activeTab = 'purchases'" class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors" :class="activeTab === 'purchases' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'">
          {{ t("generator_fuel.tab_purchases") }}
        </button>
        <button v-if="canRecord" type="button" @click="activeTab === 'readings' ? (isReadingFormOpen = true) : (isPurchaseFormOpen = true)" class="ms-1 px-3 py-1.5 rounded-full text-[11px] font-bold bg-[#8A6D1F]/10 text-[#8A6D1F] flex items-center gap-1">
          <Plus class="text-[10px]" aria-hidden="true" /> {{ t("common.add") }}
        </button>
      </div>

      <!-- ===== قراءات الخزان ===== -->
      <div v-if="activeTab === 'readings'">
        <form v-if="isReadingFormOpen" @submit.prevent="submitReading" class="glass-card p-3.5 space-y-2.5 mb-3">
          <div v-if="readingFormError" class="text-[11px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-2.5 py-1.5">{{ readingFormError }}</div>
          <div class="grid sm:grid-cols-2 gap-2.5">
            <div>
              <label class="field-label">{{ t("generator_fuel.tank_level_label") }}</label>
              <input v-model="readingForm.tank_level_liters" type="number" min="0" step="0.01" required class="field-input" dir="ltr" />
            </div>
            <div>
              <label class="field-label">{{ t("generator_fuel.meter_hours_label") }} <span class="text-[#9a9d97] font-normal">({{ t("common.optional") }})</span></label>
              <input v-model="readingForm.meter_hours" type="number" min="0" step="0.01" class="field-input" dir="ltr" />
            </div>
            <div>
              <label class="field-label">{{ t("generator_fuel.reading_date_label") }}</label>
              <input v-model="readingForm.reading_date" type="date" required class="field-input" />
            </div>
          </div>
          <textarea v-model="readingForm.notes" rows="2" maxlength="500" :placeholder="t('generator_fuel.notes_placeholder')" class="field-input resize-none"></textarea>
          <div class="flex gap-2">
            <button type="submit" :disabled="isSubmittingReading" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmittingReading" />
              {{ isSubmittingReading ? t("common.saving") : t("common.save") }}
            </button>
            <button type="button" @click="isReadingFormOpen = false" class="btn-outline-brand">{{ t("common.cancel") }}</button>
          </div>
        </form>

        <div v-if="!readings.length" class="text-center py-8 text-[11.5px] text-[#9a9d97]">{{ t("generator_fuel.no_readings") }}</div>
        <div v-else class="space-y-1.5">
          <div v-for="r in readings" :key="r.id" class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-white/50 dark:bg-white/[0.04] border border-[#eee8da] dark:border-white/10">
            <div class="flex items-center gap-2 min-w-0">
              <Droplet class="text-[#17A2B8] text-[11px] shrink-0" aria-hidden="true" />
              <div class="min-w-0">
                <p class="text-[12px] font-bold">{{ r.tank_level_liters }} {{ t("generator_fuel.liters_unit") }}</p>
                <p class="text-[10px] text-[#9a9d97]">{{ r.reading_date }}<span v-if="r.meter_hours"> · {{ r.meter_hours }} {{ t("generator_fuel.hours_unit") }}</span></p>
              </div>
            </div>
            <span v-if="r.recorded_by_name" class="text-[10px] text-[#9a9d97] shrink-0">{{ r.recorded_by_name }}</span>
          </div>
        </div>
      </div>

      <!-- ===== مشتريات الوقود ===== -->
      <div v-else>
        <form v-if="isPurchaseFormOpen" @submit.prevent="submitPurchase" class="glass-card p-3.5 space-y-2.5 mb-3">
          <div v-if="purchaseFormError" class="text-[11px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-2.5 py-1.5">{{ purchaseFormError }}</div>
          <div class="grid sm:grid-cols-2 gap-2.5">
            <div>
              <label class="field-label">{{ t("generator_fuel.liters_label") }}</label>
              <input v-model="purchaseForm.liters" type="number" min="0.1" step="0.01" required class="field-input" dir="ltr" />
            </div>
            <div>
              <label class="field-label">{{ t("generator_fuel.cost_amount_label") }}</label>
              <input v-model="purchaseForm.cost_amount" type="number" min="0" step="0.01" required class="field-input" dir="ltr" />
            </div>
            <div>
              <label class="field-label">{{ t("generator_fuel.currency_label") }}</label>
              <AppDropdownSelect
                v-model="purchaseForm.currency"
                :options="CURRENCY_OPTIONS"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>
            <div>
              <label class="field-label">{{ t("generator_fuel.purchased_at_label") }}</label>
              <input v-model="purchaseForm.purchased_at" type="date" required class="field-input" />
            </div>
          </div>
          <textarea v-model="purchaseForm.notes" rows="2" maxlength="500" :placeholder="t('generator_fuel.notes_placeholder')" class="field-input resize-none"></textarea>
          <div class="flex gap-2">
            <button type="submit" :disabled="isSubmittingPurchase" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmittingPurchase" />
              {{ isSubmittingPurchase ? t("common.saving") : t("common.save") }}
            </button>
            <button type="button" @click="isPurchaseFormOpen = false" class="btn-outline-brand">{{ t("common.cancel") }}</button>
          </div>
        </form>

        <div v-if="!purchases.length" class="text-center py-8 text-[11.5px] text-[#9a9d97]">{{ t("generator_fuel.no_purchases") }}</div>
        <div v-else class="space-y-1.5">
          <div v-for="p in purchases" :key="p.id" class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-white/50 dark:bg-white/[0.04] border border-[#eee8da] dark:border-white/10">
            <div class="flex items-center gap-2 min-w-0">
              <Fuel class="text-[#8A6D1F] text-[11px] shrink-0" aria-hidden="true" />
              <div class="min-w-0">
                <p class="text-[12px] font-bold">{{ p.liters }} {{ t("generator_fuel.liters_unit") }} — {{ p.cost_amount }} {{ p.currency }}</p>
                <p class="text-[10px] text-[#9a9d97]">{{ p.purchased_at }}</p>
              </div>
            </div>
            <span v-if="p.recorded_by_name" class="text-[10px] text-[#9a9d97] shrink-0">{{ p.recorded_by_name }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
