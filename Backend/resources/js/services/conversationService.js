import http from "./http";

export default {
  list(params = {}) {
    return http.get("/conversations", { params });
  },
  show(id) {
    return http.get(`/conversations/${id}`);
  },
  start(userId) {
    return http.post("/conversations", { user_id: userId });
  },
  startSupport() {
    return http.post("/conversations/start-support");
  },
  startWithOwner() {
    return http.post("/conversations/start-with-owner");
  },
  destroy(id) {
    return http.delete(`/conversations/${id}`);
  },
  unreadCount() {
    return http.get("/conversations/unread-count");
  },
  messages(conversationId, params = {}) {
    return http.get(`/conversations/${conversationId}/messages`, { params });
  },
  /**
   * FIX (تدقيق شامل — D4): الباك يدعم إرفاق حتى 5 ملفات بالرسالة أصلًا
   * (StoreMessageRequest) لكن لم تكن هناك وسيلة لإرسالها من الواجهة إطلاقًا.
   */
  sendMessage(conversationId, messageText, files = []) {
    if (!files.length) {
      return http.post(`/conversations/${conversationId}/messages`, {
        message_text: messageText,
      });
    }
    const formData = new FormData();
    if (messageText) formData.append("message_text", messageText);
    files.forEach((file) => formData.append("attachments[]", file));
    return http.post(`/conversations/${conversationId}/messages`, formData);
  },
  convertToIssue(conversationId, payload) {
    return http.post(`/conversations/${conversationId}/convert-to-issue`, payload);
  },
};