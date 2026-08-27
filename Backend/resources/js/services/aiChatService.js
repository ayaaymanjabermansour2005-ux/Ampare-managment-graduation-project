import http from "./http";

export default {
  availableGenerators() {
    return http.get("/ai-chat/available-generators");
  },
  list(params = {}) {
    return http.get("/ai-chat/sessions", { params });
  },
  show(sessionId) {
    return http.get(`/ai-chat/sessions/${sessionId}`);
  },
  startSession(generatorId, message, file) {
    if (file) {
      const formData = new FormData();
      if (generatorId !== undefined && generatorId !== null) formData.append("generator_id", generatorId);
      if (message) formData.append("message", message);
      formData.append("attachment", file);
      return http.post("/ai-chat/sessions", formData, { idempotent: true });
    }

    return http.post(
      "/ai-chat/sessions",
      {
        generator_id: generatorId,
        message: message || undefined,
      },
      { idempotent: true },
    );
  },
  sendMessage(sessionId, message, file) {
    if (file) {
      const formData = new FormData();
      formData.append("message", message);
      formData.append("attachment", file);
      return http.post(`/ai-chat/sessions/${sessionId}/messages`, formData, { idempotent: true });
    }

    return http.post(
      `/ai-chat/sessions/${sessionId}/messages`,
      { message },
      { idempotent: true },
    );
  },
  submitAsPrediction(sessionId) {
    return http.post(`/ai-chat/sessions/${sessionId}/submit-as-prediction`);
  },
  submitAsFaultReport(sessionId) {
    return http.post(`/ai-chat/sessions/${sessionId}/submit-as-fault-report`);
  },
};
