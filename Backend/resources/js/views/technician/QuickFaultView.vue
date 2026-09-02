<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, reactive, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import faultService from "@/services/faultService";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleCheck, LoaderCircle, Send, TriangleAlert, X } from "@lucide/vue";

const router = useRouter();
const { t } = useI18n();

const generators = ref([]);
const isLoadingGenerators = ref(true);
const generatorSelectOptions = computed(() => generators.value.map((g) => ({ value: g.id, label: g.name })));

const form = reactive({
  generator_id: "",
  title: "",
  description: "",
  priority: "medium",
});

const PRIORITIES = computed(() => [
  { v: "low", l: t("faults_page.priority_low") },
  { v: "medium", l: t("complaints_page.priority_medium") },
  { v: "high", l: t("complaints_page.priority_high") },
  { v: "critical", l: t("faults_page.priority_critical") },
]);

const PRIORITY_TONE = {
  low: "text-[#17A2B8] border-[#17A2B8]",
  medium: "text-[#8A6D1F] border-[#8A6D1F]",
  high: "text-[#D9534F] border-[#D9534F]",
  critical: "text-white bg-[#D9534F] border-[#D9534F]",
};

const isSubmitting = ref(false);
const submitError = ref(null);
const submitted = ref(false);

async function loadGenerators() {
  isLoadingGenerators.value = true;
  try {
    const { data } = await generatorService.list({ per_page: 50 });
    const payload = data.data;
    generators.value = payload.data ?? payload;
    if (generators.value.length === 1) form.generator_id = generators.value[0].id;
  } finally {
    isLoadingGenerators.value = false;
  }
}

async function handleSubmit() {
  isSubmitting.value = true;
  submitError.value = null;
  try {
    await faultService.create({ ...form });
    submitted.value = true;
    setTimeout(() => router.back(), 1200);
  } catch (err) {
    submitError.value = normalizeApiError(err, t("owner_ai_chat.fault_report_error")).message;
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(loadGenerators);
</script>

<template>
  <div class="space-y-5">
    <!-- ===== رأس الصفحة ===== -->
    <section v-reveal class="glass-card p-5 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-56 h-56 bg-[#D9534F]/12 rounded-full blur-[90px] pointer-events-none"></div>
      <div class="relative flex items-center gap-3">
        <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#8A2F2A] to-[#D9534F] text-white flex items-center justify-center text-base shrink-0">
          <TriangleAlert aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-0.5">{{ t("technician_fault.eyebrow") }}</p>
          <h1 class="text-lg font-extrabold truncate">{{ t("technician_portal.quick_fault_label") }}</h1>
        </div>
      </div>
    </section>

    <section v-reveal v-if="submitted" class="glass-card p-8 text-center text-[13px] font-bold text-[#1f7a37] dark:text-[#7fe19c]">
      <CircleCheck class="text-3xl mb-2 block mx-auto" aria-hidden="true" />
      {{ t("technician_fault.success_message") }}
    </section>

    <form v-else @submit.prevent="handleSubmit" v-reveal class="glass-card p-5 space-y-3.5">
      <div v-if="submitError" class="alert-box"><X class="shrink-0" aria-hidden="true" /> {{ submitError }}</div>

      <div>
        <label class="field-label">{{ t("owner_technician_tasks.generator_label") }}</label>
        <div v-if="isLoadingGenerators" class="h-11 rounded-xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>
        <AppDropdownSelect
          v-else
          v-model="form.generator_id"
          :options="generatorSelectOptions"
          :placeholder="t('owner_technician_tasks.choose_generator')"
          variant="field" width-class="w-full" match-trigger-width
        />
      </div>

      <div>
        <label class="field-label">{{ t("technician_fault.fault_title_label") }}</label>
        <input v-model="form.title" type="text" required maxlength="150" class="field-input" />
      </div>

      <div>
        <label class="field-label">{{ t("technician_fault.description_label") }}</label>
        <textarea v-model="form.description" rows="4" required maxlength="2000" class="field-input resize-none"></textarea>
      </div>

      <div>
        <label class="field-label">{{ t("technician_fault.priority_label") }}</label>
        <div class="flex gap-2">
          <button
            v-for="p in PRIORITIES"
            :key="p.v"
            type="button"
            @click="form.priority = p.v"
            class="flex-1 py-2 rounded-full text-[11.5px] font-bold border transition"
            :class="form.priority === p.v ? PRIORITY_TONE[p.v] : 'text-[#6B6B6B] dark:text-[#a8aaa5] border-[#e7e2d6] dark:border-white/10 hover:border-[#8A6D1F]/40'"
          >
            {{ p.l }}
          </button>
        </div>
      </div>

      <button type="submit" :disabled="isSubmitting || !form.generator_id" class="btn-fill-brand btn-fill-brand--danger w-full justify-center">
        <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Send aria-hidden="true" v-else />
        {{ isSubmitting ? t("technician_fault.submitting_ellipsis") : t("technician_fault.submit_button") }}
      </button>
    </form>
  </div>
</template>