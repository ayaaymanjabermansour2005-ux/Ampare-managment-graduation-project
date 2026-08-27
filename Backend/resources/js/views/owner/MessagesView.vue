<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useConversations } from "@/composables/useConversations";
import { vReveal } from "@/directives/reveal";
import AiChatWorkspace from "@/components/ai/AiChatWorkspace.vue";
import aiChatService from "@/services/aiChatService";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { ArrowRight, Check, ChevronLeft, CircleCheck, Headset, LoaderCircle, MessageSquareOff, MessagesSquare, Send, TriangleAlert } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();

const {
  conversations,
  isLoading,
  error,
  fetchConversations,
  activeConversation,
  messages,
  isLoadingMessages,
  openConversation,
  isSending,
  sendError,
  sendMessage,
  isStarting,
  startError,
  startConversation,
  isStartingSupport,
  startSupportError,
  startSupportConversation,
  isConverting,
  convertError,
  convertToIssue,
} = useConversations();

const route = useRoute();

/* ==========================================================================
 * ========================  التابات (المساعد الذكي / الرسائل)  =============
 * ========================================================================== */
const TABS = computed(() => [
  { key: "assistant", label: t("messages_page.tab_assistant"), icon: "fa-robot" },
  { key: "messages", label: t("messages_page.tab_messages"), icon: "fa-comments" },
]);
const activeTab = ref("messages");

const totalUnread = computed(() => conversations.value.reduce((sum, c) => sum + (c.unread_count || 0), 0));

const messageText = ref("");
const messagesContainer = ref(null);

async function scrollToBottom() {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
}

async function handleOpen(conversation) {
  resetConvertPanel();
  await openConversation(conversation);
  scrollToBottom();
}

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

function resetConvertPanel() {
  showConvertPanel.value = false;
  convertCategory.value = null;
  convertGeneratorId.value = "";
  convertSuccess.value = false;
}

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

async function handleSend() {
  if (!messageText.value.trim()) return;
  const text = messageText.value;
  messageText.value = "";
  const ok = await sendMessage(text);
  if (ok) scrollToBottom();
}

async function handleContactSupport() {
  activeTab.value = "messages";
  const ok = await startSupportConversation();
  if (ok) scrollToBottom();
}

