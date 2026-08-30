import { defineStore } from "pinia";
import { ref, computed } from "vue";
import authService from "@/services/authService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { useConnectivityStore } from "./connectivity";
import { useNotificationStore } from "./notification";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const roles = ref([]);
  const permissions = ref([]);
  const isLoading = ref(false);
  const errors = ref(null);
  const errorMessage = ref(null);
  const initialized = ref(false);

  const isAuthenticated = computed(() => !!user.value);

  function hasRole(role) {
    return roles.value.includes(role);
  }

  function can(permission) {
    return permissions.value.includes(permission);
  }

  function setUserData(payload) {
    user.value = payload;
    roles.value = payload?.roles?.map((r) => r.name) ?? [];
    permissions.value = payload?.permissions ?? [];
  }

  function clearUserData() {
    user.value = null;
    roles.value = [];
    permissions.value = [];

    // لازم نصفّر إشعارات المستخدم السابق هون كمان — وإلا بضل شكل قديم بالـ
    // store لحظات لحد ما fetchNotifications() تنفّذ لحساب المستخدم الجديد،
    // نفس مبدأ تصفير قناة الـ WebSocket بالأعلى.
    useNotificationStore().$reset();
  }

  async function register(payload) {
    isLoading.value = true;
    errors.value = null;
    errorMessage.value = null;
    try {
      const data = await authService.register(payload);
      return data;
    } catch (error) {
      const normalized = normalizeApiError(error);
      errors.value = normalized.fieldErrors;
      errorMessage.value = normalized.message;
      throw error;
    } finally {
      isLoading.value = false;
    }
  }

  async function login(payload) {
    isLoading.value = true;
    errors.value = null;
    errorMessage.value = null;
    try {
      const data = await authService.login(payload);
      setUserData(data.data);
      useConnectivityStore().retryAfterReauth();
      return data;
    } catch (error) {
      const normalized = normalizeApiError(error);
      errors.value = normalized.fieldErrors;
      errorMessage.value = normalized.message;
      throw error;
    } finally {
      isLoading.value = false;
    }
  }

  async function logout() {
    isLoading.value = true;

    const connectivityStore = useConnectivityStore();
    const hadPendingSync = user.value
      ? connectivityStore.pendingCount > 0
      : false;

    try {
      await authService.logout();
    } catch (error) {
      console.warn(
        "Logout request failed, clearing local state anyway.",
        error,
      );
    } finally {
      await connectivityStore.clearOwnQueueOnLogout();
      clearUserData();
      isLoading.value = false;

      // لازم نغادر قناة الإشعارات الفورية صراحةً هون — App.vue بيبقى
      // mounted طول عمر الجلسة، فمنطق unsubscribe المعتمد على unmount
      // المكوّنات ما بيتفعّل بعد تسجيل الخروج، وبتضل القناة مفتوحة.
      const { forceLeaveRealtimeChannel } = await import(
        "@/composables/useRealtimeNotifications"
      );
      forceLeaveRealtimeChannel();

      // FRONT-009: كاش Workbox (Cache Storage) بيحتفظ باستجابات NetworkFirst
      // (فواتير، اشتراكات، بيانات فنيين...) لغاية 24 ساعة — كان يبقى بعد
      // تسجيل الخروج على جهاز مشترك (كانت فقط طابور IndexedDB يتنضّف).
      // نمسح الكاشات المسمّاة صراحةً بدل مسح كل شيء (service worker نفسه
      // وأي كاش أصول ثابتة يجب أن يبقيان).
      if (typeof caches !== "undefined") {
        try {
          await Promise.all(
            ["ampare-api-cache", "ampare-technician-api-cache"].map((name) =>
              caches.delete(name),
            ),
          );
        } catch (error) {
          console.warn("Failed to purge Workbox caches on logout.", error);
        }
      }
    }

    return { hadPendingSync };
  }

  async function fetchUser() {
    try {
      const data = await authService.me();
      setUserData(data.data);
    } catch {
      clearUserData();
    } finally {
      initialized.value = true;
    }
  }

  function clearLocalSession() {
    clearUserData();
  }

  return {
    user,
    roles,
    permissions,
    isLoading,
    errors,
    errorMessage,
    initialized,
    isAuthenticated,
    hasRole,
    can,
    register,
    login,
    logout,
    fetchUser,
    clearLocalSession,
  };
});
