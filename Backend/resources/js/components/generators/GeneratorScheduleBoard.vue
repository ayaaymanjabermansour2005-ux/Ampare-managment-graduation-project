<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useGeneratorSchedules } from "@/composables/useGeneratorSchedules";
import { useConfirm } from "@/composables/useConfirm";
import { Plus, Trash2, Zap } from "@lucide/vue";

const { t, locale } = useI18n();
const { confirm } = useConfirm();

const props = defineProps({
  generatorId: { type: [Number, String], required: true },
  canManage: { type: Boolean, default: false },
});

const {
  schedules,
  isLoading,
  isSaving,
  error,
  activeNow,
  upcoming,
  fetchSchedules,
  createSchedule,
  deleteSchedule,
} = useGeneratorSchedules(props.generatorId);

const showForm = ref(false);
const form = ref({ starts_at: "", ends_at: "", note: "" });

async function handleSubmit() {
  try {
    await createSchedule(form.value);
    form.value = { starts_at: "", ends_at: "", note: "" };
    showForm.value = false;
  } catch {}
}

async function handleDelete(scheduleId) {
  const confirmed = await confirm({
    title: t("generator_schedule_board.delete_confirm_title"),
    message: t("generator_schedule_board.delete_confirm_message"),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;
  await deleteSchedule(scheduleId);
}

function formatTime(dateStr) {
  return new Date(dateStr).toLocaleTimeString(locale.value === "ar" ? "ar" : "en-US", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

onMounted(() => fetchSchedules());
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold text-gray-700 dark:text-[#eceee8]">{{ t("generator_schedule_board.title") }}</h3>
      <button
        v-if="canManage"
        type="button"
        @click="showForm = !showForm"
        class="text-sm text-primary-500 hover:text-primary-600 font-medium"
      >
        <Plus class="me-1" aria-hidden="true" /> {{ t("generator_schedule_board.publish_new") }}
      </button>
    </div>

    <div v-if="error" class="bg-danger-bg text-danger text-sm rounded-lg p-3">
      {{ error }}
    </div>

    <form
      v-if="canManage && showForm"
      @submit.prevent="handleSubmit"
      class="bg-surface dark:bg-[#1c1e20] border border-border dark:border-white/10 rounded-card p-4 space-y-3"
    >
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-gray-500 dark:text-[#a8aaa5] mb-1">{{ t("generator_schedule_board.from_label") }}</label>
          <input
            v-model="form.starts_at"
            type="datetime-local"
            required
            class="w-full border border-border dark:border-white/10 dark:bg-white/5 rounded-md px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs text-gray-500 dark:text-[#a8aaa5] mb-1">{{ t("generator_schedule_board.to_label") }}</label>
          <input
            v-model="form.ends_at"
            type="datetime-local"
            required
            class="w-full border border-border dark:border-white/10 dark:bg-white/5 rounded-md px-3 py-2 text-sm"
          />
        </div>
      </div>
      <div>
        <label class="block text-xs text-gray-500 dark:text-[#a8aaa5] mb-1">{{ t("generator_schedule_board.note_label") }}</label>
        <input
          v-model="form.note"
          type="text"
          maxlength="255"
          :placeholder="t('generator_schedule_board.note_placeholder')"
          class="w-full border border-border dark:border-white/10 dark:bg-white/5 rounded-md px-3 py-2 text-sm"
        />
      </div>
      <button
        type="submit"
        :disabled="isSaving"
        class="w-full bg-primary-500 text-white py-2 rounded-md text-sm font-medium hover:bg-primary-600 disabled:opacity-50 transition"
      >
        {{ isSaving ? t("generator_schedule_board.publishing") : t("generator_schedule_board.publish") }}
      </button>
    </form>

    <div v-if="isLoading" class="space-y-2">
      <div
        v-for="i in 2"
        :key="i"
        class="h-16 rounded-card bg-gray-50 dark:bg-white/5 animate-pulse"
      ></div>
    </div>

    <template v-else>
      <div
        v-if="activeNow"
        class="bg-success-bg border border-success/20 rounded-card p-4 flex items-center justify-between"
      >
        <div>
          <p class="text-xs text-success font-medium mb-0.5">
            <Zap aria-hidden="true" /> {{ t("generator_schedule_board.active_now_label") }}
          </p>
          <p class="text-sm text-gray-700 dark:text-[#eceee8] font-mono">
            {{ formatTime(activeNow.starts_at) }} —
            {{ formatTime(activeNow.ends_at) }}
          </p>
          <p v-if="activeNow.note" class="text-xs text-gray-500 dark:text-[#a8aaa5] mt-1">
            {{ activeNow.note }}
          </p>
        </div>
        <button :aria-label="t('common.delete')"
          v-if="canManage"
          @click="handleDelete(activeNow.id)"
          class="text-gray-400 dark:text-[#8f938a] hover:text-danger text-sm"
        >
          <Trash2 aria-hidden="true" />
        </button>
      </div>

      <div
        v-if="upcoming.length === 0 && !activeNow"
        class="text-center py-10 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10"
      >
        <p class="text-sm text-gray-500 dark:text-[#a8aaa5]">{{ t("generator_schedule_board.empty_state") }}</p>
      </div>

      <div
        v-for="s in upcoming"
        :key="s.id"
        class="bg-surface dark:bg-[#1c1e20] border border-border dark:border-white/10 rounded-card p-4 flex items-center justify-between"
      >
        <div>
          <p class="text-sm text-gray-700 dark:text-[#eceee8] font-mono">
            {{ formatTime(s.starts_at) }} — {{ formatTime(s.ends_at) }}
          </p>
          <p v-if="s.note" class="text-xs text-gray-500 dark:text-[#a8aaa5] mt-1">{{ s.note }}</p>
        </div>
        <button :aria-label="t('common.delete')"
          v-if="canManage"
          @click="handleDelete(s.id)"
          class="text-gray-400 dark:text-[#8f938a] hover:text-danger text-sm"
        >
          <Trash2 aria-hidden="true" />
        </button>
      </div>
    </template>
  </div>
</template>
