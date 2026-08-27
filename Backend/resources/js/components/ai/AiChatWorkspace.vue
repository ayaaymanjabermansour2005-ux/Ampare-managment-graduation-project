<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { ArrowLeft, ArrowRight, ArrowUp, Bot, Camera, Circle, CircleAlert, Ellipsis, FileText, LoaderCircle, MessagesSquare, Paperclip, Search, Send, Sparkles, SquarePen, X } from "@lucide/vue";


import { useAiChat } from "@/composables/useAiChat";
import { useConfirm } from "@/composables/useConfirm";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";

const { t, locale } = useI18n();

const props = defineProps({
  mode: { type: String, default: "support" },
  title: { type: String, default: "" },
});

const { confirm } = useConfirm();
const chat = useAiChat();
const showComposer = ref(false);
const selectedGeneratorId = ref(null);
const openingMessage = ref("");
const draft = ref("");
const search = ref("");
const messagesEl = ref(null);
const predictionSubmitted = ref(false);
const reportSubmitted = ref(false);
const attachedFile = ref(null);
const attachedFilePreviewUrl = ref(null);
const attachmentError = ref(null);
const fileInputEl = ref(null);
const cameraInputEl = ref(null);

const MAX_ATTACHMENT_BYTES = 10 * 1024 * 1024;
const ALLOWED_ATTACHMENT_EXTENSIONS = ["jpg", "jpeg", "png", "pdf"];

const effectiveTitle = computed(() => props.title || t("ai_chat_workspace.default_title"));
const canCreatePrediction = computed(() => ["owner", "technician"].includes(props.mode));
const canCreateReport = computed(() => props.mode === "subscriber");
const isGeneralAdminChat = computed(() => props.mode === "admin");
const generatorSelectOptions = computed(() => chat.generators.value.map((g) => ({ value: g.id, label: g.name })));
const filteredSessions = computed(() => {
  const term = search.value.trim().toLowerCase();
  return term ? chat.sessions.value.filter((item) => item.generator?.name?.toLowerCase().includes(term)) : chat.sessions.value;
});
const quickPrompts = computed(() => [
  t("ai_chat_workspace.quick_prompt_generator_not_running"),
  t("ai_chat_workspace.quick_prompt_abnormal_noise"),
  t("ai_chat_workspace.quick_prompt_high_fuel_consumption"),
  t("ai_chat_workspace.quick_prompt_voltage_drop"),
]);

function timeAgo(value) {
  if (!value) return "";
  const minutes = Math.max(0, Math.floor((Date.now() - new Date(value.replace(" ", "T"))) / 60000));
  if (minutes < 1) return t("subscribers_page.time_now");
  if (minutes < 60) return t("subscribers_page.time_mins_ago", { mins: minutes });
  if (minutes < 1440) return t("subscribers_page.time_hours_ago", { hours: Math.floor(minutes / 60) });
  return t("subscribers_page.time_days_ago", { days: Math.floor(minutes / 1440) });
}

function formatTime(value) {
  if (!value) return "";
  return new Date(value.replace(" ", "T")).toLocaleTimeString(locale.value === "ar" ? "ar" : "en", { hour: "2-digit", minute: "2-digit" });
}

async function scrollToLatest() {
  await nextTick();
  if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
}

function clearAttachment() {
  if (attachedFilePreviewUrl.value) URL.revokeObjectURL(attachedFilePreviewUrl.value);
  attachedFile.value = null;
  attachedFilePreviewUrl.value = null;
  attachmentError.value = null;
}

function validateAndSetAttachment(file) {
  attachmentError.value = null;

  if (file.size > MAX_ATTACHMENT_BYTES) {
    attachmentError.value = t("ai_chat_workspace.attachment_too_large_error");
    return;
  }

  const extension = file.name.split(".").pop()?.toLowerCase();
  if (!ALLOWED_ATTACHMENT_EXTENSIONS.includes(extension)) {
    attachmentError.value = t("ai_chat_workspace.attachment_invalid_type_error");
    return;
  }

  if (attachedFilePreviewUrl.value) URL.revokeObjectURL(attachedFilePreviewUrl.value);
  attachedFile.value = file;
  attachedFilePreviewUrl.value = file.type.startsWith("image/") ? URL.createObjectURL(file) : null;
}

