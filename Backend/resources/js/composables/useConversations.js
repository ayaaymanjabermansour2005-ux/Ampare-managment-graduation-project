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
      error.value = err.response?.data?.message ?? t("owner_messages.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const activeConversation = ref(null);
  const messages = ref([]);
  const isLoadingMessages = ref(false);

  async function openConversation(conversation) {
    activeConversation.value = conversation;
    isLoadingMessages.value = true;
    try {
      const { data } = await conversationService.messages(conversation.id, {
        per_page: 50,
      });
      const payload = data.data;
      messages.value = (payload.data ?? payload).slice().reverse();
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
      sendError.value = err.response?.data?.message ?? t("owner_messages.send_error");
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
      startError.value = err.response?.data?.message ?? t("owner_messages.start_error");
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
      startSupportError.value = err.response?.data?.message ?? t("owner_messages.start_error");
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
      startWithOwnerError.value = err.response?.data?.message ?? t("owner_messages.start_error");
      return false;
    } finally {
      isStartingWithOwner.value = false;
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
      convertError.value = err.response?.data?.message ?? t("messages_page.convert_error");
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
    currentUserId: authStore.user?.id,
  };
}