import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import aiChatService from "@/services/aiChatService";

export function useAiChat() {
  const { t } = useI18n();
  const generators = ref([]);
  const isLoadingGenerators = ref(true);
  const generatorsError = ref(null);

  async function loadGenerators() {
    isLoadingGenerators.value = true;
    generatorsError.value = null;
    try {
      const { data } = await aiChatService.availableGenerators();
      generators.value = data.data;
    } catch (err) {
      generatorsError.value =
        normalizeApiError(err, t("owner_ai_chat.generators_load_error")).message;
    } finally {
      isLoadingGenerators.value = false;
    }
  }

  const sessions = ref([]);
  const isLoadingSessions = ref(false);
  const sessionsError = ref(null);

  async function loadSessions() {
    isLoadingSessions.value = true;
    sessionsError.value = null;
    try {
      const { data } = await aiChatService.list({ per_page: 20 });
      const payload = data.data;
      sessions.value = payload.data ?? payload;
    } catch (err) {
      sessionsError.value =
        normalizeApiError(err, t("owner_ai_chat.sessions_load_error")).message;
    } finally {
      isLoadingSessions.value = false;
    }
  }

  const activeSession = ref(null);
  const messages = ref([]);
  const isLoadingSession = ref(false);
  const sessionError = ref(null);

  async function openSession(sessionId) {
    isLoadingSession.value = true;
    sessionError.value = null;
    try {
      const { data } = await aiChatService.show(sessionId);
      activeSession.value = data.data;
      messages.value = data.data.messages ?? [];
    } catch (err) {
      sessionError.value =
        normalizeApiError(err, t("owner_ai_chat.session_load_error")).message;
    } finally {
      isLoadingSession.value = false;
    }
  }

  const isStarting = ref(false);
  const startError = ref(null);

  async function startSession(generatorId, firstMessage, file) {
    isStarting.value = true;
    startError.value = null;
    try {
      const { data } = await aiChatService.startSession(
        generatorId,
        firstMessage,
        file,
      );
      activeSession.value = data.data;
      messages.value = data.data.messages ?? [];
      sessions.value.unshift(data.data);
      return true;
    } catch (err) {
      startError.value = normalizeApiError(err, t("owner_ai_chat.start_error")).message;
      return false;
    } finally {
      isStarting.value = false;
    }
  }

  const isSending = ref(false);
  const sendError = ref(null);

  // FIX: الباك اند (SendAiChatMessageAction) بيرجّع رد الذكاء الاصطناعي
  // بس، مش رسالة المستخدم نفسها — لازم نضيفها محلياً (optimistic) قبل
  // إرسال الطلب، وبعدين نلحق رد الذكاء الاصطناعي لما يوصل.
  async function sendMessage(text, file) {
    if (!activeSession.value) return false;

    const tempId = `temp-${Date.now()}`;
    messages.value.push({
      id: tempId,
      role: "user",
      content: text,
      created_at: new Date().toISOString(),
    });

    isSending.value = true;
    sendError.value = null;
    try {
      const { data } = await aiChatService.sendMessage(
        activeSession.value.id,
        text,
        file,
      );
      messages.value.push(data.data);
      return true;
    } catch (err) {
      // FIX-006: الرسالة التفاؤلية (optimistic) تتحذف لو الإرسال فشل فعليًا،
      // بدل ما تضل ظاهرة بالواجهة وكأنها انبعتت بنجاح.
      const tempIndex = messages.value.findIndex((m) => m.id === tempId);
      if (tempIndex !== -1) messages.value.splice(tempIndex, 1);
      sendError.value =
        normalizeApiError(err, t("owner_ai_chat.send_error")).message;
      return false;
    } finally {
      isSending.value = false;
    }
  }

  const isSubmittingPrediction = ref(false);
  const predictionError = ref(null);

  async function submitAsPrediction() {
    if (!activeSession.value) return false;
    isSubmittingPrediction.value = true;
    predictionError.value = null;
    try {
      await aiChatService.submitAsPrediction(activeSession.value.id);
      return true;
    } catch (err) {
      predictionError.value =
        normalizeApiError(err, t("owner_ai_chat.prediction_error")).message;
      return false;
    } finally {
      isSubmittingPrediction.value = false;
    }
  }

  const isSubmittingFaultReport = ref(false);
  const faultReportError = ref(null);

  async function submitAsFaultReport() {
    if (!activeSession.value) return false;
    isSubmittingFaultReport.value = true;
    faultReportError.value = null;
    try {
      await aiChatService.submitAsFaultReport(activeSession.value.id);
      return true;
    } catch (err) {
      faultReportError.value =
        normalizeApiError(err, t("owner_ai_chat.fault_report_error")).message;
      return false;
    } finally {
      isSubmittingFaultReport.value = false;
    }
  }

  return {
    generators,
    isLoadingGenerators,
    generatorsError,
    loadGenerators,
    sessions,
    isLoadingSessions,
    sessionsError,
    loadSessions,
    activeSession,
    messages,
    isLoadingSession,
    sessionError,
    openSession,
    isStarting,
    startError,
    startSession,
    isSending,
    sendError,
    sendMessage,
    isSubmittingPrediction,
    predictionError,
    submitAsPrediction,
    isSubmittingFaultReport,
    faultReportError,
    submitAsFaultReport,
  };
}
