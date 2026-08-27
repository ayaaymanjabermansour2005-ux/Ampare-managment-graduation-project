import { defineStore } from "pinia";
import {
  queueCount,
  queueCountForUser,
  clearQueueForUser,
} from "@/utils/offlineQueue";
import { flushQueue } from "@/utils/syncQueue";
import { useAuthStore } from "./auth";

export const useConnectivityStore = defineStore("connectivity", {
  state: () => ({
    isOnline: navigator.onLine,
    pendingCount: 0,
    isSyncing: false,
    needsReauth: false,
  }),

  actions: {
    init() {
      window.addEventListener("online", () => {
        this.isOnline = true;
        this.sync();
      });

      window.addEventListener("offline", () => {
        this.isOnline = false;
      });

      this.refreshPendingCount();

      if (this.isOnline) {
        this.sync();
      }
    },

    async refreshPendingCount() {
      const authStore = useAuthStore();

      this.pendingCount = authStore.user
        ? await queueCountForUser(authStore.user.id)
        : await queueCount();
    },

    async sync() {
      const authStore = useAuthStore();

      if (this.isSyncing || !this.isOnline || !authStore.user) return;

      this.isSyncing = true;
      try {
        const result = await flushQueue(authStore.user.id);
        this.needsReauth = result.needsReauth;
      } finally {
        await this.refreshPendingCount();
        this.isSyncing = false;
      }
    },

    async retryAfterReauth() {
      this.needsReauth = false;
      await this.sync();
    },

    async clearOwnQueueOnLogout() {
      const authStore = useAuthStore();
      if (!authStore.user) return;

      await clearQueueForUser(authStore.user.id);
      this.pendingCount = 0;
    },
  },
});
