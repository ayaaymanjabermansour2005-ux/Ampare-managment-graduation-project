<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Check, CircleAlert, Factory, Fuel, LoaderCircle, Pencil, PlugZap, Wrench, X, Zap } from "@lucide/vue";


/**
 * نافذة إضافة/تعديل مولد بهوية لوحة تحكم الأدمن (modal-head-brand) —
 * مستخرَجة من GeneratorsManagementView.vue لتُستخدَم من أي صفحة أدمن
 * تحتاج نفس نموذج إضافة/تعديل المولد (مثال: GeneratorsManagementView،
 * GeneratorOwnersView) دون تكرار نفس المنطق/القالب في أكثر من مكان.
 */
const props = defineProps({
  open: { type: Boolean, default: false },
  generator: { type: Object, default: null }, // null = وضع الإضافة، كائن = وضع التعديل
  owners: { type: Array, default: () => [] },
  isSaving: { type: Boolean, default: false },
  saveError: { type: Object, default: null },
});

const emit = defineEmits(["close", "submit"]);

const { t } = useI18n();

const isEditMode = computed(() => !!props.generator);

function emptyForm() {
  return {
    name: "", name_en: "", price_per_kw: "", currency: "ILS", capacity_kw: "", lines_count: 1,
    fuel_type: "diesel", notes: "", operating_schedule: "24h", operating_start_time: "", operating_end_time: "",
    latitude: "", longitude: "", owner_id: "", status: "active",
    manufacturer: "", model: "", serial_number: "",
    rated_voltage: "", rated_frequency_hz: "", phase_count: "",
    rated_load_kw: "", service_interval_hours: "", next_service_due_at: "", installed_at: "",
  };
}

const form = ref(emptyForm());

function syncFormFromProps() {
  const g = props.generator;
  if (!g) {
    form.value = emptyForm();
    return;
  }
  form.value = {
    name: g.name, name_en: g.name_en ?? "", price_per_kw: g.price_per_kw, currency: g.currency,
    capacity_kw: g.capacity_kw ?? "", lines_count: g.lines_count ?? 1,
    fuel_type: g.fuel_type ?? "diesel",
    notes: g.notes ?? "",
    operating_schedule: g.operating_schedule, operating_start_time: g.operating_start_time ?? "",
    operating_end_time: g.operating_end_time ?? "",
    latitude: g.location?.latitude ?? "", longitude: g.location?.longitude ?? "",
    owner_id: g.owner?.id ?? "", status: g.status,
    manufacturer: g.manufacturer ?? "", model: g.model ?? "", serial_number: g.serial_number ?? "",
    rated_voltage: g.rated_voltage ?? "", rated_frequency_hz: g.rated_frequency_hz ?? "", phase_count: g.phase_count ?? "",
    rated_load_kw: g.rated_load_kw ?? "", service_interval_hours: g.service_interval_hours ?? "",
    next_service_due_at: g.next_service_due_at ?? "", installed_at: g.installed_at ?? "",
  };
}

watch(() => props.open, (isOpen) => { if (isOpen) syncFormFromProps(); });

const ownerOptions = computed(() => props.owners.map((o) => ({ value: o.id, label: o.name })));
const currencyOptions = computed(() => [
  { value: "ILS", label: t("owner_generators.form.currency_ils") },
  { value: "USD", label: t("owner_generators.form.currency_usd") },
]);
const fuelTypeOptions = computed(() => [
  { value: "diesel", label: t("dashboard.fuel_diesel") },
  { value: "gas", label: t("dashboard.fuel_gas") },
  { value: "petrol", label: t("dashboard.fuel_petrol") },
  { value: "dual", label: t("dashboard.fuel_dual") },
]);
const scheduleOptions = computed(() => [
  { value: "24h", label: t("dashboard.schedule_24h") },
  { value: "day", label: t("dashboard.schedule_day") },
  { value: "night", label: t("dashboard.schedule_night") },
  { value: "custom", label: t("dashboard.schedule_custom") },
]);
const frequencyOptions = computed(() => [
  { value: 50, label: t("generator_form_modal.frequency_50hz") },
  { value: 60, label: t("generator_form_modal.frequency_60hz") },
]);
const phaseOptions = computed(() => [
  { value: 1, label: t("generator_form_modal.phase_single") },
  { value: 3, label: t("generator_form_modal.phase_three") },
]);
const statusOptions = computed(() => [
  { value: "active", label: t("status.active") },
  { value: "maintenance", label: t("status.maintenance") },
  { value: "inactive", label: t("status.inactive") },
  { value: "pending_verification", label: t("status.pending_verification") },
  { value: "rejected", label: t("status.rejected") },
]);

const saveErrorMessage = computed(() => {
  if (!props.saveError) return null;
  const errors = props.saveError.errors;
  if (errors && typeof errors === "object") {
    const firstMessages = Object.values(errors).flat();
    if (firstMessages.length) return firstMessages.join(" — ");
  }
  return props.saveError.message ?? t("generator_form_modal.could_not_save_generator");
});

