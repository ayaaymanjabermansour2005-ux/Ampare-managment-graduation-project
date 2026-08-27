import { defineStore } from "pinia";
import notificationService from "@/services/notificationService";

export const useNotificationStore = defineStore("notification", {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    pagination: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
  }),

  getters: {
    unreadNotifications: (state) =>
      state.notifications.filter((n) => !n.is_read),
  },

  actions: {
    async fetchNotifications(page = 1) {
      this.loading = true;
      try {
        const response = await notificationService.getAll({ page });
        const payload = response.data ?? {};
        this.notifications = payload.data ?? [];
        this.pagination = {
          current_page: payload.current_page ?? 1,
          last_page: payload.last_page ?? 1,
          total: payload.total ?? this.notifications.length,
          per_page: payload.per_page ?? 15,
        };
        // عدّاد غير المقروء يبقى إجماليًا حقيقيًا عبر endpoint مخصص، وليس
        // مبنيًا على الصفحة الحالية المعروضة فقط.
        await this.fetchUnreadCount();
      } finally {
        this.loading = false;
      }
    },

    async fetchUnreadCount() {
      try {
        const response = await notificationService.unreadCount();
        this.unreadCount = response.data?.unread_count ?? this.unreadCount;
      } catch {
        // تجاهل — يبقى العدّاد الحالي كما هو لو فشل الطلب.
      }
    },

    addNotification(notification) {
      // حماية من ازدواجية الإشعار — ممكن توصل نفس الحادثة مرتين (مثلاً
      // WebSocket push بالتزامن مع fetchNotifications() REST، أو إعادة
      // اتصال Echo بعد انقطاع مؤقت).
      if (this.notifications.some((n) => n.id === notification.id)) return;

      this.notifications.unshift(notification);
      this.unreadCount++;
    },

    async markAsRead(id) {
      await notificationService.markAsRead(id);
      const notification = this.notifications.find((n) => n.id === id);
      if (notification && !notification.is_read) {
        notification.is_read = true;
        this.unreadCount--;
      }
    },

    async markAllAsRead() {
      await notificationService.markAllAsRead();
      this.notifications.forEach((n) => {
        n.is_read = true;
      });
      this.unreadCount = 0;
    },

    async deleteNotification(id) {
      await notificationService.delete(id);
      this.notifications = this.notifications.filter((n) => n.id !== id);
    },
  },
});