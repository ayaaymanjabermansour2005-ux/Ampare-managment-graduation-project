import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import conversationService from "@/services/conversationService";
import { useAuthStore } from "@/stores/auth";

export function useConversations() {
  const { t } = useI18n();
  const authStore = useAuthStore();

  const conversations = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchConversations() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await conversationService.list({ per_page: 30 });
      const payload = data.data;
      conversations.value = payload.data ?? payload;
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_messages.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const activeConversation = ref(null);
  const messages = ref([]);
  const isLoadingMessages = ref(false);
  const messagesError = ref(null);

  async function openConversation(conversation) {
    activeConversation.value = conversation;
    isLoadingMessages.value = true;
    messagesError.value = null;
    try {
      const { data } = await conversationService.messages(conversation.id, {
        per_page: 50,
      });
      const payload = data.data;
      messages.value = (payload.data ?? payload).slice().reverse();
    } catch (err) {
      // Without this, a failed fetch left the PREVIOUS conversation's stale
      // messages rendered under the newly selected conversation's header — and
      // the exception propagated into startConversation/startSupportConversation/
      // startWithOwnerConversation's own try/catch (they all `await
      // openConversation(...)` internally), misreporting a messages-load failure
      // as a failed conversation *start* even though the conversation was created.
      messages.value = [];
      messagesError.value = normalizeApiError(err, t("owner_messages.messages_load_error")).message;
    } finally {
      isLoadingMessages.value = false;
    }
  }

  const isSending = ref(false);
  const sendError = ref(null);

  async function sendMessage(text) {
    if (!activeConversation.value) return false;
    isSending.value = true;
    sendError.value = null;
    try {
      const { data } = await conversationService.sendMessage(
        activeConversation.value.id,
        text,
      );
      messages.value.push(data.data);
      return true;
    } catch (err) {
      sendError.value = normalizeApiError(err, t("owner_messages.send_error")).message;
      return false;
    } finally {
      isSending.value = false;
    }
  }

  const isStarting = ref(false);
  const startError = ref(null);

  async function startConversation(userId) {
    isStarting.value = true;
    startError.value = null;
    try {
      const { data } = await conversationService.start(userId);
      const existing = conversations.value.find((c) => c.id === data.data.id);
      if (!existing) conversations.value.unshift(data.data);
      await openConversation(data.data);
      return true;
    } catch (err) {
      startError.value = normalizeApiError(err, t("owner_messages.start_error")).message;
      return false;
    } finally {
      isStarting.value = false;
    }
  }

  const isStartingSupport = ref(false);
  const startSupportError = ref(null);

  async function startSupportConversation() {
    isStartingSupport.value = true;
    startSupportError.value = null;
    try {
      const { data } = await conversationService.startSupport();
      const existing = conversations.value.find((c) => c.id === data.data.id);
      if (!existing) conversations.value.unshift(data.data);
      await openConversation(data.data);
      return true;
    } catch (err) {
      startSupportError.value = normalizeApiError(err, t("owner_messages.start_error")).message;
      return false;
    } finally {
      isStartingSupport.value = false;
    }
  }

  const isStartingWithOwner = ref(false);
  const startWithOwnerError = ref(null);

  async function startWithOwnerConversation() {
    isStartingWithOwner.value = true;
    startWithOwnerError.value = null;
    try {
      const { data } = await conversationService.startWithOwner();
      const existing = conversations.value.find((c) => c.id === data.data.id);
      if (!existing) conversations.value.unshift(data.data);
      await openConversation(data.data);
      return true;
    } catch (err) {
      startWithOwnerError.value = normalizeApiError(err, t("owner_messages.start_error")).message;
      return false;
    } finally {
      isStartingWithOwner.value = false;
    }
  }

  /* ---------------- حذف محادثة ----------------
   * FIX (تدقيق شامل للوحة الأدمن — بند 8): DELETE /conversations/{id} جاهز
   * بالكامل بالباك اند (ConversationPolicy::delete) لكن conversationService.
   * destroy() لم يكن مستخدَمًا من أي واجهة إطلاقًا.
   */
  const isDeletingConversation = ref(false);
  const deleteConversationError = ref(null);

  async function deleteConversation(conversationId) {
    isDeletingConversation.value = true;
    deleteConversationError.value = null;
    try {
      await conversationService.destroy(conversationId);
      conversations.value = conversations.value.filter((c) => c.id !== conversationId);
      if (activeConversation.value?.id === conversationId) {
        activeConversation.value = null;
        messages.value = [];
      }
      return true;
    } catch (err) {
      deleteConversationError.value = normalizeApiError(err, t("messages_page.delete_conversation_error")).message;
      return false;
    } finally {
      isDeletingConversation.value = false;
    }
  }

  const isConverting = ref(false);
  const convertError = ref(null);

  async function convertToIssue(category, generatorId) {
    if (!activeConversation.value) return false;
    isConverting.value = true;
    convertError.value = null;
    try {
      const { data } = await conversationService.convertToIssue(activeConversation.value.id, {
        category,
        generator_id: generatorId ?? undefined,
      });
      return data.data;
    } catch (err) {
      convertError.value = normalizeApiError(err, t("messages_page.convert_error")).message;
      return false;
    } finally {
      isConverting.value = false;
    }
  }

  return {
    conversations,
    isLoading,
    error,
    fetchConversations,
    activeConversation,
    messages,
    isLoadingMessages,
    messagesError,
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
    isStartingWithOwner,
    startWithOwnerError,
    startWithOwnerConversation,
    isConverting,
    convertError,
    convertToIssue,
    isDeletingConversation,
    deleteConversationError,
    deleteConversation,
    currentUserId: authStore.user?.id,
  };
}