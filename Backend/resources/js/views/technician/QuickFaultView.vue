<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, reactive, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import faultService from "@/services/faultService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleCheck } from "@lucide/vue";

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
  <div class="space-y-6">
    <div>
      <p class="text-xs font-medium text-secondary-600 tracking-wide mb-1">
        {{ t("technician_fault.eyebrow") }}
      </p>
      <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ t("technician_portal.quick_fault_label") }}</h1>
    </div>

    <div
      v-if="submitted"
      class="bg-success-bg text-success text-sm rounded-lg p-6 text-center"
    >
      <CircleCheck class="text-2xl mb-2 block" aria-hidden="true" />
      {{ t("technician_fault.success_message") }}
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-4">
      <div v-if="submitError" class="bg-danger-bg text-danger text-sm rounded-lg p-3">
        {{ submitError }}
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("owner_technician_tasks.generator_label") }}</label>
        <div v-if="isLoadingGenerators" class="h-11 rounded-lg bg-gray-50 dark:bg-white/5 animate-pulse"></div>
        <AppDropdownSelect
          v-else
          v-model="form.generator_id"
          :options="generatorSelectOptions"
          :placeholder="t('owner_technician_tasks.choose_generator')"
          variant="field" width-class="w-full" match-trigger-width
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("technician_fault.fault_title_label") }}</label>
        <input
          v-model="form.title"
          type="text"
          required
          maxlength="150"
          class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("technician_fault.description_label") }}</label>
        <textarea
          v-model="form.description"
          rows="4"
          required
          maxlength="2000"
          class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("technician_fault.priority_label") }}</label>
        <div class="flex gap-2">
          <button
            v-for="p in PRIORITIES"
            :key="p.v"
            type="button"
            @click="form.priority = p.v"
            class="flex-1 py-2 rounded-lg text-xs font-medium border transition"
            :class="
              form.priority === p.v
                ? 'bg-primary-500 text-white border-primary-500'
                : 'bg-surface dark:bg-[#1c1e20] text-gray-600 dark:text-gray-300 border-border dark:border-white/10 hover:border-primary-300'
            "
          >
            {{ p.l }}
          </button>
        </div>
      </div>

      <button
        type="submit"
        :disabled="isSubmitting || !form.generator_id"
        class="w-full py-2.5 rounded-lg text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 disabled:opacity-50 transition"
      >
        {{ isSubmitting ? t("technician_fault.submitting_ellipsis") : t("technician_fault.submit_button") }}
      </button>
    </form>
  </div>
</template>