function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}
function timeAgo(str) {
  if (!str) return "-";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}

function close() {
  if (props.isSaving) return;
  emit("close");
}

function handleSubmit() {
  const payload = {
    name: form.value.name,
    name_en: form.value.name_en || null,
    price_per_kw: Number(form.value.price_per_kw),
    currency: form.value.currency,
    capacity_kw: form.value.capacity_kw ? Number(form.value.capacity_kw) : null,
    lines_count: Number(form.value.lines_count) || 1,
    fuel_type: form.value.fuel_type,
    notes: form.value.notes || null,
    operating_schedule: form.value.operating_schedule,
    manufacturer: form.value.manufacturer || null,
    model: form.value.model || null,
    serial_number: form.value.serial_number || null,
    rated_voltage: form.value.rated_voltage ? Number(form.value.rated_voltage) : null,
    rated_frequency_hz: form.value.rated_frequency_hz ? Number(form.value.rated_frequency_hz) : null,
    phase_count: form.value.phase_count ? Number(form.value.phase_count) : null,
    rated_load_kw: form.value.rated_load_kw ? Number(form.value.rated_load_kw) : null,
    service_interval_hours: form.value.service_interval_hours ? Number(form.value.service_interval_hours) : null,
    next_service_due_at: form.value.next_service_due_at || null,
    installed_at: form.value.installed_at || null,
  };
  if (form.value.operating_schedule === "custom") {
    payload.operating_start_time = form.value.operating_start_time;
    payload.operating_end_time = form.value.operating_end_time;
  }
  if (form.value.latitude && form.value.longitude) {
    payload.latitude = Number(form.value.latitude);
    payload.longitude = Number(form.value.longitude);
  }
  if (isEditMode.value) {
    payload.status = form.value.status;
  } else {
    payload.owner_id = Number(form.value.owner_id);
  }
  emit("submit", payload);
}
</script>

