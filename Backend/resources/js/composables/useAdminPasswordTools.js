import { reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * أدوات كلمة السر لصفحة المشتركين (رابط إعادة تعيين / تعيين مباشر) —
 * منقولة من SubscribersView.vue (FRONT-004a slice 2، God-component breakdown).
 */
export function useAdminPasswordTools() {
  const { t } = useI18n();
  const { confirm } = useConfirm();
  const toast = useToastStore();

  const isSendingResetLink = ref(false);
  async function handleSendResetLink(subscriber) {
    const confirmed = await confirm({
      title: t("users_page.send_reset_link_title"),
      message: t("subscribers_page.send_reset_link_message", { email: subscriber.email }),
      confirmLabel: t("users_page.send_action"),
    });
    if (!confirmed) return;
    isSendingResetLink.value = true;
    try {
      await userService.sendPasswordResetLink(subscriber.id);
      toast.show({ type: "success", title: t("users_page.sent_toast_title"), message: t("subscribers_page.reset_link_sent_message", { email: subscriber.email }) });
    } catch (err) {
      toast.show({ type: "danger", title: t("subscribers_page.send_failed_title"), message: normalizeApiError(err, "").message });
    } finally {
      isSendingResetLink.value = false;
    }
  }

  const setPasswordModal = ref(null);
  const setPasswordForm = reactive({ password: "", password_confirmation: "" });
  const showSetPassword = ref(true);
  const isSettingPassword = ref(false);
  const setPasswordError = ref(null);

  function generateSetPassword() {
    const upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    const lower = "abcdefghijkmnpqrstuvwxyz";
    const digits = "23456789";
    const symbols = "!@#$%&*";
    const all = upper + lower + digits + symbols;
    let pwd = upper[Math.floor(Math.random() * upper.length)] + lower[Math.floor(Math.random() * lower.length)] + digits[Math.floor(Math.random() * digits.length)] + symbols[Math.floor(Math.random() * symbols.length)];
    for (let i = 0; i < 8; i++) pwd += all[Math.floor(Math.random() * all.length)];
    pwd = pwd.split("").sort(() => Math.random() - 0.5).join("");
    setPasswordForm.password = pwd;
    setPasswordForm.password_confirmation = pwd;
  }
  function openSetPassword(subscriber) {
    setPasswordModal.value = subscriber;
    setPasswordError.value = null;
    showSetPassword.value = true;
    generateSetPassword();
  }
  async function copySetPassword() {
    try {
      await navigator.clipboard.writeText(setPasswordForm.password);
      toast.show({ type: "success", title: t("users_page.copied_toast_title"), message: t("subscribers_page.password_copied_short") });
    } catch {
      // فشل النسخ (صلاحيات المتصفح) — تجاهل بصمت.
    }
  }
  async function handleSetPassword() {
    isSettingPassword.value = true;
    setPasswordError.value = null;
    try {
      await userService.setPassword(setPasswordModal.value.id, setPasswordForm.password, setPasswordForm.password_confirmation);
      toast.show({
        type: "success",
        title: t("users_page.set_action"),
        message: t("subscribers_page.password_set_message"),
      });
      setPasswordModal.value = null;
    } catch (err) {
      const normalized = normalizeApiError(err, t("subscribers_page.could_not_set_password"));
      setPasswordError.value = normalized.fieldError("password") ?? normalized.message;
    } finally {
      isSettingPassword.value = false;
    }
  }

  return {
    isSendingResetLink,
    handleSendResetLink,
    setPasswordModal,
    setPasswordForm,
    showSetPassword,
    isSettingPassword,
    setPasswordError,
    generateSetPassword,
    openSetPassword,
    copySetPassword,
    handleSetPassword,
  };
}
