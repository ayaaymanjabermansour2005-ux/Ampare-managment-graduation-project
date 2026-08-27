<script setup>
import { onMounted, ref, computed, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { useConversations } from "@/composables/useConversations";
import aiChatService from "@/services/aiChatService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Check, CircleCheck, LoaderCircle, Send, TriangleAlert } from "@lucide/vue";


const { t } = useI18n();

const {
  activeConversation,
  messages,
  isSending,
  sendError,
  sendMessage,
  isStartingWithOwner,
  startWithOwnerError,
  startWithOwnerConversation,
  isConverting,
  convertError,
  convertToIssue,
} = useConversations();

const messageText = ref("");
const messagesContainer = ref(null);

/* ==========================================================================
 * ==================  تحويل المحادثة إلى بلاغ عطل / شكوى  ==================
 * ========================================================================== */
const showConvertPanel = ref(false);
const convertCategory = ref(null);
const convertGeneratorId = ref("");
const convertGenerators = ref([]);
const isLoadingConvertGenerators = ref(false);
const convertGeneratorOptions = computed(() => convertGenerators.value.map((g) => ({ value: g.id, label: g.name })));
const convertSuccess = ref(false);

function toggleConvertPanel() {
  showConvertPanel.value = !showConvertPanel.value;
}

async function selectCategory(category) {
  convertCategory.value = category;
  if (category === "fault" && convertGenerators.value.length === 0) {
    isLoadingConvertGenerators.value = true;
    try {
      const { data } = await aiChatService.availableGenerators();
      convertGenerators.value = data.data;
    } finally {
      isLoadingConvertGenerators.value = false;
    }
  }
}

async function handleConvertConfirm() {
  const ok = await convertToIssue(
    convertCategory.value,
    convertCategory.value === "fault" ? convertGeneratorId.value : null,
  );
  if (ok) convertSuccess.value = true;
}

async function scrollToBottom() {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
}

async function handleSend() {
  if (!messageText.value.trim()) return;
  const text = messageText.value;
  messageText.value = "";
  const ok = await sendMessage(text);
  if (ok) scrollToBottom();
}

onMounted(async () => {
  await startWithOwnerConversation();
  scrollToBottom();
});
</script>

<template>
  <div class="h-[calc(100vh-11rem)] flex flex-col bg-surface dark:bg-[#1c1e20] rounded-card border border-border dark:border-white/10 overflow-hidden">
    <header class="px-4 py-3.5 border-b border-border dark:border-white/10 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-medium text-secondary-600 tracking-wide">{{ t("technician_messages.conversation_with_label") }}</p>
        <h2 class="font-semibold text-gray-700 dark:text-gray-200 truncate">
          {{ activeConversation?.other_participant?.name ?? t("technician_payments.default_owner_label") }}
        </h2>
      </div>
      <button
        v-if="activeConversation"
        type="button"
        @click="toggleConvertPanel"
        class="status-chip chip-warning !cursor-pointer text-[11px] shrink-0"
      >
        <TriangleAlert aria-hidden="true" />
        {{ t("messages_page.convert_to_issue_button") }}
      </button>
    </header>

    <!-- ===== لوحة تحويل المحادثة إلى بلاغ عطل / شكوى ===== -->
    <div
      v-if="showConvertPanel"
      class="px-4 py-3.5 border-b border-border dark:border-white/10 bg-gray-50 dark:bg-white/5 space-y-2.5"
    >
      <template v-if="convertSuccess">
        <span class="status-chip chip-success">
          <CircleCheck aria-hidden="true" />
          {{ t("messages_page.convert_success") }}
        </span>
      </template>
      <template v-else>
        <p class="text-[11.5px] font-bold text-gray-700 dark:text-gray-200">{{ t("messages_page.convert_category_label") }}</p>
        <div class="flex gap-2">
          <button
            type="button"
            @click="selectCategory('fault')"
            class="status-chip !cursor-pointer"
            :class="convertCategory === 'fault' ? 'chip-danger' : ''"
          >
            {{ t("messages_page.convert_category_fault") }}
          </button>
          <button
            type="button"
            @click="selectCategory('complaint')"
            class="status-chip !cursor-pointer"
            :class="convertCategory === 'complaint' ? 'chip-warning' : ''"
          >
            {{ t("messages_page.convert_category_complaint") }}
          </button>
        </div>

        <div v-if="convertCategory === 'fault'">
          <label class="text-[11px] text-gray-400 dark:text-gray-500 block mb-1">
            {{ t("messages_page.convert_generator_label") }}
          </label>
          <AppDropdownSelect
            v-model="convertGeneratorId"
            :options="convertGeneratorOptions"
            :disabled="isLoadingConvertGenerators"
            :placeholder="isLoadingConvertGenerators ? t('common.loading') : t('messages_page.convert_generator_placeholder')"
            variant="field" width-class="w-full" match-trigger-width
          />
        </div>

        <p v-if="convertError" class="text-[11px] text-danger">{{ convertError }}</p>

        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="handleConvertConfirm"
            :disabled="isConverting || !convertCategory || (convertCategory === 'fault' && !convertGeneratorId)"
            class="inline-flex items-center gap-1.5 text-[11.5px] font-bold px-4 py-1.5 rounded-full bg-primary-500 text-white disabled:opacity-50 transition"
          >
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isConverting" /><Check aria-hidden="true" v-else />
            {{ t("messages_page.convert_confirm_button") }}
          </button>
          <button type="button" @click="showConvertPanel = false" class="text-[11.5px] text-gray-400 dark:text-gray-500">
            {{ t("common.cancel") }}
          </button>
        </div>
      </template>
    </div>

    <div
      v-if="isStartingWithOwner"
      class="flex-1 flex items-center justify-center text-sm text-gray-400 dark:text-gray-500"
    >
      <LoaderCircle class="me-2 animate-spin" aria-hidden="true" />{{ t("owner_messages.opening_conversation") }}
    </div>

    <div
      v-else-if="startWithOwnerError"
      class="flex-1 flex items-center justify-center text-sm text-danger p-6 text-center"
    >
      {{ startWithOwnerError }}
    </div>

    <template v-else>
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
        <div v-if="messages.length === 0" class="text-center text-sm text-gray-400 dark:text-gray-500 py-8">
          {{ t("technician_messages.empty_state") }}
        </div>

        <div
          v-for="msg in messages"
          :key="msg.id"
          class="flex"
          :class="
            msg.sender?.id === activeConversation?.other_participant?.id
              ? 'justify-start'
              : 'justify-end'
          "
        >
          <div
            class="max-w-[75%] rounded-2xl px-3.5 py-2 text-sm break-words"
            :class="
              msg.sender?.id === activeConversation?.other_participant?.id
                ? 'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-200 rounded-bs-sm'
                : 'bg-primary-500 text-white rounded-be-sm'
            "
          >
            {{ msg.message_text }}
          </div>
        </div>
      </div>

      <footer class="p-3 border-t border-border dark:border-white/10">
        <div v-if="sendError" class="text-xs text-danger mb-2">{{ sendError }}</div>
        <form @submit.prevent="handleSend" class="flex gap-2">
          <input
            v-model="messageText"
            type="text"
            :placeholder="t('owner_messages.message_placeholder')"
            maxlength="2000"
            class="flex-1 rounded-full border border-border dark:border-white/10 bg-transparent px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
          />
          <button
            :aria-label="t('common.send_message')"
            type="submit"
            :disabled="isSending || !messageText.trim()"
            class="w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center hover:bg-primary-600 disabled:opacity-50 transition shrink-0"
          >
            <Send class="text-sm" aria-hidden="true" />
          </button>
        </form>
      </footer>
    </template>
  </div>
</template>
