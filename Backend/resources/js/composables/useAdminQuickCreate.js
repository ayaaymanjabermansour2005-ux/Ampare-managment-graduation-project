import { ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import adminOpsService from "@/services/adminOpsService";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { useOwnerOptions } from "@/composables/useOwnerOptions";

/**
 * Owner/subscriber/technician quick-create and backup-run orchestration for
 * the admin dashboard's "Quick Admin Actions" panel — extracted out of
 * DashboardView.vue (FE-01) so this API/state logic is reusable and
 * testable independent of the view.
 *
 * @param {{ onSubscriberCreated?: () => void|Promise<void> }} [options]
 *   onSubscriberCreated lets the caller refresh dashboard data after a
 *   subscriber is added, mirroring the view's previous `await loadAll()`.
 */
export function useAdminQuickCreate(options = {}) {
  const { t } = useI18n();
  const { confirm } = useConfirm();
  const toast = useToastStore();

  /* ---------------- مالك مولد جديد ---------------- */
  const isOwnerModalOpen = ref(false);
  const isSavingOwner = ref(false);
  const ownerError = ref(null);
  const ownerForm = ref(emptyOwnerForm());

  function emptyOwnerForm() {
    return { name: "", email: "", phone: "", password: "", password_confirmation: "" };
  }

  function openOwnerModal() {
    ownerForm.value = emptyOwnerForm();
    ownerError.value = null;
    isOwnerModalOpen.value = true;
  }

  async function handleCreateOwner() {
    isSavingOwner.value = true;
    ownerError.value = null;
    try {
      await userService.createGeneratorOwner(ownerForm.value);
      isOwnerModalOpen.value = false;
      toast.show({ type: "success", title: t("dashboard.owner_created", { name: ownerForm.value.name }) });
    } catch (err) {
      ownerError.value = {
        message: err.response?.data?.message ?? t("dashboard.owner_create_error"),
        errors: err.response?.data?.errors ?? null,
      };
    } finally {
      isSavingOwner.value = false;
    }
  }

  /* ---------------- مشترك جديد (زر الهيرو) ----------------
   * بتستخدم نفس endpoint/DTO الأساسي لإنشاء مشترك (AdminCreateSubscriberAction
   * عبر userService.createSubscriber) — مو مسار منفصل. القدرة المطلوبة وربط
   * المولد بيصيرو لاحقاً من شاشة الاشتراكات العادية، مو من هالفورم السريع.
   */
  const isNewSubscriberModalOpen = ref(false);
  const isSavingSubscriber = ref(false);
  const subscriberError = ref(null);
  const subscriberForm = ref(emptySubscriberForm());

  function emptySubscriberForm() {
    return { name: "", email: "", phone: "", password: "", password_confirmation: "", address: "" };
  }

  async function openNewSubscriberModal() {
    subscriberForm.value = emptySubscriberForm();
    subscriberError.value = null;
    isNewSubscriberModalOpen.value = true;
  }

  async function handleCreateSubscriber() {
    isSavingSubscriber.value = true;
    subscriberError.value = null;
    try {
      await userService.createSubscriber({
        name: subscriberForm.value.name,
        email: subscriberForm.value.email,
        phone: subscriberForm.value.phone,
        password: subscriberForm.value.password,
        password_confirmation: subscriberForm.value.password_confirmation,
        address: subscriberForm.value.address || null,
      });
      isNewSubscriberModalOpen.value = false;
      await options.onSubscriberCreated?.();
      toast.show({ type: "success", title: t("dashboard.subscriber_created", { name: subscriberForm.value.name }) });
    } catch (err) {
      subscriberError.value = {
        message: err.response?.data?.message ?? t("dashboard.subscriber_create_error"),
        errors: err.response?.data?.errors ?? null,
      };
    } finally {
      isSavingSubscriber.value = false;
    }
  }

  /* ---------------- فني جديد سريع (نافذة أدوات سريعة) ----------------
   * إنشاء فني خاص نيابةً عن مالك مولد يختاره الأدمن (نفس مسار AdminCreateTechnicianForOwnerAction
   * المستخدم في شاشة المستخدمين) — وليس فنيًا بلا مالك.
   */
  const isTechnicianModalOpen = ref(false);
  const isSavingTechnician = ref(false);
  const technicianError = ref(null);
  const technicianForm = ref(emptyTechnicianForm());
  const { owners, isLoadingOwners, ownerSelectOptions, fetchOwners } = useOwnerOptions();

  function emptyTechnicianForm() {
    return { owner_id: "", name: "", email: "", password: "", password_confirmation: "", notes: "" };
  }

  function openTechnicianModal() {
    technicianForm.value = emptyTechnicianForm();
    technicianError.value = null;
    isTechnicianModalOpen.value = true;
    if (owners.value.length === 0) fetchOwners();
  }

  async function handleCreateTechnician() {
    if (!technicianForm.value.owner_id || !technicianForm.value.name) return;
    isSavingTechnician.value = true;
    technicianError.value = null;
    try {
      await userService.createTechnician({ ...technicianForm.value });
      isTechnicianModalOpen.value = false;
      toast.show({ type: "success", title: t("dashboard.technician_created", { name: technicianForm.value.name }) });
    } catch (err) {
      technicianError.value = {
        message: err.response?.data?.message ?? t("dashboard.technician_create_error"),
        errors: err.response?.data?.errors ?? null,
      };
    } finally {
      isSavingTechnician.value = false;
    }
  }

  /* ---------------- النسخة الاحتياطية ---------------- */
  const isRunningBackup = ref(false);

  async function handleRunBackup() {
    const confirmed = await confirm({
      title: t("dashboard.backup_confirm_title"),
      message: t("dashboard.backup_confirm_message"),
      confirmLabel: t("dashboard.backup_confirm_start"),
    });
    if (!confirmed) return;

    isRunningBackup.value = true;
    try {
      await adminOpsService.runBackup();
      toast.show({ type: "success", title: t("dashboard.backup_started") });
    } catch (err) {
      toast.show({ type: "danger", title: err.response?.data?.message ?? t("dashboard.backup_start_error") });
    } finally {
      isRunningBackup.value = false;
    }
  }

  return {
    isOwnerModalOpen,
    isSavingOwner,
    ownerError,
    ownerForm,
    openOwnerModal,
    handleCreateOwner,

    isNewSubscriberModalOpen,
    isSavingSubscriber,
    subscriberError,
    subscriberForm,
    openNewSubscriberModal,
    handleCreateSubscriber,

    isTechnicianModalOpen,
    isSavingTechnician,
    technicianError,
    technicianForm,
    technicianOwnerOptions: ownerSelectOptions,
    isLoadingTechnicianOwners: isLoadingOwners,
    openTechnicianModal,
    handleCreateTechnician,

    isRunningBackup,
    handleRunBackup,
  };
}
