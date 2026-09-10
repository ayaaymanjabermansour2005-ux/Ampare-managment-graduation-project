import { ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import adminOpsService from "@/services/adminOpsService";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { useOwnerOptions } from "@/composables/useOwnerOptions";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * يدير نوافذ ومنطق "الإجراءات الإدارية السريعة" بلوحة تحكم الأدمن: إنشاء
 * مالك مولد/مشترك/فني سريعًا، وتشغيل نسخة احتياطية — دون مغادرة الصفحة.
 *
 * @param {{ onSubscriberCreated?: () => void|Promise<void> }} [options]
 *   onSubscriberCreated لتحديث بيانات لوحة التحكم بعد إضافة مشترك جديد.
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

  /** يرجع فورم فارغ لإنشاء مالك مولد جديد. */
  function emptyOwnerForm() {
    return { name: "", email: "", phone: "", password: "", password_confirmation: "" };
  }

  /** يفتح نافذة إنشاء مالك مولد بفورم فارغ. */
  function openOwnerModal() {
    ownerForm.value = emptyOwnerForm();
    ownerError.value = null;
    isOwnerModalOpen.value = true;
  }

  /** يرسل فورم إنشاء مالك المولد الجديد للباك اند، ويغلق النافذة عند النجاح. */
  async function handleCreateOwner() {
    isSavingOwner.value = true;
    ownerError.value = null;
    try {
      await userService.createGeneratorOwner(ownerForm.value);
      isOwnerModalOpen.value = false;
      toast.show({ type: "success", title: t("dashboard.owner_created", { name: ownerForm.value.name }) });
    } catch (err) {
      const normalized = normalizeApiError(err, t("dashboard.owner_create_error"));
      ownerError.value = { message: normalized.message, errors: normalized.fieldErrors };
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

  /** يرجع فورم فارغ لإنشاء مشترك جديد. */
  function emptySubscriberForm() {
    return { name: "", email: "", phone: "", password: "", password_confirmation: "", address: "" };
  }

  /** يفتح نافذة إنشاء مشترك بفورم فارغ. */
  async function openNewSubscriberModal() {
    subscriberForm.value = emptySubscriberForm();
    subscriberError.value = null;
    isNewSubscriberModalOpen.value = true;
  }

  /** يرسل فورم إنشاء المشترك الجديد للباك اند، يحدّث بيانات لوحة التحكم عبر onSubscriberCreated عند النجاح. */
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
      const normalized = normalizeApiError(err, t("dashboard.subscriber_create_error"));
      subscriberError.value = { message: normalized.message, errors: normalized.fieldErrors };
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

  /** يرجع فورم فارغ لإنشاء فني جديد تابع لمالك مولد. */
  function emptyTechnicianForm() {
    return { owner_id: "", name: "", email: "", password: "", password_confirmation: "", notes: "" };
  }

  /** يفتح نافذة إنشاء فني بفورم فارغ، ويحمّل قائمة الملّاك إذا لم تكن محمَّلة أصلًا. */
  function openTechnicianModal() {
    technicianForm.value = emptyTechnicianForm();
    technicianError.value = null;
    isTechnicianModalOpen.value = true;
    if (owners.value.length === 0) fetchOwners();
  }

  /** يرسل فورم إنشاء الفني الجديد للباك اند، بعد التأكد من اختيار مالك واسم. */
  async function handleCreateTechnician() {
    if (!technicianForm.value.owner_id || !technicianForm.value.name) return;
    isSavingTechnician.value = true;
    technicianError.value = null;
    try {
      await userService.createTechnician({ ...technicianForm.value });
      isTechnicianModalOpen.value = false;
      toast.show({ type: "success", title: t("dashboard.technician_created", { name: technicianForm.value.name }) });
    } catch (err) {
      const normalized = normalizeApiError(err, t("dashboard.technician_create_error"));
      technicianError.value = { message: normalized.message, errors: normalized.fieldErrors };
    } finally {
      isSavingTechnician.value = false;
    }
  }

  /* ---------------- النسخة الاحتياطية ---------------- */
  const isRunningBackup = ref(false);

  /** يطلب تأكيد الأدمن ثم يشغّل نسخة احتياطية فورية للنظام. */
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
      toast.show({ type: "danger", title: normalizeApiError(err, t("dashboard.backup_start_error")).message });
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
