<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import adminOpsService from "@/services/adminOpsService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleCheck, LoaderCircle, Megaphone, Send, X } from "@lucide/vue";


const { t } = useI18n();

const isOpen = ref(false);
const title = ref("");
const message = ref("");
const audience = ref("all");
const isSending = ref(false);
const sendError = ref(null);
const successCount = ref(null);

const AUDIENCE_OPTIONS = computed(() => [
  { value: "all", label: t("announcement.audience_all") },
  { value: "subscriber", label: t("announcement.audience_subscriber") },
  { value: "generator_owner", label: t("announcement.audience_owner") },
  { value: "technician", label: t("announcement.audience_technician") },
]);

function open() {
  isOpen.value = true;
  title.value = "";
  message.value = "";
  audience.value = "all";
  sendError.value = null;
  successCount.value = null;
}
function close() {
  if (isSending.value) return;
  isOpen.value = false;
}

async function handleSubmit() {
  if (!title.value.trim() || !message.value.trim()) return;
  isSending.value = true;
  sendError.value = null;
  try {
    const { data } = await adminOpsService.sendAnnouncement({
      title: title.value.trim(),
      message: message.value.trim(),
      audience: audience.value,
    });
    successCount.value = data.data.recipients_count;
    setTimeout(close, 1800);
  } catch (err) {
    sendError.value = normalizeApiError(err, t("announcement.send_error")).message;
  } finally {
    isSending.value = false;
  }
}

defineExpose({ open });
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="close">
      <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl">
        <div class="modal-head-brand modal-head-brand--gold">
          <div class="modal-head-brand__inner">
            <span class="modal-head-brand__icon"><Megaphone aria-hidden="true" /></span>
            <div class="min-w-0">
              <h3 class="modal-head-brand__title">{{ $t("announcement.modal_title") }}</h3>
            </div>
          </div>
          <button :aria-label="$t('common.close')" type="button" class="modal-head-brand__close" @click="close">
            <X aria-hidden="true" />
          </button>
        </div>

        <div class="p-5">
        <div v-if="successCount !== null" class="py-6 text-center">
          <CircleCheck class="text-3xl text-[#28A745] mb-2" aria-hidden="true" />
          <p class="text-[12.5px] font-bold">
            {{ $t("announcement.sent_to_count", { count: successCount }) }}
          </p>
        </div>

        <form v-else @submit.prevent="handleSubmit" class="space-y-3">
          <div v-if="sendError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">
            {{ sendError }}
          </div>

          <div>
            <label class="block text-[11px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] mb-1.5">
              {{ $t("announcement.title_label") }}
            </label>
            <input
              v-model="title"
              type="text"
              maxlength="150"
              required
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>

          <div>
            <label class="block text-[11px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] mb-1.5">
              {{ $t("announcement.message_label") }}
            </label>
            <textarea
              v-model="message"
              maxlength="1000"
              rows="4"
              required
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"
            ></textarea>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] mb-1.5">
              {{ $t("announcement.audience_label") }}
            </label>
            <AppDropdownSelect
              v-model="audience"
              :options="AUDIENCE_OPTIONS"
              variant="field" width-class="w-full" match-trigger-width
            />
          </div>

          <button
            type="submit"
            :disabled="isSending"
            class="w-full btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSending" /><Send aria-hidden="true" v-else />
            {{ isSending ? $t("announcement.sending") : $t("announcement.send") }}
          </button>
        </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>