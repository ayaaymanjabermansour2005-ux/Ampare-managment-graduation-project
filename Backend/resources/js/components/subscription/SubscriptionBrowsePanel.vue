<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useSubscriberBrowseGenerators } from "@/composables/useSubscriberBrowseGenerators";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Check, ChevronLeft, ChevronRight, CircleAlert, CircleCheck, Gauge, LoaderCircle, MapPin, Plus, Send, TriangleAlert, Unplug, UserRound, X, Zap } from "@lucide/vue";


const emit = defineEmits(["subscribed"]);

const route = useRoute();
const { t, locale } = useI18n();

const {
  generators,
  pagination,
  isLoading,
  error,
  fetchGenerators,
  meters,
  isLoadingMeters,
  fetchMeters,
  isCreatingMeter,
  createMeterError,
  createMeter,
  isSubscribing,
  subscribeError,
  subscribeErrors,
  subscribe,
} = useSubscriberBrowseGenerators();

function localizedName(g) {
  return locale.value === "en" && g.name_en ? g.name_en : g.name;
}
function fmtMoney(n) {
  return "₪ " + Number(n ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}

/* ---------------- نموذج الاشتراك ---------------- */
const subscribeModal = ref(null); // generator object أو null
const form = reactive({
  subscriber_meter_id: "",
  schedule: "24h",
  service_start_time: "",
  service_end_time: "",
  billing_cycle: "monthly",
  contract_type: "",
  start_date: new Date().toISOString().slice(0, 10),
  end_date: "",
  requested_capacity_kw: "",
});
const agreedToTerms = ref(false);
const submitSuccess = ref(false);

/* ---------------- إضافة عداد جديد (Inline) ---------------- */
const isAddingMeter = ref(false);
const newMeter = reactive({ meter_number: "", property_label: "" });

async function openSubscribe(g) {
  subscribeModal.value = g;
  submitSuccess.value = false;
  Object.assign(form, {
    subscriber_meter_id: "",
    schedule: "24h",
    service_start_time: "",
    service_end_time: "",
    billing_cycle: "monthly",
    contract_type: "",
    start_date: new Date().toISOString().slice(0, 10),
    end_date: "",
    requested_capacity_kw: "",
  });
  agreedToTerms.value = false;
  isAddingMeter.value = false;
  if (meters.value.length === 0) await fetchMeters();
}

async function submitNewMeter() {
  const meter = await createMeter({ ...newMeter });
  if (meter) {
    form.subscriber_meter_id = meter.id;
    isAddingMeter.value = false;
    newMeter.meter_number = "";
    newMeter.property_label = "";
  }
}

async function handleSubscribe() {
  if (!agreedToTerms.value) return;

  const payload = {
    subscriber_meter_id: form.subscriber_meter_id,
    generator_id: subscribeModal.value.id,
    schedule: form.schedule,
    billing_cycle: form.billing_cycle,
    start_date: form.start_date,
  };
  if (form.schedule === "custom") {
    payload.service_start_time = form.service_start_time;
    payload.service_end_time = form.service_end_time;
  }
  if (form.contract_type) payload.contract_type = form.contract_type;
  if (form.end_date) payload.end_date = form.end_date;
  if (form.requested_capacity_kw) payload.requested_capacity_kw = Number(form.requested_capacity_kw);

  const result = await subscribe(payload);
  if (result) {
    submitSuccess.value = true;
    // بيسمح للحاوية إنها تحدّث حالة "اشتراكي" وتنقل المستخدم لتابها لو حابة
    emit("subscribed", result);
  }
}

/* ===== إحصاء بسيط — عدد المولدات المتاحة حاليًا ===== */
const availableCount = computed(() => pagination.value?.total ?? generators.value.length);

/* ===== خيارات القوائم المنسدلة (AppDropdownSelect) بالنافذة ===== */
const meterOptions = computed(() =>
  meters.value.map((m) => ({
    value: m.id,
    label: m.property_label ? `${m.meter_number} — ${m.property_label}` : m.meter_number,
  })),
);

const scheduleOptions = computed(() => [
  { value: "24h", label: t("dashboard.schedule_24h") },
  { value: "day", label: t("browse_generators_page.schedule_day") },
  { value: "night", label: t("browse_generators_page.schedule_night") },
  { value: "custom", label: t("browse_generators_page.schedule_custom") },
]);

const billingCycleOptions = computed(() => [
  { value: "daily", label: t("browse_generators_page.billing_daily") },
  { value: "weekly", label: t("browse_generators_page.billing_weekly") },
  { value: "monthly", label: t("browse_generators_page.billing_monthly") },
]);

onMounted(async () => {
  await fetchGenerators(1);
  // دعم الوصول المباشر من رابط "اشترك" باللاندنج بيج (?highlight=ID)
  const highlightId = route.query.highlight ? Number(route.query.highlight) : null;
  if (highlightId) {
    const match = generators.value.find((g) => g.id === highlightId);
    if (match) openSubscribe(match);
  }
});

defineExpose({ availableCount });
</script>

<template>
  <div class="space-y-6">
    <div v-if="!isLoading && !error" class="flex items-center justify-end text-[11.5px] font-bold text-[#9a9d97]">
      {{ $t("common.total") }}:
      <span class="text-[#52733D] dark:text-[#8cc35a] ms-1">{{ availableCount }}</span>
    </div>

    <section v-if="isLoading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="h-48 rounded-2xl thumb-loading"></div>
    </section>

    <section v-else-if="error" v-reveal class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
      <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
      {{ error }}
    </section>

    <section v-else-if="generators.length === 0" v-reveal class="glass-card p-10 text-center max-w-md mx-auto">
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <Unplug aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ $t("browse_generators_page.no_generators_available") }}</h3>
    </section>

    <section v-else v-reveal class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="g in generators" :key="g.id" class="glass-card p-5 hoverable">
        <div class="flex items-start justify-between mb-2.5">
          <h3 class="font-bold text-[14px]">{{ localizedName(g) }}</h3>
          <span class="status-chip chip-success">{{ $t("users_page.status_active") }}</span>
        </div>
        <p class="text-[11.5px] text-[#9a9d97] mb-1"><MapPin class="text-[9px]" aria-hidden="true" /> {{ g.location?.city ?? "-" }}</p>
        <p class="text-[11.5px] text-[#9a9d97] mb-3"><UserRound class="text-[9px]" aria-hidden="true" /> {{ g.owner?.name ?? "-" }}</p>
        <div class="flex items-center justify-between mb-4">
          <span class="text-[13px] font-extrabold text-[#8A6D1F]">{{ fmtMoney(g.price_per_kw) }} / {{ $t("browse_generators_page.per_kwh") }}</span>
          <span v-if="g.capacity_kw" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ g.capacity_kw }} kW</span>
        </div>
        <button
          type="button"
          @click="openSubscribe(g)"
          class="btn-fill relative w-full bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2"
        >
          <Zap aria-hidden="true" /> {{ $t("browse_generators_page.subscribe_now") }}
        </button>
      </div>
    </section>

    <!-- Pagination -->
    <div v-if="!isLoading && pagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button
        type="button"
        class="action-btn action-btn--view"
        :aria-label="$t('common.previous_page')"
        :disabled="pagination.current_page <= 1"
        @click="fetchGenerators(pagination.current_page - 1)"
      >
        <ChevronRight class="rtl:rotate-0 ltr:rotate-180" aria-hidden="true" />
      </button>
      <span class="text-[12px] font-bold px-2">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button
        type="button"
        class="action-btn action-btn--view"
        :aria-label="$t('common.next_page')"
        :disabled="pagination.current_page >= pagination.last_page"
        @click="fetchGenerators(pagination.current_page + 1)"
      >
        <ChevronLeft class="rtl:rotate-0 ltr:rotate-180" aria-hidden="true" />
      </button>
    </div>

    <!-- ===================== نافذة الاشتراك ===================== -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="subscribeModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
          @click.self="subscribeModal = null"
        >
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green shrink-0">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Zap aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">
                    {{ $t("browse_generators_page.subscribe_to", { name: localizedName(subscribeModal) }) }}
                  </h3>
                  <p class="modal-head-brand__subtitle">{{ fmtMoney(subscribeModal.price_per_kw) }} / {{ $t("browse_generators_page.per_kwh") }}</p>
                </div>
              </div>
              <button type="button" :aria-label="$t('common.close')" @click="subscribeModal = null" class="modal-head-brand__close">
                <X aria-hidden="true" />
              </button>
            </div>

            <div v-if="submitSuccess" class="p-6 text-center space-y-3">
              <CircleCheck class="text-3xl text-[#28A745]" aria-hidden="true" />
              <p class="text-[13px] font-bold">{{ $t("browse_generators_page.request_sent_success") }}</p>
              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ $t("browse_generators_page.pending_owner_approval_notice") }}</p>
              <button type="button" @click="subscribeModal = null" class="btn-fill-brand">
                {{ $t("common.close") }}
              </button>
            </div>

            <form v-else @submit.prevent="handleSubscribe" class="p-5 space-y-3.5 overflow-y-auto">
              <div v-if="subscribeError" class="alert-box">
                <CircleAlert class="shrink-0" aria-hidden="true" />
                <span>{{ subscribeError }}</span>
              </div>

              <div class="form-section">
                <div class="form-section-head">
                  <span class="form-section-icon"><Gauge aria-hidden="true" /></span>
                  {{ $t("browse_generators_page.meter_label") }}
                </div>

                <div v-if="isLoadingMeters" class="text-[11.5px] text-[#9a9d97]">{{ $t("common.loading") }}</div>

                <template v-else-if="!isAddingMeter">
                  <AppDropdownSelect
                    v-if="meters.length"
                    v-model="form.subscriber_meter_id"
                    :options="meterOptions"
                    :placeholder="$t('browse_generators_page.select_meter_placeholder')"
                    variant="field"
                    match-trigger-width
                    width-class="w-full"
                  />
                  <p v-else class="text-[11.5px] text-[#9a9d97] mb-2">{{ $t("browse_generators_page.no_meters_registered") }}</p>
                  <button type="button" @click="isAddingMeter = true" class="text-[11px] font-bold text-[#8A6D1F] mt-2 inline-flex items-center gap-1.5">
                    <Plus aria-hidden="true" /> {{ $t("browse_generators_page.add_new_meter") }}
                  </button>
                </template>

                <template v-else>
                  <div class="form-field">
                    <input v-model="newMeter.meter_number" required :placeholder="$t('browse_generators_page.meter_number_placeholder')" class="font-mono" />
                    <p v-if="createMeterError?.meter_number" class="form-error">{{ createMeterError.meter_number[0] }}</p>
                  </div>
                  <div class="form-field">
                    <input v-model="newMeter.property_label" :placeholder="$t('browse_generators_page.property_label_placeholder')" />
                  </div>
                  <div class="flex gap-2">
                    <button type="button" :disabled="isCreatingMeter || !newMeter.meter_number" @click="submitNewMeter" class="btn-fill-brand flex-1 justify-center">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingMeter" /><Check aria-hidden="true" v-else />
                      <span>{{ $t("browse_generators_page.save_meter") }}</span>
                    </button>
                    <button type="button" @click="isAddingMeter = false" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
                  </div>
                </template>

                <p v-if="subscribeErrors.subscriber_meter_id" class="form-error mt-1">{{ subscribeErrors.subscriber_meter_id[0] }}</p>
              </div>

              <div class="form-field">
                <label>{{ $t("browse_generators_page.operating_schedule_label") }}</label>
                <AppDropdownSelect
                  v-model="form.schedule"
                  :options="scheduleOptions"
                  variant="field"
                  match-trigger-width
                  width-class="w-full"
                />
              </div>

              <div v-if="form.schedule === 'custom'" class="grid grid-cols-2 gap-3">
                <div class="form-field">
                  <label>{{ $t("browse_generators_page.start_time_label") }}</label>
                  <input v-model="form.service_start_time" type="time" required />
                </div>
                <div class="form-field">
                  <label>{{ $t("browse_generators_page.end_time_label") }}</label>
                  <input v-model="form.service_end_time" type="time" required />
                </div>
              </div>

              <div class="form-field">
                <label>{{ $t("browse_generators_page.billing_cycle_label") }}</label>
                <AppDropdownSelect
                  v-model="form.billing_cycle"
                  :options="billingCycleOptions"
                  variant="field"
                  match-trigger-width
                  width-class="w-full"
                />
              </div>

              <div class="form-field">
                <label>{{ $t("subscriptions_page.start_date_field_label") }}</label>
                <input v-model="form.start_date" type="date" required />
                <p v-if="subscribeErrors.start_date" class="form-error">{{ subscribeErrors.start_date[0] }}</p>
              </div>

              <label class="flex items-start gap-2 text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] cursor-pointer">
                <input v-model="agreedToTerms" type="checkbox" required class="mt-0.5 accent-[#8A6D1F]" />
                {{ $t("browse_generators_page.agree_terms_text") }}
              </label>

              <button
                type="submit"
                :disabled="isSubscribing || !agreedToTerms || !form.subscriber_meter_id"
                class="btn-fill-brand w-full justify-center"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubscribing" /><Send aria-hidden="true" v-else />
                <span>{{ isSubscribing ? $t("browse_generators_page.sending_ellipsis") : $t("browse_generators_page.send_subscription_request") }}</span>
              </button>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>