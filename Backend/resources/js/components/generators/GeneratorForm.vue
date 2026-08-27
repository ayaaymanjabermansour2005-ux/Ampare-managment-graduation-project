<script setup>
import { ref, reactive, watch, computed } from "vue";
import { useI18n } from "vue-i18n";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleAlert, Info, LoaderCircle, Pencil, PlugZap, X } from "@lucide/vue";


const props = defineProps({
  open: { type: Boolean, default: false },
  generator: { type: Object, default: null },
  isSaving: { type: Boolean, default: false },
  serverError: { type: Object, default: null },
});

const emit = defineEmits(["close", "submit"]);

const { t } = useI18n();

const isEditing = computed(() => !!props.generator);
const coordinates = ref(null);
const form = reactive({
  name: "",
  name_en: "",
  price_per_kw: "",
  currency: "ILS",
  capacity_kw: "",
  lines_count: 1,
  location_id: "",
  status: "active",
  operating_schedule: "day",
  operating_start_time: "",
  operating_end_time: "",
});

const STATUS_OPTIONS = ["active", "maintenance", "inactive"];

// خيارات الدروب داون المخصّص (AppDropdownSelect) — نفس هوية الموقع
// (السهم الذهبي/التحديد الأخضر) بدل عناصر <select> الافتراضية، لتتطابق
// بصريًا مع AdminGeneratorFormModal.vue.
const currencyOptions = computed(() => [
  { value: "ILS", label: t("owner_generators.form.currency_ils") },
  { value: "USD", label: t("owner_generators.form.currency_usd") },
]);
const scheduleOptions = computed(() => [
  { value: "day", label: t("owner_generators.schedule.day") },
  { value: "night", label: t("owner_generators.schedule.night") },
  { value: "24h", label: t("owner_generators.schedule.24h") },
  { value: "custom", label: t("owner_generators.schedule.custom") },
]);

function resetForm(source) {
  form.name = source?.name ?? "";
  form.name_en = source?.name_en ?? "";
  form.price_per_kw = source?.price_per_kw ?? "";
  form.currency = source?.currency ?? "ILS";
  form.capacity_kw = source?.capacity_kw ?? "";
  form.lines_count = source?.lines_count ?? 1;
  form.location_id = source?.location?.id ?? "";
  form.status = source?.status ?? "active";
  form.operating_schedule = source?.operating_schedule ?? "day";
  form.operating_start_time = source?.operating_start_time ?? "";
  form.operating_end_time = source?.operating_end_time ?? "";
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) resetForm(props.generator);
  },
);

function fieldError(field) {
  return props.serverError?.errors?.[field]?.[0] ?? null;
}

function handleSubmit() {
  const payload = { ...form };
  if (!payload.capacity_kw) delete payload.capacity_kw;
  if (!payload.location_id) delete payload.location_id;
  if (payload.operating_schedule !== "custom") {
    delete payload.operating_start_time;
    delete payload.operating_end_time;
  }
  if (coordinates.value) {
    payload.latitude = coordinates.value.latitude;
    payload.longitude = coordinates.value.longitude;
  }
  emit("submit", payload);
}
</script>