function onFileSelected(event) {
  const file = event.target.files?.[0];
  event.target.value = "";
  if (file) validateAndSetAttachment(file);
}

function triggerFileInput() {
  fileInputEl.value?.click();
}

function triggerCameraInput() {
  cameraInputEl.value?.click();
}

function startNewChat() {
  chat.activeSession.value = null;
  selectedGeneratorId.value = isGeneralAdminChat.value ? null : (chat.generators.value[0]?.id ?? null);
  openingMessage.value = "";
  predictionSubmitted.value = false;
  reportSubmitted.value = false;
  clearAttachment();
  showComposer.value = true;
}

async function selectSession(session) {
  showComposer.value = false;
  predictionSubmitted.value = false;
  reportSubmitted.value = false;
  await chat.openSession(session.id);
  scrollToLatest();
}

async function createSession() {
  const created = await chat.startSession(selectedGeneratorId.value, openingMessage.value, attachedFile.value);
  if (created) {
    showComposer.value = false;
    clearAttachment();
    scrollToLatest();
  }
}

function addPrompt(prompt) {
  if (showComposer.value) openingMessage.value = openingMessage.value ? `${openingMessage.value}\n${prompt}` : prompt;
  else draft.value = draft.value ? `${draft.value}\n${prompt}` : prompt;
}

async function send() {
  if (!draft.value.trim()) return;
  const text = draft.value;
  const file = attachedFile.value;
  draft.value = "";
  const sent = await chat.sendMessage(text, file);
  if (sent) clearAttachment();
  scrollToLatest();
}

async function submitPrediction() {
  const confirmed = await confirm({
    title: t("ai_chat_workspace.confirm_prediction_title"),
    message: t("ai_chat_workspace.confirm_prediction_message"),
    confirmLabel: t("ai_chat_workspace.confirm_prediction_action"),
  });
  if (confirmed && await chat.submitAsPrediction()) predictionSubmitted.value = true;
}

async function submitReport() {
  const confirmed = await confirm({
    title: t("ai_chat_workspace.confirm_report_title"),
    message: t("ai_chat_workspace.confirm_report_message"),
    confirmLabel: t("ai_chat_workspace.confirm_report_action"),
    variant: "danger",
  });
  if (confirmed && await chat.submitAsFaultReport()) reportSubmitted.value = true;
}

onMounted(async () => {
  const tasks = [chat.loadSessions()];
  if (!isGeneralAdminChat.value) tasks.push(chat.loadGenerators());
  await Promise.all(tasks);
});
</script>

