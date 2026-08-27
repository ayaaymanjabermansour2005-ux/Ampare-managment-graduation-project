import http from "./http";

export default {
  async getAll(params = {}) {
    const { data } = await http.get("/notifications", { params });
    return data;
  },

  async unreadCount() {
    const { data } = await http.get("/notifications/unread-count");
    return data;
  },

  async markAsRead(notificationId) {
    const { data } = await http.patch(`/notifications/${notificationId}/read`);
    return data;
  },

  async markAllAsRead() {
    const { data } = await http.patch("/notifications/read-all");
    return data;
  },

  async delete(notificationId) {
    const { data } = await http.delete(`/notifications/${notificationId}`);
    return data;
  },
};