<template>
  <Teleport to="body">
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="emit('close')">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[92vh] flex flex-col shadow-2xl overflow-hidden">
          <div class="modal-head-brand modal-head-brand--green">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Pencil aria-hidden="true" v-if="isEditing" /><PlugZap aria-hidden="true" v-else /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ isEditing ? t("owner_generators.form.edit_title") : t("owner_generators.form.add_title") }}</h3>
              </div>
            </div>
            <button type="button" class="modal-head-brand__close" :aria-label="t('owner_generators.close')" @click="emit('close')">
              <X aria-hidden="true" />
            </button>
          </div>

          <form class="p-5 grid sm:grid-cols-2 gap-4 overflow-y-auto" @submit.prevent="handleSubmit">
            <div v-if="serverError?.message && !serverError?.errors" class="sm:col-span-2 alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ serverError.message }}
            </div>

            <div class="sm:col-span-2">
              <label class="field-label">{{ t("owner_generators.form.name_label") }}</label>
              <input
                v-model="form.name" type="text" required maxlength="150"
                :placeholder="t('owner_generators.form.name_placeholder')"
                class="field-input"
              />
              <span v-if="fieldError('name')" class="form-error">{{ fieldError("name") }}</span>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label">{{ t("owner_generators.form.name_en_label") }}</label>
              <input
                v-model="form.name_en" type="text" maxlength="150" dir="ltr"
                :placeholder="t('owner_generators.form.name_en_placeholder')"
                class="field-input"
              />
              <span v-if="fieldError('name_en')" class="form-error">{{ fieldError("name_en") }}</span>
              <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ t("owner_generators.form.name_en_hint") }}</p>
            </div>

            <div>
              <label class="field-label">{{ t("owner_generators.form.price_label") }}</label>
              <input v-model="form.price_per_kw" type="number" step="0.01" min="0" required class="field-input" />
              <span v-if="fieldError('price_per_kw')" class="form-error">{{ fieldError("price_per_kw") }}</span>
            </div>
            <div>
              <label class="field-label">{{ t("owner_generators.form.currency_label") }}</label>
              <AppDropdownSelect
                v-model="form.currency" :options="currencyOptions"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>

            <div>
              <label class="field-label">{{ t("owner_generators.form.capacity_label") }} <span class="text-[#9a9d97] dark:text-[#8f938a] font-normal">(kW)</span></label>
              <input v-model="form.capacity_kw" type="number" min="1" :placeholder="t('owner_generators.form.capacity_placeholder')" class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("owner_generators.form.lines_label") }}</label>
              <input v-model.number="form.lines_count" type="number" min="1" max="20" required class="field-input" />
              <span v-if="fieldError('lines_count')" class="form-error">{{ fieldError("lines_count") }}</span>
            </div>

            <div class="sm:col-span-2">
              <label class="field-label">{{ t("owner_generators.form.schedule_label") }}</label>
              <AppDropdownSelect
                v-model="form.operating_schedule" :options="scheduleOptions"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>

            <template v-if="form.operating_schedule === 'custom'">
              <div>
                <label class="field-label">{{ t("owner_generators.form.start_time_label") }}</label>
                <input v-model="form.operating_start_time" type="time" required class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t("owner_generators.form.end_time_label") }}</label>
                <input v-model="form.operating_end_time" type="time" required class="field-input" />
                <span v-if="fieldError('operating_end_time')" class="form-error">{{ fieldError("operating_end_time") }}</span>
              </div>
            </template>

            <div v-if="isEditing" class="sm:col-span-2">
              <label class="field-label">{{ t("owner_generators.form.status_label") }}</label>
              <div class="grid grid-cols-3 gap-2">
                <label
                  v-for="opt in STATUS_OPTIONS" :key="opt"
                  class="cursor-pointer text-center text-[11.5px] font-bold rounded-xl border py-2 transition"
                  :class="form.status === opt ? 'border-[#52733D] bg-[#EBF1E7] dark:bg-white/5 text-[#52733D] dark:text-[#8cc35a]' : 'border-[#e7e2d6] dark:border-white/10 text-[#6B6B6B] dark:text-[#a8aaa5] hover:border-[#D4AF37]/50'"
                >
                  <input v-model="form.status" type="radio" :value="opt" class="sr-only" />
                  {{ t(`owner_generators.status.${opt}`) }}
                </label>
              </div>
            </div>

            <p class="sm:col-span-2 text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#EBF1E7] dark:bg-white/5 rounded-xl p-3">
              <Info class="me-1" aria-hidden="true" />
              {{ t("owner_generators.form.map_pending_note") }}
            </p>
          </form>

          <div class="modal-footer-brand">
            <button type="button" class="btn-outline-brand" @click="emit('close')">{{ t("owner_generators.form.cancel") }}</button>
            <button type="button" class="btn-fill-brand" :disabled="isSaving" @click="handleSubmit">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" />
              {{ isSaving ? t("owner_generators.form.saving") : (isEditing ? t("owner_generators.form.save") : t("owner_generators.form.create")) }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>