<template>
  <section class="glass-card overflow-hidden min-h-[calc(100vh-9.5rem)]">
    <input ref="fileInputEl" type="file" accept="image/*,application/pdf" class="hidden" @change="onFileSelected" />
    <input ref="cameraInputEl" type="file" accept="image/*" capture="environment" class="hidden" @change="onFileSelected" />
    <div class="flex min-h-[calc(100vh-9.5rem)]">
      <aside class="w-full sm:w-80 shrink-0 flex flex-col border-e border-[#e7e2d6] dark:border-white/10 bg-[#fcfbf8]/70 dark:bg-white/[.02]" :class="{ 'hidden sm:flex': chat.activeSession.value || showComposer }">
        <header class="p-4 border-b border-[#e7e2d6] dark:border-white/10">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center shadow-sm"><Bot aria-hidden="true" /></span>
              <div class="min-w-0"><h1 class="font-extrabold text-[14px] truncate">{{ effectiveTitle }}</h1><p class="text-[10.5px] text-[#7c8078] dark:text-[#a8aaa5]">{{ t("ai_chat_workspace.status_online") }}</p></div>
            </div>
            <button type="button" @click="startNewChat" class="w-9 h-9 rounded-xl bg-[#52733D] text-white hover:bg-[#3E582E] transition shadow-sm" :aria-label="t('ai_chat_workspace.new_chat_aria')"><SquarePen class="text-xs" aria-hidden="true" /></button>
          </div>
          <label class="relative block"><Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[10px] text-[#9a9d97] dark:text-[#8f938a]" aria-hidden="true" /><input v-model="search" class="field-input !py-2 !ps-8 !text-[11px]" :placeholder="t('ai_chat_workspace.search_conversations_placeholder')" /></label>
        </header>
        <div class="flex-1 overflow-y-auto">
          <div v-if="chat.isLoadingSessions.value" class="p-4 space-y-2"><div v-for="i in 4" :key="i" class="h-16 thumb-loading rounded-xl"></div></div>
          <div v-else-if="chat.sessionsError.value" class="p-6 text-center text-[12px] text-[#c2413d] dark:text-[#f0a19c]"><CircleAlert class="text-lg mb-2 block" aria-hidden="true" />{{ chat.sessionsError.value }}<button @click="chat.loadSessions" class="block mx-auto mt-3 text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("ai_chat_workspace.retry") }}</button></div>
          <div v-else-if="!chat.sessions.value.length" class="p-8 text-center text-[12px] text-[#8b8e88] dark:text-[#a8aaa5]"><MessagesSquare class="text-2xl mb-3 block" aria-hidden="true" />{{ t("ai_chat_workspace.no_sessions_line1") }}<br>{{ t("ai_chat_workspace.no_sessions_line2") }}</div>
          <div v-else-if="!filteredSessions.length" class="p-8 text-center text-[12px] text-[#8b8e88] dark:text-[#a8aaa5]">{{ t("ai_chat_workspace.no_matching_sessions") }}</div>
          <button v-for="session in filteredSessions" :key="session.id" type="button" @click="selectSession(session)" class="w-full p-3.5 flex gap-3 text-start border-b border-[#eee9df] dark:border-white/5 hover:bg-[#eef4eb] dark:hover:bg-white/5 transition" :class="{ 'bg-[#e8f0e4] dark:bg-white/10': chat.activeSession.value?.id === session.id }">
            <span class="w-10 h-10 rounded-full bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center shrink-0"><Bot class="text-sm" aria-hidden="true" /></span>
            <span class="min-w-0 flex-1"><b class="block text-[12px] truncate">{{ isGeneralAdminChat ? t("ai_chat_workspace.general_support_title") : (session.generator?.name || t("ai_chat_workspace.default_session_title")) }}</b><small class="block text-[10.5px] text-[#8b8e88] dark:text-[#a8aaa5] mt-1">{{ timeAgo(session.created_at) }}</small></span>
          </button>
        </div>
      </aside>

      <div v-if="showComposer" class="flex-1 flex flex-col p-5 sm:p-8 overflow-y-auto">
        <button type="button" @click="showComposer = false" class="sm:hidden self-start text-[#52733D] dark:text-[#8cc35a] text-sm mb-6"><ArrowRight class="rtl:inline-block ltr:hidden me-1" aria-hidden="true" /><ArrowLeft class="ltr:inline-block rtl:hidden me-1" aria-hidden="true" /> {{ t("ai_chat_workspace.back_to_conversations") }}</button>
        <div class="max-w-xl w-full mx-auto my-auto">
          <span class="w-12 h-12 rounded-2xl bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center text-xl mb-4"><Sparkles aria-hidden="true" /></span>
          <h2 class="text-lg font-extrabold mb-1">{{ t("ai_chat_workspace.composer_heading") }}</h2><p class="text-[12px] text-[#777a74] dark:text-[#a8aaa5] mb-6">{{ isGeneralAdminChat ? t("ai_chat_workspace.composer_subtitle_admin") : t("ai_chat_workspace.composer_subtitle_generator") }}</p>
          <div v-if="chat.startError.value" class="alert-box mb-4">{{ chat.startError.value }}</div>
          <div v-if="!isGeneralAdminChat && chat.isLoadingGenerators.value" class="h-12 thumb-loading rounded-xl"></div>
          <div v-else-if="!isGeneralAdminChat && chat.generatorsError.value" class="alert-box">{{ chat.generatorsError.value }}</div>
          <div v-else-if="!isGeneralAdminChat && !chat.generators.value.length" class="text-[12px] text-[#8b8e88] dark:text-[#a8aaa5]">{{ t("ai_chat_page.no_generators_available") }}</div>
          <form v-else @submit.prevent="createSession" class="space-y-3">
            <AppDropdownSelect v-if="!isGeneralAdminChat" v-model="selectedGeneratorId" :options="generatorSelectOptions" variant="field" width-class="w-full" match-trigger-width />
            <textarea v-model="openingMessage" rows="4" maxlength="2000" class="field-input resize-none" :placeholder="t('ai_chat_workspace.issue_placeholder')"></textarea>
            <div v-if="attachedFile" class="flex items-center gap-2">
              <div class="relative shrink-0">
                <img v-if="attachedFilePreviewUrl" :src="attachedFilePreviewUrl" :alt="t('ai_chat_workspace.attached_image_alt')" class="w-14 h-14 rounded-lg object-cover border border-[#d9dfd4] dark:border-white/10" />
                <span v-else class="w-14 h-14 rounded-lg border border-[#d9dfd4] dark:border-white/10 flex items-center justify-center text-[#52733D] dark:text-[#8cc35a]"><FileText class="text-lg" aria-hidden="true" /></span>
                <button type="button" @click="clearAttachment" class="absolute -top-1.5 -end-1.5 w-5 h-5 rounded-full bg-[#c2413d] text-white flex items-center justify-center text-[9px]" :aria-label="t('ai_chat_workspace.remove_attachment_aria')"><X aria-hidden="true" /></button>
              </div>
              <span class="text-[10.5px] text-[#7c8078] dark:text-[#a8aaa5] truncate">{{ attachedFile.name }}</span>
            </div>
            <p v-if="attachmentError" class="alert-box">{{ attachmentError }}</p>
            <div class="flex flex-wrap gap-2"><button v-for="prompt in quickPrompts" :key="prompt" @click.prevent="addPrompt(prompt)" class="text-[10.5px] px-3 py-1.5 rounded-full border border-[#d7e4d2] dark:border-white/10 text-[#52733D] dark:text-[#8cc35a] hover:bg-[#eef4eb] dark:hover:bg-white/5">{{ prompt }}</button></div>
            <div class="flex items-center gap-2">
              <button type="button" @click="triggerFileInput" :disabled="chat.isStarting.value" class="w-9 h-9 rounded-xl border border-[#d9dfd4] dark:border-white/10 text-[#52733D] dark:text-[#8cc35a] hover:bg-[#eef4eb] dark:hover:bg-white/5 disabled:opacity-40 transition" :aria-label="t('ai_chat_workspace.attach_file_aria')"><Paperclip aria-hidden="true" /></button>
              <button type="button" @click="triggerCameraInput" :disabled="chat.isStarting.value" class="w-9 h-9 rounded-xl border border-[#d9dfd4] dark:border-white/10 text-[#52733D] dark:text-[#8cc35a] hover:bg-[#eef4eb] dark:hover:bg-white/5 disabled:opacity-40 transition" :aria-label="t('ai_chat_workspace.attach_camera_aria')"><Camera aria-hidden="true" /></button>
            </div>
            <button class="btn-fill-brand" :disabled="chat.isStarting.value"><LoaderCircle class="animate-spin" aria-hidden="true" v-if="chat.isStarting.value" /><Send aria-hidden="true" v-else />{{ chat.isStarting.value ? t("ai_chat_page.starting") : t("ai_chat_page.start_conversation") }}</button>
          </form>
        </div>
      </div>

      <div v-else-if="chat.activeSession.value" class="flex-1 min-w-0 flex flex-col bg-[#faf9f5]/40 dark:bg-transparent">
        <header class="px-4 py-3 border-b border-[#e7e2d6] dark:border-white/10 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5 min-w-0"><button type="button" @click="chat.activeSession.value = null" class="sm:hidden text-[#52733D] dark:text-[#8cc35a]" :aria-label="t('ai_chat_workspace.back_to_conversations')"><ArrowRight class="rtl:inline-block ltr:hidden" aria-hidden="true" /><ArrowLeft class="ltr:inline-block rtl:hidden" aria-hidden="true" /></button><span class="w-9 h-9 rounded-full bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center"><Bot aria-hidden="true" /></span><div class="min-w-0"><b class="block text-[12.5px] truncate">{{ isGeneralAdminChat ? t("ai_chat_workspace.general_support_title") : chat.activeSession.value.generator?.name }}</b><small class="text-[10px] text-[#28A745]"><Circle class="text-[7px] me-1" aria-hidden="true" />{{ effectiveTitle }}</small></div></div>
          <div class="flex gap-1.5"><button v-if="canCreatePrediction && !predictionSubmitted" @click="submitPrediction" :disabled="chat.isSubmittingPrediction.value" class="status-chip chip-warning !cursor-pointer">{{ t("ai_chat_workspace.record_prediction") }}</button><button v-if="canCreateReport && !reportSubmitted" @click="submitReport" :disabled="chat.isSubmittingFaultReport.value" class="status-chip chip-danger !cursor-pointer">{{ t("ai_chat_workspace.send_report") }}</button></div>
        </header>
        <div v-if="chat.predictionError.value || chat.faultReportError.value" class="px-4 py-2 text-[11px] text-[#c2413d] dark:text-[#f0a19c] bg-[#fce8e6] dark:bg-[#c2413d]/15">{{ chat.predictionError.value || chat.faultReportError.value }}</div>
        <main ref="messagesEl" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
          <div v-if="chat.isLoadingSession.value" class="text-center text-[12px] text-[#8b8e88] dark:text-[#a8aaa5] py-10">{{ t("ai_chat_workspace.loading_conversation") }}</div>
          <template v-else><div v-for="message in chat.messages.value" :key="message.id" class="flex gap-2.5" :class="message.role === 'user' ? 'flex-row-reverse' : ''"><span v-if="message.role !== 'user'" class="w-8 h-8 rounded-full bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center shrink-0"><Bot class="text-xs" aria-hidden="true" /></span><div class="max-w-[82%] sm:max-w-[72%]"><div v-if="message.attachments?.length" class="mb-1.5 flex flex-wrap gap-2" :class="message.role === 'user' ? 'justify-end' : ''"><a v-for="att in message.attachments" :key="att.id" :href="att.download_url" target="_blank" rel="noopener"><img v-if="att.mime_type?.startsWith('image/')" :src="att.preview_url" :alt="t('ai_chat_workspace.attached_image_alt')" class="max-w-[160px] max-h-[160px] rounded-xl border border-[#ece7dd] dark:border-white/10 object-cover" /><span v-else class="flex items-center gap-1.5 text-[11px] px-3 py-2 rounded-xl border border-[#ece7dd] dark:border-white/10 bg-white dark:bg-white/5"><FileText class="text-[#c2413d]" aria-hidden="true" />{{ att.original_name }}</span></a></div><div class="rounded-2xl px-4 py-2.5 text-[12.5px] leading-6 whitespace-pre-line shadow-sm" :class="message.role === 'user' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white rounded-be-sm' : 'bg-white dark:bg-white/10 rounded-bs-sm border border-[#ece7dd] dark:border-white/5'">{{ message.content }}</div><small class="block mt-1 text-[10px] text-[#969994] dark:text-[#8f938a]" :class="message.role === 'user' ? 'text-end' : ''">{{ formatTime(message.created_at) }}</small></div></div></template>
          <div v-if="chat.isSending.value" class="flex gap-2.5"><span class="w-8 h-8 rounded-full bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center"><Bot class="text-xs" aria-hidden="true" /></span><span class="bg-white dark:bg-white/10 border border-[#ece7dd] dark:border-white/5 rounded-2xl rounded-bs-sm px-4 py-3 text-[#8b8e88] dark:text-[#a8aaa5]"><Ellipsis class="animate-pulse" aria-hidden="true" /></span></div>
        </main>
        <footer class="p-3 sm:p-4 border-t border-[#e7e2d6] dark:border-white/10 bg-white/70 dark:bg-transparent"><div class="max-w-3xl mx-auto"><p v-if="chat.sendError.value" class="text-[11px] text-[#c2413d] dark:text-[#f0a19c] mb-2">{{ chat.sendError.value }}</p><div v-if="attachedFile" class="flex items-center gap-2 mb-2"><div class="relative shrink-0"><img v-if="attachedFilePreviewUrl" :src="attachedFilePreviewUrl" :alt="t('ai_chat_workspace.attached_image_alt')" class="w-12 h-12 rounded-lg object-cover border border-[#d9dfd4] dark:border-white/10" /><span v-else class="w-12 h-12 rounded-lg border border-[#d9dfd4] dark:border-white/10 flex items-center justify-center text-[#52733D] dark:text-[#8cc35a]"><FileText aria-hidden="true" /></span><button type="button" @click="clearAttachment" class="absolute -top-1.5 -end-1.5 w-5 h-5 rounded-full bg-[#c2413d] text-white flex items-center justify-center text-[9px]" :aria-label="t('ai_chat_workspace.remove_attachment_aria')"><X aria-hidden="true" /></button></div><span class="text-[10.5px] text-[#7c8078] dark:text-[#a8aaa5] truncate">{{ attachedFile.name }}</span></div><p v-if="attachmentError" class="alert-box mb-2">{{ attachmentError }}</p><form @submit.prevent="send" class="flex items-end gap-2 rounded-2xl border border-[#d9dfd4] dark:border-white/10 p-1.5 bg-white dark:bg-white/5 shadow-sm"><button type="button" @click="triggerFileInput" :disabled="chat.isSending.value" class="w-10 h-10 shrink-0 rounded-xl text-[#52733D] dark:text-[#8cc35a] hover:bg-[#eef4eb] dark:hover:bg-white/5 disabled:opacity-40 transition" :aria-label="t('ai_chat_workspace.attach_file_aria')"><Paperclip aria-hidden="true" /></button><button type="button" @click="triggerCameraInput" :disabled="chat.isSending.value" class="w-10 h-10 shrink-0 rounded-xl text-[#52733D] dark:text-[#8cc35a] hover:bg-[#eef4eb] dark:hover:bg-white/5 disabled:opacity-40 transition" :aria-label="t('ai_chat_workspace.attach_camera_aria')"><Camera aria-hidden="true" /></button><textarea v-model="draft" rows="1" maxlength="2000" class="flex-1 resize-none bg-transparent px-3 py-2 text-[12.5px] outline-none" :placeholder="t('ai_chat_page.type_message_placeholder')" @keydown.enter.exact.prevent="send"></textarea><button type="submit" :disabled="chat.isSending.value || !draft.trim()" class="w-10 h-10 rounded-xl bg-[#52733D] hover:bg-[#3E582E] disabled:opacity-40 text-white transition" :aria-label="t('ai_chat_page.start_conversation')"><ArrowUp aria-hidden="true" /></button></form><p class="text-center text-[9.5px] text-[#9a9d97] dark:text-[#8f938a] mt-2">{{ t("ai_chat_workspace.disclaimer") }}</p></div></footer>
      </div>

      <div v-else class="flex-1 flex flex-col items-center justify-center p-6 text-center"><span class="w-14 h-14 rounded-2xl bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center text-xl mb-4"><MessagesSquare aria-hidden="true" /></span><h2 class="font-bold text-[14px]">{{ t("ai_chat_workspace.empty_state_heading") }}</h2><p class="text-[12px] text-[#8b8e88] dark:text-[#a8aaa5] mt-1">{{ t("ai_chat_workspace.empty_state_subtitle") }}</p><button @click="startNewChat" class="btn-fill-brand mt-5">{{ t("ai_chat_page.new_conversation") }}</button></div>
    </div>
  </section>
</template>