<template>
  <Teleport to="body">
    <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="close">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <!-- Header -->
          <div class="modal-head-brand modal-head-brand--green shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Pencil aria-hidden="true" v-if="isEditMode" /><PlugZap aria-hidden="true" v-else /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ isEditMode ? $t("owner_generators.form.edit_title") : $t("owner_generators.form.add_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ form.name || $t("generator_form_modal.new_generator_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="close" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleSubmit" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="saveError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveErrorMessage }}
            </div>

            <div>
              <label class="field-label">{{ $t("owner_generators.form.name_label") }}</label>
              <input v-model="form.name" required type="text" class="field-input" />
            </div>

            <div>
              <label class="field-label">{{ $t("owner_generators.form.name_en_label") }}</label>
              <input v-model="form.name_en" type="text" dir="ltr" class="field-input" />
              <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1">
                {{ $t("generator_form_modal.name_en_hint") }}
              </p>
            </div>

            <div v-if="!isEditMode">
              <label class="field-label">{{ $t("dashboard.owner") }}</label>
              <AppDropdownSelect
                v-model="form.owner_id"
                :options="ownerOptions"
                :placeholder="$t('generator_form_modal.select_owner_placeholder')"
                variant="field"
                width-class="w-full"
                match-trigger-width
              />
            </div>

            <div class="grid sm:grid-cols-2 gap-3.5">
              <div>
                <label class="field-label">{{ $t("dashboard.price_per_kw_label") }}</label>
                <input v-model="form.price_per_kw" required type="number" min="0" step="0.01" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ $t("dashboard.currency") }}</label>
                <AppDropdownSelect v-model="form.currency" :options="currencyOptions" variant="field" width-class="w-full" match-trigger-width />
              </div>

              <div>
                <label class="field-label">{{ $t("dashboard.capacity_kw_label") }}</label>
                <input v-model="form.capacity_kw" type="number" min="1" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ $t("generator_form_modal.lines_count_label") }}</label>
                <input v-model="form.lines_count" type="number" min="1" max="20" class="field-input" />
              </div>

              <div>
                <label class="field-label">{{ $t("dashboard.fuel_type_label") }}</label>
                <AppDropdownSelect v-model="form.fuel_type" :options="fuelTypeOptions" variant="field" width-class="w-full" match-trigger-width />
              </div>
              <div>
                <label class="field-label">{{ $t("owner_generators.form.schedule_label") }}</label>
                <AppDropdownSelect v-model="form.operating_schedule" :options="scheduleOptions" variant="field" width-class="w-full" match-trigger-width />
              </div>

              <template v-if="form.operating_schedule === 'custom'">
                <div>
                  <label class="field-label">{{ $t("owner_generators.form.start_time_label") }}</label>
                  <input v-model="form.operating_start_time" type="time" required class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("owner_generators.form.end_time_label") }}</label>
                  <input v-model="form.operating_end_time" type="time" required class="field-input" />
                </div>
              </template>

              <div>
                <label class="field-label">{{ $t("dashboard.latitude_optional") }}</label>
                <input v-model="form.latitude" type="number" step="any" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ $t("dashboard.longitude_optional") }}</label>
                <input v-model="form.longitude" type="number" step="any" class="field-input" />
              </div>

              <div :class="isEditMode ? '' : 'sm:col-span-2'">
                <label class="field-label">{{ $t("dashboard.status") }}</label>
                <AppDropdownSelect v-model="form.status" :options="statusOptions" variant="field" width-class="w-full" match-trigger-width />
              </div>

              <!-- بادج قراءة فقط لنسبة الوقود — لا تُعدَّل من هنا، يدخلها مالك المولد أو الفني المسؤول -->
              <div v-if="isEditMode">
                <label class="field-label">{{ $t("dashboard.current_fuel_level") }}</label>
                <div class="fuel-readonly-badge">
                  <Fuel aria-hidden="true" :style="{ color: fuelColor(generator?.fuel_percentage) }" />
                  <span class="fuel-readonly-badge__value" :style="{ color: fuelColor(generator?.fuel_percentage) }">
                    {{ generator?.fuel_percentage !== null && generator?.fuel_percentage !== undefined ? generator.fuel_percentage + '%' : '-' }}
                  </span>
                  <span class="fuel-readonly-badge__meta">
                    {{ generator?.fuel_updated_at ? t("dashboard.fuel_last_updated", { time: timeAgo(generator.fuel_updated_at) }) : t("dashboard.fuel_no_update_yet") }}
                  </span>
                </div>
                <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1.5">
                  {{ $t("dashboard.fuel_level_hint") }}
                </p>
              </div>
            </div>

            <!-- المواصفات الفنية: هوية المعدة (الصانع/الموديل/الرقم التسلسلي) -->
            <div class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-1.5"><Factory class="text-[10px]" aria-hidden="true" /> {{ $t("generator_form_modal.technical_specs_section_title") }}</h5>
              <div class="grid sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.manufacturer_label") }}</label>
                  <input v-model="form.manufacturer" type="text" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.model_label") }}</label>
                  <input v-model="form.model" type="text" class="field-input" />
                </div>
                <div class="sm:col-span-2">
                  <label class="field-label">{{ $t("generator_form_modal.serial_number_label") }}</label>
                  <input v-model="form.serial_number" type="text" dir="ltr" class="field-input" />
                </div>
              </div>
            </div>

            <!-- المواصفات الكهربائية: القيم المرجعية المُقاسة عليها القراءات التشخيصية اللاحقة -->
            <div class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-1.5"><Zap class="text-[10px]" aria-hidden="true" /> {{ $t("generator_form_modal.electrical_specs_section_title") }}</h5>
              <div class="grid sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.rated_voltage_label") }}</label>
                  <input v-model="form.rated_voltage" type="number" min="1" max="65000" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.rated_load_kw_label") }}</label>
                  <input v-model="form.rated_load_kw" type="number" min="1" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.rated_frequency_hz_label") }}</label>
                  <AppDropdownSelect
                    v-model="form.rated_frequency_hz"
                    :options="frequencyOptions"
                    :placeholder="$t('generator_form_modal.not_specified_placeholder')"
                    variant="field"
                    width-class="w-full"
                    match-trigger-width
                  />
                </div>
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.phase_count_label") }}</label>
                  <AppDropdownSelect
                    v-model="form.phase_count"
                    :options="phaseOptions"
                    :placeholder="$t('generator_form_modal.not_specified_placeholder')"
                    variant="field"
                    width-class="w-full"
                    match-trigger-width
                  />
                </div>
              </div>
            </div>

            <!-- معايير التشغيل والصيانة: الحمل المقنن وموعد الصيانة القادمة وتاريخ التركيب -->
            <div class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-1.5"><Wrench class="text-[10px]" aria-hidden="true" /> {{ $t("generator_form_modal.maintenance_specs_section_title") }}</h5>
              <div class="grid sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.service_interval_hours_label") }}</label>
                  <input v-model="form.service_interval_hours" type="number" min="1" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("generator_form_modal.next_service_due_at_label") }}</label>
                  <input v-model="form.next_service_due_at" type="date" class="field-input" />
                </div>
                <div class="sm:col-span-2">
                  <label class="field-label">{{ $t("generator_form_modal.installed_at_label") }}</label>
                  <input v-model="form.installed_at" type="date" :max="new Date().toISOString().slice(0, 10)" class="field-input" />
                </div>
              </div>
            </div>

            <div>
              <label class="field-label">{{ $t("users_page.notes_optional_label") }}</label>
              <textarea v-model="form.notes" rows="2" maxlength="1000" class="field-input resize-none" :placeholder="$t('dashboard.notes_placeholder')"></textarea>
            </div>
          </form>

          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="close" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button type="submit" @click="handleSubmit" :disabled="isSaving" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
              {{ isEditMode ? $t("dashboard.save_changes") : $t("dashboard.save_generator") }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
