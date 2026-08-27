import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import userService from "@/services/userService";
import authService from "@/services/authService";

export function useSubscriberProfile() {
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
      authStore.user.name = data.data.name;
      authStore.user.email = data.data.email;
      authStore.user.phone = data.data.phone;
      profileSuccess.value = true;
      return true;
    } catch (err) {
      profileError.value = err.response?.data ?? {
        message: t("owner_settings.profile.save_error"),
      };
      return false;
    } finally {
      isSavingProfile.value = false;
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
      passwordError.value = err.response?.data ?? {
        message: t("owner_settings.security.password_error"),
      };
      return false;
    } finally {
      isChangingPassword.value = false;
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
      return true;
    } catch (err) {
      logoutOthersError.value =
        err.response?.data?.message ?? t("owner_settings.security.wrong_password");
      return false;
    } finally {
      isLoggingOutOthers.value = false;
    }
  }

  return {
    isSavingProfile,
    profileError,
    profileSuccess,
    updateProfile,
    isChangingPassword,
    passwordError,
    passwordSuccess,
    changePassword,
    isLoggingOutOthers,
    logoutOthersError,
    logoutOthersSuccess,
    logoutOtherDevices,
  };
}
