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
  sendMessage(conversationId, messageText) {
    return http.post(`/conversations/${conversationId}/messages`, {
      message_text: messageText,
    });
  },
  convertToIssue(conversationId, payload) {
    return http.post(`/conversations/${conversationId}/convert-to-issue`, payload);
  },
};