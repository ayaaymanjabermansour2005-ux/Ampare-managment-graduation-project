import { onMounted, onUnmounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useNotificationStore } from "@/stores/notification";
import { useToastStore } from "@/stores/toast";
import { playNotificationSound } from "@/utils/playNotificationSound";

let activeChannel = null;
let subscribedUserId = null;
let subscriberCount = 0;

function leaveActiveChannel() {
  if (subscribedUserId && window.Echo) {
    window.Echo.leave(`App.Models.User.${subscribedUserId}`);
  }
  activeChannel = null;
  subscribedUserId = null;
}

export function useRealtimeNotifications() {
  const authStore = useAuthStore();
  const notificationStore = useNotificationStore();
  const toastStore = useToastStore();

  function subscribe() {
    if (!authStore.user || !window.Echo) return;
    if (activeChannel && subscribedUserId === authStore.user.id) return;

    // مستخدم مختلف عن آخر اشتراك نشط (تبديل حساب بدون إعادة تحميل الصفحة) —
    // لازم نغادر القناة القديمة صراحةً قبل الاشتراك بالجديدة، وإلا بتضل
    // القناة القديمة مفتوحة وممكن توصل إشعارات مستخدم سابق لجلسة تانية.
    if (activeChannel && subscribedUserId !== authStore.user.id) {
      leaveActiveChannel();
    }

    activeChannel = window.Echo.private(`App.Models.User.${authStore.user.id}`);
    subscribedUserId = authStore.user.id;

    activeChannel.notification((notification) => {
      notificationStore.addNotification({
        id: notification.id,
        type: notification.type ?? "notification",
        title: notification.title,
        message: notification.message,
        link_type: notification.link_type ?? null,
        link_id: notification.link_id ?? null,
        is_read: false,
        created_at: new Date().toISOString(),
      });

      toastStore.show({ title: notification.title, message: notification.message, type: "info" });
      playNotificationSound();
    });
  }

  function unsubscribe() {
    leaveActiveChannel();
  }

  onMounted(() => {
    subscriberCount++;
    if (authStore.isAuthenticated) {
      subscribe();
    }
  });

  onUnmounted(() => {
    subscriberCount = Math.max(0, subscriberCount - 1);
    if (subscriberCount === 0) {
      unsubscribe();
    }
  });

  return { subscribe, unsubscribe };
}

// يُستدعى صراحةً من auth store عند تسجيل الخروج — لأن App.vue بيبقى mounted
// طول عمر الجلسة، فـ subscriberCount (المعتمد على unmount المكوّنات) ما
// بيوصل صفر أبداً بعد أول mount، وبالتالي unsubscribe() العادي ما بينفّذ.
export function forceLeaveRealtimeChannel() {
  leaveActiveChannel();
}