onMounted(async () => {
  // يسمح بفتح الصفحة مباشرة على تاب "المساعد الذكي" (مثلاً من الداشبورد): ?tab=assistant
  if (route.query.tab === "assistant") activeTab.value = "assistant";

  await fetchConversations();

  const startUserId = route.query.startUserId;
  if (startUserId) {
    activeTab.value = "messages";
    await startConversation(Number(startUserId));
    scrollToBottom();
    return;
  }

  const openId = route.query.open;
  if (openId) {
    const conv = conversations.value.find((c) => c.id === Number(openId));
    if (conv) {
      activeTab.value = "messages";
      await openConversation(conv);
      scrollToBottom();
    }
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("common.home") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("messages_page.breadcrumb") }}</span>
      </nav>
      <div class="relative flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
              <MessagesSquare aria-hidden="true" />
            </span>
            {{ t("messages_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ t("messages_page.subtitle") }}
          </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <!-- ===== تواصل مباشر مع الدعم الفني ===== -->
          <button
            type="button"
            @click="handleContactSupport"
            :disabled="isStartingSupport"
            class="inline-flex items-center gap-2 text-[12px] font-bold text-[#8A6D1F] border border-[#8A6D1F]/30 rounded-full px-4 py-2 hover:bg-[#8A6D1F]/10 transition disabled:opacity-60"
          >
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isStartingSupport" /><Headset aria-hidden="true" v-else />
            {{ t("messages_page.contact_support_button") }}
          </button>

          <!-- ===== تبديل التابات ===== -->
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 w-fit">
            <button
              v-for="tab in TABS" :key="tab.key" type="button"
              @click="activeTab = tab.key"
              class="relative flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
              :class="
                activeTab === tab.key
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                  : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
              "
            >
              <AppIcon :name="tab.icon" />
              {{ tab.label }}
              <span
                v-if="tab.key === 'messages' && totalUnread > 0"
                class="absolute -top-1 -end-1 min-w-[16px] h-4 px-1 rounded-full bg-[#D9534F] text-white text-[9px] font-bold flex items-center justify-center"
              >{{ totalUnread }}</span>
            </button>
          </div>
        </div>
      </div>

      <p v-if="startSupportError" class="relative text-[11px] text-[#D9534F] mt-3">{{ startSupportError }}</p>
    </section>

    <!-- ==========================================================
         ==================  تاب: المساعد الذكي  =====================
         ========================================================== -->
    <AiChatWorkspace v-if="activeTab === 'assistant'" mode="owner" :title="t('messages_page.assistant_title')" />

    <!-- ==========================================================
         ==================  تاب: المحادثات  ==========================
         ========================================================== -->
    <section v-else v-reveal class="glass-card p-0 overflow-hidden">
      <div class="h-[calc(100vh-16rem)] flex">
        <!-- قائمة المحادثات -->
        <aside
          class="w-full sm:w-72 border-e border-[#eee8da] dark:border-white/10 flex flex-col shrink-0"
          :class="{ 'hidden sm:flex': activeConversation }"
        >
          <header class="px-4 py-3.5 border-b border-[#eee8da] dark:border-white/10">
            <h2 class="font-bold text-[13px]">{{ t("messages_page.conversations_list_title") }}</h2>
          </header>

          <div class="flex-1 overflow-y-auto">
            <div v-if="isLoading" class="p-4 space-y-2">
              <div v-for="i in 4" :key="i" class="h-14 rounded-lg thumb-loading"></div>
            </div>

            <div v-else-if="error" class="p-4 text-[12px] text-[#D9534F]">{{ error }}</div>

            <div v-else-if="conversations.length === 0" class="text-center py-10 px-4">
              <MessageSquareOff class="text-2xl text-[#9a9d97] mb-2" aria-hidden="true" />
              <p class="text-[12.5px] text-[#9a9d97] mb-3">{{ t("messages_page.no_conversations_yet") }}</p>
              <button
                type="button"
                @click="handleContactSupport"
                :disabled="isStartingSupport"
                class="inline-flex items-center gap-2 text-[11.5px] font-bold text-[#8A6D1F] border border-[#8A6D1F]/30 rounded-full px-3.5 py-1.5 hover:bg-[#8A6D1F]/10 transition disabled:opacity-60"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isStartingSupport" /><Headset aria-hidden="true" v-else />
                {{ t("messages_page.contact_support_button") }}
              </button>
            </div>

            <button
              v-for="conv in conversations"
              :key="conv.id"
              type="button"
              @click="handleOpen(conv)"
              class="w-full flex items-center gap-3 px-4 py-3 text-start border-b border-[#f0ece0] dark:border-white/5 hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition"
              :class="{ 'bg-[#f4efe5]/70 dark:bg-white/5': activeConversation?.id === conv.id }"
            >
              <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white font-bold text-[12px] shrink-0">
                {{ conv.other_participant?.name?.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-[12.5px] font-bold truncate">{{ conv.other_participant?.name }}</p>
                  <span v-if="conv.unread_count" class="status-chip chip-success shrink-0">
                    {{ conv.unread_count }}
                  </span>
                </div>
                <p v-if="conv.latest_message" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate mt-0.5">
                  {{ conv.latest_message.text }}
                </p>
              </div>
            </button>
          </div>
        </aside>

        <!-- المحادثة النشطة -->
        <div class="flex-1 flex flex-col min-w-0" :class="{ 'hidden sm:flex': !activeConversation }">
          <template v-if="activeConversation">
            <header class="px-4 py-3.5 border-b border-[#eee8da] dark:border-white/10 flex items-center gap-3">
              <button :aria-label="t('common.back')" type="button" @click="activeConversation = null" class="sm:hidden text-[#6B6B6B] dark:text-[#a8aaa5]">
                <ArrowRight aria-hidden="true" />
              </button>
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white font-bold text-[11px] shrink-0">
                {{ activeConversation.other_participant?.name?.charAt(0) }}
              </div>
              <p class="font-bold text-[12.5px] flex-1">{{ activeConversation.other_participant?.name }}</p>
              <button
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
              class="px-4 py-3.5 border-b border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/40 dark:bg-white/5 space-y-2.5"
            >
              <template v-if="convertSuccess">
                <span class="status-chip chip-success">
                  <CircleCheck aria-hidden="true" />
                  {{ t("messages_page.convert_success") }}
                </span>
              </template>
              <template v-else>
                <p class="text-[11.5px] font-bold">{{ t("messages_page.convert_category_label") }}</p>
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
                  <label class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] block mb-1">
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

                <p v-if="convertError" class="text-[11px] text-[#D9534F]">{{ convertError }}</p>

                <div class="flex items-center gap-3">
                  <button
                    type="button"
                    @click="handleConvertConfirm"
                    :disabled="isConverting || !convertCategory || (convertCategory === 'fault' && !convertGeneratorId)"
                    class="btn-fill inline-flex items-center gap-1.5 text-[11.5px] font-bold px-4 py-1.5 rounded-full bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white disabled:opacity-50 transition"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isConverting" /><Check aria-hidden="true" v-else />
                    {{ t("messages_page.convert_confirm_button") }}
                  </button>
                  <button type="button" @click="showConvertPanel = false" class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
                    {{ t("common.cancel") }}
                  </button>
                </div>
              </template>
            </div>

            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
              <div v-if="isLoadingMessages" class="text-center text-[12px] text-[#9a9d97] py-8">
                <LoaderCircle class="me-1.5 animate-spin" aria-hidden="true" />{{ t("common.loading") }}
              </div>

              <div
                v-for="msg in messages"
                :key="msg.id"
                class="flex"
                :class="msg.sender?.id === activeConversation.other_participant?.id ? 'justify-start' : 'justify-end'"
              >
                <div
                  class="max-w-[75%] rounded-2xl px-3.5 py-2 text-[12.5px]"
                  :class="msg.sender?.id === activeConversation.other_participant?.id
                    ? 'bg-[#f4efe5] dark:bg-white/5'
                    : 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'"
                >
                  {{ msg.message_text }}
                </div>
              </div>
            </div>

            <footer class="p-3 border-t border-[#eee8da] dark:border-white/10">
              <div v-if="sendError" class="text-[11px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-2">{{ sendError }}</div>
              <form @submit.prevent="handleSend" class="flex gap-2">
                <input
                  v-model="messageText"
                  type="text"
                  :placeholder="t('messages_page.message_input_placeholder')"
                  maxlength="2000"
                  class="flex-1 bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full px-4 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition"
                />
                <button :aria-label="t('common.send_message')"
                  type="submit"
                  :disabled="isSending || !messageText.trim()"
                  class="btn-fill relative w-10 h-10 rounded-full bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white flex items-center justify-center disabled:opacity-50 transition shrink-0 shadow-md"
                >
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSending" /><Send aria-hidden="true" v-else />
                </button>
              </form>
            </footer>
          </template>

          <div v-else-if="isStarting || isStartingSupport" class="flex-1 flex items-center justify-center text-[12px] text-[#9a9d97]">
            <LoaderCircle class="me-2 animate-spin" aria-hidden="true" />{{ t("messages_page.opening_conversation") }}
          </div>
          <div v-else class="flex-1 flex flex-col items-center justify-center text-center px-6">
            <MessagesSquare class="text-2xl text-[#9a9d97] mb-2" aria-hidden="true" />
            <p class="text-[12.5px] text-[#9a9d97]">{{ startError || t("messages_page.select_conversation_placeholder") }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>