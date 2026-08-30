import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import userService from "@/services/userService";
import authService from "@/services/authService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useAccountSettings() {
  const { t } = useI18n();
  const authStore = useAuthStore();

  const isSavingProfile = ref(false);
  const profileError = ref(null);
  const profileSuccess = ref(false);

  async function updateProfile(payload) {
    isSavingProfile.value = true;
    profileError.value = null;
    profileSuccess.value = false;
    try {
      const { data } = await userService.update(authStore.user.id, payload);
      authStore.user = { ...authStore.user, ...data.data };
      profileSuccess.value = true;
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("owner_settings.profile.save_error"));
      profileError.value = { message: normalized.message, errors: normalized.fieldErrors };
      return false;
    } finally {
      isSavingProfile.value = false;
    }
  }

  const isUploadingAvatar = ref(false);
  const avatarError = ref(null);

  async function uploadAvatar(file) {
    isUploadingAvatar.value = true;
    avatarError.value = null;
    try {
      const { data } = await userService.updateAvatar(authStore.user.id, file);
      authStore.user = { ...authStore.user, avatar_url: data.data.avatar_url };
      return true;
    } catch (err) {
      avatarError.value = normalizeApiError(err, t("owner_settings.profile.avatar_error")).message;
      return false;
    } finally {
      isUploadingAvatar.value = false;
    }
  }

  const isChangingPassword = ref(false);
  const passwordError = ref(null);
  const passwordSuccess = ref(false);

  async function changePassword(payload) {
    isChangingPassword.value = true;
    passwordError.value = null;
    passwordSuccess.value = false;
    try {
      await authService.changePassword(payload);
      passwordSuccess.value = true;
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("owner_settings.security.password_error"));
      passwordError.value = { message: normalized.message, errors: normalized.fieldErrors };
      return false;
    } finally {
      isChangingPassword.value = false;
    }
  }

  const sessions = ref([]);
  const isLoadingSessions = ref(false);
  const sessionsError = ref(null);
  const revokingSessionId = ref(null);

  async function fetchSessions() {
    isLoadingSessions.value = true;
    sessionsError.value = null;
    try {
      const { data } = await authService.sessions();
      sessions.value = data.data;
    } catch (err) {
      sessionsError.value = normalizeApiError(err, t("owner_settings.security.sessions_load_error")).message;
    } finally {
      isLoadingSessions.value = false;
    }
  }

  async function revokeSession(sessionId) {
    revokingSessionId.value = sessionId;
    try {
      await authService.revokeSession(sessionId);
      sessions.value = sessions.value.filter((s) => s.id !== sessionId);
      return true;
    } catch {
      return false;
    } finally {
      revokingSessionId.value = null;
    }
  }

  const isLoggingOutOthers = ref(false);
  const logoutOthersError = ref(null);
  const logoutOthersSuccess = ref(false);

  async function logoutOtherDevices(password) {
    isLoggingOutOthers.value = true;
    logoutOthersError.value = null;
    logoutOthersSuccess.value = false;
    try {
      await authService.logoutOtherDevices(password);
      logoutOthersSuccess.value = true;
      await fetchSessions();
      return true;
    } catch (err) {
      logoutOthersError.value = normalizeApiError(err, t("owner_settings.security.wrong_password")).message;
      return false;
    } finally {
      isLoggingOutOthers.value = false;
    }
  }

  const loginLog = ref([]);
  const isLoadingLoginLog = ref(false);
  const loginLogError = ref(null);

  async function fetchLoginLog() {
    isLoadingLoginLog.value = true;
    loginLogError.value = null;
    try {
      const { data } = await authService.loginLog();
      loginLog.value = data.data.data ?? data.data;
    } catch (err) {
      loginLogError.value = normalizeApiError(err, t("owner_settings.security.login_log_load_error")).message;
    } finally {
      isLoadingLoginLog.value = false;
    }
  }

  const isDeletingAccount = ref(false);
  const deleteAccountError = ref(null);

  async function deleteAccount(password) {
    isDeletingAccount.value = true;
    deleteAccountError.value = null;
    try {
      await authService.deleteAccount(password);
      await authStore.logout();
      return true;
    } catch (err) {
      deleteAccountError.value = normalizeApiError(err, t("owner_settings.danger.delete_error")).message;
      return false;
    } finally {
      isDeletingAccount.value = false;
    }
  }

  return {
    isSavingProfile,
    profileError,
    profileSuccess,
    updateProfile,
    isUploadingAvatar,
    avatarError,
    uploadAvatar,
    isChangingPassword,
    passwordError,
    passwordSuccess,
    changePassword,
    sessions,
    isLoadingSessions,
    sessionsError,
    revokingSessionId,
    fetchSessions,
    revokeSession,
    isLoggingOutOthers,
    logoutOthersError,
    logoutOthersSuccess,
    logoutOtherDevices,
    loginLog,
    isLoadingLoginLog,
    loginLogError,
    fetchLoginLog,
    isDeletingAccount,
    deleteAccountError,
    deleteAccount,
  };
}