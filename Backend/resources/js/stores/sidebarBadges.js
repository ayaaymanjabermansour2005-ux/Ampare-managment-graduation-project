import { defineStore } from "pinia";
import { ref } from "vue";
import sidebarService from "@/services/sidebarService";
import conversationService from "@/services/conversationService";

const REFRESH_INTERVAL_MS = 60_000;

export const useSidebarBadgesStore = defineStore("sidebarBadges", () => {
  const counts = ref({
    owner_applications_pending: 0,
    payments_pending: 0,
    complaints_open: 0,
    faults_pending: 0,
    contact_messages_new: 0,
    messages_unread: 0,
    generators_pending_verification: 0,
    users_locked: 0,
    invoices_overdue: 0,
    article_comments_pending: 0,
  });
  const isLoaded = ref(false);
  let refreshTimer = null;

  async function fetchCounts() {
    try {
      const [badgesRes, unreadRes] = await Promise.all([
        sidebarService.badgeCounts(),
        conversationService.unreadCount(),
      ]);
      counts.value = {
        ...badgesRes.data.data,
        messages_unread: unreadRes.data.data.unread_count ?? 0,
      };
    } catch (error) {
      // فشل هادئ مقصود بالواجهة — القيم بتضل بآخر تحديث ناجح لحد ما
      // الـ interval التالي (60 ثانية) يعيد المحاولة، بدون ما نقاطع
      // المستخدم بخطأ غير حرج على أرقام الشارات الجانبية. بنسجّله فقط
      // للتشخيص.
      console.error("Failed to fetch sidebar badge counts.", error);
    } finally {
      isLoaded.value = true;
    }
  }

  function startAutoRefresh() {
    if (refreshTimer) return;
    fetchCounts();
    refreshTimer = setInterval(fetchCounts, REFRESH_INTERVAL_MS);
  }

  function stopAutoRefresh() {
    clearInterval(refreshTimer);
    refreshTimer = null;
  }

  return { counts, isLoaded, fetchCounts, startAutoRefresh, stopAutoRefresh };
});
