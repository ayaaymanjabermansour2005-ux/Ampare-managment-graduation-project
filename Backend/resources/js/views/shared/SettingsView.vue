<script setup>
import { ref, computed, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { useAccountSettings } from "@/composables/useAccountSettings";
import { useNotificationSettings, NOTIFICATION_ICONS } from "@/composables/useNotificationSettings";
import { usePaymentMethods } from "@/composables/usePaymentMethods";
import { useConfirm } from "@/composables/useConfirm";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Camera, ChevronLeft, ChevronRight, CircleAlert, CircleCheck, Info, Landmark, LoaderCircle, Lock, Monitor, Plus, Save, TriangleAlert, UserCog, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();
const { confirm } = useConfirm();

const TABS = computed(() => {
  const tabs = [
    { key: "profile", label: t("owner_settings.tabs.profile"), icon: "fa-user" },
    { key: "security", label: t("owner_settings.tabs.security"), icon: "fa-shield-halved" },
  ];
  if (authStore.hasRole("generator_owner") || authStore.hasRole("technician")) {
    tabs.push({ key: "financial", label: t("owner_settings.tabs.financial"), icon: "fa-building-columns" });
  }
  tabs.push(
    { key: "notifications", label: t("owner_settings.tabs.notifications"), icon: "fa-bell" },
    { key: "danger", label: t("owner_settings.tabs.danger"), icon: "fa-triangle-exclamation" }
  );
  return tabs;
});
const activeTab = ref("profile");

const {
  isSavingProfile, profileError, profileSuccess, updateProfile,
  isUploadingAvatar, avatarError, uploadAvatar,
  isChangingPassword, passwordError, passwordSuccess, changePassword,
  sessions, isLoadingSessions, sessionsError, revokingSessionId, fetchSessions, revokeSession,
  isLoggingOutOthers, logoutOthersError, logoutOtherDevices,
  loginLog, isLoadingLoginLog, loginLogError, fetchLoginLog,
  isDeletingAccount, deleteAccountError, deleteAccount,
} = useAccountSettings();

const {
  preferences, isLoading: isLoadingPrefs, isSaving: isSavingPrefs, error: prefsError,
  fetchPreferences, savePreferences, notificationKeys,
} = useNotificationSettings();

const {
  methods: paymentMethods, isLoading: isLoadingMethods, error: methodsError, fetchMethods,
  isSaving: isSavingMethod, saveError: methodSaveError, createMethod, updateMethod,
  isDeleting: isDeletingMethod, deleteError: methodDeleteError, deletingId: deletingMethodId, deleteMethod,
} = usePaymentMethods();

/* ---------------- الملف الشخصي ---------------- */
const profileForm = reactive({
  name: "", email: "", phone: "", birth_date: "", address: "", bio: "",
  whatsapp: "", facebook_url: "", instagram_url: "",
});

function loadProfileForm() {
  const u = authStore.user;
  profileForm.name = u?.name ?? "";
  profileForm.email = u?.email ?? "";
  profileForm.phone = u?.phone ?? "";
  profileForm.birth_date = u?.birth_date ?? "";
  profileForm.address = u?.address ?? "";
  profileForm.bio = u?.bio ?? "";
  profileForm.whatsapp = u?.whatsapp ?? "";
  profileForm.facebook_url = u?.facebook_url ?? "";
  profileForm.instagram_url = u?.instagram_url ?? "";
}

async function handleProfileSubmit() {
  await updateProfile({ ...profileForm });
}

const avatarInput = ref(null);
function triggerAvatarPicker() {
  avatarInput.value?.click();
}
async function handleAvatarChange(e) {
  const file = e.target.files?.[0];
  if (file) await uploadAvatar(file);
  e.target.value = "";
}

/* ---------------- الأمان ---------------- */
const passwordForm = reactive({ current_password: "", password: "", password_confirmation: "" });
// FIX: ما كان في تحقّق محلي من تطابق كلمتي المرور — نفس النمط المُطبَّق
// بفورمات التسجيل الثلاثة (Register/ResetPassword/OwnerApplication).
const passwordMismatch = computed(
  () => passwordForm.password_confirmation.length > 0 && passwordForm.password !== passwordForm.password_confirmation,
);
async function handlePasswordSubmit() {
  if (passwordMismatch.value) return;
  const ok = await changePassword({ ...passwordForm });
  if (ok) {
    passwordForm.current_password = "";
    passwordForm.password = "";
    passwordForm.password_confirmation = "";
  }
}

const logoutOthersPassword = ref("");
const showLogoutOthersConfirm = ref(false);
async function confirmLogoutOthers() {
  const ok = await logoutOtherDevices(logoutOthersPassword.value);
  if (ok) {
    logoutOthersPassword.value = "";
    showLogoutOthersConfirm.value = false;
  }
}

function formatDateTime(str) {
  if (!str) return "—";
  return new Date(str.replace(" ", "T")).toLocaleString(t("owner_settings.locale_code"), {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

/* ---------------- وضع الهدوء ---------------- */
const dndForm = reactive({
  dnd_enabled: false,
  dnd_days: [0, 1, 2, 3, 4, 5, 6],
  dnd_start_time: "22:00",
  dnd_end_time: "07:00",
});

function syncDndFormFromPreferences() {
  dndForm.dnd_enabled = !!preferences.value.dnd_enabled;
  dndForm.dnd_days = (preferences.value.dnd_days ?? "0,1,2,3,4,5,6")
    .split(",").filter(Boolean).map(Number);
  dndForm.dnd_start_time = preferences.value.dnd_start_time ?? "22:00";
  dndForm.dnd_end_time = preferences.value.dnd_end_time ?? "07:00";
}

function toggleDndDay(day) {
  const idx = dndForm.dnd_days.indexOf(day);
  if (idx === -1) dndForm.dnd_days.push(day);
  else dndForm.dnd_days.splice(idx, 1);
}

async function saveDndSettings() {
  await savePreferences({
    dnd_enabled: dndForm.dnd_enabled,
    dnd_days: dndForm.dnd_days.slice().sort().join(","),
    dnd_start_time: dndForm.dnd_start_time,
    dnd_end_time: dndForm.dnd_end_time,
  });
}

async function toggleNotificationType(key, currentValue) {
  await savePreferences({ [key]: !currentValue });
}

const DAY_LABELS = computed(() => t("owner_settings.notifications.day_labels").split(","));

const notificationTypesList = computed(() =>
  notificationKeys().map((key) => ({
    key,
    label: t(`owner_settings.notifications.types.${key}`, key),
    icon: NOTIFICATION_ICONS[key] ?? "fa-bell",
    enabled: !!preferences.value[key],
  }))
);

/* ---------------- الإعدادات المالية (وسائل الدفع) ---------------- */
const isPaymentMethodModalOpen = ref(false);
const editingPaymentMethodId = ref(null);
const isEditingPaymentMethod = computed(() => !!editingPaymentMethodId.value);

const paymentMethodForm = reactive({
  type: "bank",
  currency: "ILS",
  bank_name: "",
  account_name: "",
  account_number: "",
  is_default: false,
});

const PAYMENT_METHOD_TYPE_OPTIONS = computed(() => [
  { value: "bank", label: t("owner_settings.financial.type_bank") },
  { value: "wallet", label: t("owner_settings.financial.type_wallet") },
  { value: "cash", label: t("owner_settings.financial.type_cash") },
]);
const PAYMENT_METHOD_CURRENCY_OPTIONS = computed(() => [
  { value: "ILS", label: t("owner_settings.financial.currency_ils") },
  { value: "USD", label: t("owner_settings.financial.currency_usd") },
]);

function resetPaymentMethodForm() {
  paymentMethodForm.type = "bank";
  paymentMethodForm.currency = "ILS";
  paymentMethodForm.bank_name = "";
  paymentMethodForm.account_name = "";
  paymentMethodForm.account_number = "";
  paymentMethodForm.is_default = false;
}

function openAddPaymentMethod() {
  editingPaymentMethodId.value = null;
  resetPaymentMethodForm();
  methodSaveError.value = null;
  isPaymentMethodModalOpen.value = true;
}

function openEditPaymentMethod(method) {
  editingPaymentMethodId.value = method.id;
  paymentMethodForm.type = method.type;
  paymentMethodForm.currency = method.currency ?? "ILS";
  paymentMethodForm.bank_name = method.bank_name ?? "";
  paymentMethodForm.account_name = method.account_name ?? "";
  paymentMethodForm.account_number = "";
  paymentMethodForm.is_default = !!method.is_default;
  methodSaveError.value = null;
  isPaymentMethodModalOpen.value = true;
}

function closePaymentMethodModal() {
  isPaymentMethodModalOpen.value = false;
}

const canSubmitPaymentMethod = computed(() => {
  if (!paymentMethodForm.type) return false;
  if (paymentMethodForm.type === "cash") return true;
  if (!paymentMethodForm.currency || !paymentMethodForm.bank_name.trim() || !paymentMethodForm.account_name.trim()) return false;
  if (!isEditingPaymentMethod.value && !paymentMethodForm.account_number.trim()) return false;
  return true;
});

function paymentMethodIcon(type) {
  if (type === "bank") return "fa-building-columns";
  if (type === "wallet") return "fa-wallet";
  return "fa-money-bill-wave";
}

async function submitPaymentMethod() {
  const payload = { type: paymentMethodForm.type, is_default: paymentMethodForm.is_default };
  if (paymentMethodForm.type === "cash") {
    payload.currency = null;
    payload.bank_name = null;
    payload.account_name = null;
    payload.account_number = null;
  } else {
    payload.currency = paymentMethodForm.currency;
    payload.bank_name = paymentMethodForm.bank_name;
    payload.account_name = paymentMethodForm.account_name;
    if (paymentMethodForm.account_number.trim()) {
      payload.account_number = paymentMethodForm.account_number;
    }
  }

  const ok = isEditingPaymentMethod.value
    ? await updateMethod(editingPaymentMethodId.value, payload)
    : await createMethod(payload);

  if (ok) {
    isPaymentMethodModalOpen.value = false;
    await fetchMethods();
  }
}

async function handleDeletePaymentMethod(method) {
  const confirmed = await confirm({
    title: t("owner_settings.financial.delete_confirm_title"),
    message: t("owner_settings.financial.delete_confirm_message"),
    confirmLabel: t("owner_settings.financial.delete_button"),
    variant: "danger",
  });
  if (!confirmed) return;
  const ok = await deleteMethod(method.id);
  if (ok) await fetchMethods();
}

/* ---------------- الإجراءات الحساسة ---------------- */
const deletePassword = ref("");
const showDeleteConfirm = ref(false);
const deleteConfirmText = ref("");
const deleteConfirmPhrase = computed(() => t("owner_settings.danger.delete_confirm_phrase"));

async function confirmDeleteAccount() {
  const ok = await deleteAccount(deletePassword.value);
  if (ok) {
    router.push({ name: "login" });
  }
}

/* ---------------- تحميل البيانات حسب التبويب ---------------- */
function onTabChange(tab) {
  activeTab.value = tab;
  if (tab === "security" && sessions.value.length === 0) {
    fetchSessions();
    fetchLoginLog();
  }
  if (tab === "notifications" && Object.keys(preferences.value).length === 0) {
    fetchPreferences().then(syncDndFormFromPreferences);
  }
  if (tab === "financial" && paymentMethods.value.length === 0) {
    fetchMethods();
  }
}

onMounted(() => {
  loadProfileForm();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ t("common.home") }}</span>
        <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_settings.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
              <UserCog aria-hidden="true" />
            </span>
            {{ t("owner_settings.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ t("owner_settings.subtitle") }}
          </p>
        </div>

        <!-- ===== تبديل التابات ===== -->
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            @click="onTabChange(tab.key)"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
            :class="
              activeTab === tab.key
                ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
            "
          >
            <AppIcon :name="tab.icon" />
            {{ tab.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- ==================== الملف الشخصي ==================== -->
    <div v-if="activeTab === 'profile'" class="space-y-6">
      <section v-reveal class="glass-card p-6 flex items-center gap-5">
        <div class="relative">
          <div class="w-20 h-20 rounded-full overflow-hidden bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center border-2 border-[#D4AF37]/50">
            <img v-if="authStore.user?.avatar_url" :src="authStore.user.avatar_url" :alt="t('owner_settings.profile.avatar_alt')" class="w-full h-full object-cover" />
            <span v-else class="text-[#3E582E] dark:text-[#a8d19a] font-bold text-2xl">{{ authStore.user?.name?.charAt(0) ?? "?" }}</span>
          </div>
          <button :aria-label="t('common.upload_avatar')"
            type="button"
            @click="triggerAvatarPicker"
            :disabled="isUploadingAvatar"
            class="absolute -bottom-1 -end-1 w-7 h-7 rounded-full bg-gradient-to-l from-[#3E582E] to-[#8A6D1F] text-white flex items-center justify-center shadow-md hover:scale-105 transition disabled:opacity-50"
          >
            <LoaderCircle class="text-[11px] animate-spin" aria-hidden="true" v-if="isUploadingAvatar" /><Camera class="text-[11px]" aria-hidden="true" v-else />
          </button>
          <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
        </div>
        <div>
          <p class="font-bold text-[13px]">{{ authStore.user?.name }}</p>
          <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ authStore.user?.email }}</p>
          <p v-if="avatarError" class="text-[11px] text-[#D9534F] mt-1">{{ avatarError }}</p>
        </div>
      </section>

      <form @submit.prevent="handleProfileSubmit" v-reveal class="glass-card p-6 space-y-4">
        <h2 class="text-[13.5px] font-bold mb-1">{{ t("owner_settings.profile.personal_info") }}</h2>

        <div v-if="profileSuccess" class="text-[12.5px] text-[#28A745] bg-[#28A745]/10 border border-[#28A745]/30 rounded-lg p-3">
          <CircleCheck class="me-1.5" aria-hidden="true" />{{ t("owner_settings.profile.save_success") }}
        </div>
        <div v-if="profileError?.message" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3">
          <CircleAlert class="me-1.5" aria-hidden="true" />{{ profileError.message }}
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.profile.full_name") }}</label>
            <input v-model="profileForm.name" type="text" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.profile.email") }}</label>
            <input v-model="profileForm.email" type="email" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
            <p v-if="profileError?.errors?.email" class="text-[11px] text-[#D9534F] mt-1">{{ profileError.errors.email[0] }}</p>
            <p v-else-if="profileForm.email !== authStore.user?.email" class="text-[11px] text-[#8A6D1F] mt-1">
              <Info aria-hidden="true" /> {{ t("owner_settings.profile.email_change_notice") }}
            </p>
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.profile.phone") }}</label>
            <input v-model="profileForm.phone" type="tel" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.profile.birth_date") }}</label>
            <input v-model="profileForm.birth_date" type="date" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.profile.address") }}</label>
            <input v-model="profileForm.address" type="text" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
        </div>

        <div>
          <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.profile.bio") }}</label>
          <textarea v-model="profileForm.bio" rows="3" maxlength="1000" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] resize-none outline-none focus:border-[#8A6D1F] transition"></textarea>
        </div>

        <h3 class="text-[11.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] pt-3 border-t border-[#f0ece0] dark:border-white/5">{{ t("owner_settings.profile.social_links") }}</h3>
        <div class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="text-[11.5px] block mb-1.5"><i class="fa-brands fa-whatsapp text-[#28A745] me-1"></i> {{ t("owner_settings.profile.whatsapp") }}</label>
            <input v-model="profileForm.whatsapp" type="tel" placeholder="+970..." class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] block mb-1.5"><i class="fa-brands fa-facebook text-[#17A2B8] me-1"></i> {{ t("owner_settings.profile.facebook") }}</label>
            <input v-model="profileForm.facebook_url" type="url" placeholder="https://facebook.com/..." class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] block mb-1.5"><i class="fa-brands fa-instagram text-[#8A6D1F] me-1"></i> {{ t("owner_settings.profile.instagram") }}</label>
            <input v-model="profileForm.instagram_url" type="url" placeholder="https://instagram.com/..." class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isSavingProfile"
          class="btn-fill relative px-6 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-60 transition w-fit"
        >
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingProfile" /><Save aria-hidden="true" v-else />
          {{ isSavingProfile ? t("owner_settings.profile.saving") : t("owner_settings.profile.save") }}
        </button>
      </form>
    </div>

    <!-- ==================== الأمان ==================== -->
    <div v-else-if="activeTab === 'security'" class="space-y-6">
      <form @submit.prevent="handlePasswordSubmit" v-reveal class="glass-card p-6 space-y-4">
        <h2 class="text-[13.5px] font-bold mb-1">{{ t("owner_settings.security.change_password") }}</h2>
        <div v-if="passwordSuccess" class="text-[12.5px] text-[#28A745] bg-[#28A745]/10 border border-[#28A745]/30 rounded-lg p-3">
          <CircleCheck class="me-1.5" aria-hidden="true" />{{ t("owner_settings.security.password_changed") }}
        </div>
        <div v-if="passwordError?.message" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3">
          <CircleAlert class="me-1.5" aria-hidden="true" />{{ passwordError.message }}
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.security.current_password") }}</label>
            <input v-model="passwordForm.current_password" type="password" required class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.security.new_password") }}</label>
            <input v-model="passwordForm.password" type="password" required minlength="10" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition" />
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.security.confirm_password") }}</label>
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              required
              class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition"
              :class="{ '!border-[#D9534F]': passwordMismatch }"
            />
            <p v-if="passwordMismatch" class="text-[11px] text-[#D9534F] mt-1">{{ t("auth.password_mismatch") }}</p>
          </div>
        </div>

        <button
          type="submit"
          :disabled="isChangingPassword || passwordMismatch"
          class="btn-fill relative px-6 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-60 transition w-fit"
        >
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isChangingPassword" /><Lock aria-hidden="true" v-else />
          {{ isChangingPassword ? t("owner_settings.security.changing") : t("owner_settings.security.change_password") }}
        </button>
      </form>

      <section v-reveal class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[13.5px] font-bold">{{ t("owner_settings.security.connected_devices") }}</h2>
          <button
            type="button"
            @click="showLogoutOthersConfirm = true"
            class="text-[11.5px] font-bold text-[#D9534F] hover:underline"
          >
            {{ t("owner_settings.security.logout_others") }}
          </button>
        </div>

        <div v-if="showLogoutOthersConfirm" class="bg-[#D4AF37]/10 border border-[#D4AF37]/30 rounded-lg p-4 mb-4 space-y-3">
          <p class="text-[11.5px]">{{ t("owner_settings.security.logout_others_confirm_notice") }}</p>
          <div v-if="logoutOthersError" class="text-[11px] text-[#D9534F]">{{ logoutOthersError }}</div>
          <div class="flex gap-2">
            <input v-model="logoutOthersPassword" type="password" :placeholder="t('owner_settings.security.password_placeholder')" class="flex-1 bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
            <button type="button" @click="confirmLogoutOthers" :disabled="isLoggingOutOthers" class="px-4 py-2 rounded-full text-[11.5px] font-bold text-white bg-[#D9534F] hover:bg-[#D9534F]/90 disabled:opacity-50 transition">
              {{ isLoggingOutOthers ? "..." : t("owner_settings.security.confirm") }}
            </button>
            <button type="button" @click="showLogoutOthersConfirm = false" class="px-4 py-2 rounded-full text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] border border-[#e7e2d6] dark:border-white/10">{{ t("owner_settings.security.cancel") }}</button>
          </div>
        </div>

        <div v-if="isLoadingSessions" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("common.loading") }}</div>
        <div v-else-if="sessionsError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3 text-center">
          <CircleAlert class="me-1.5" aria-hidden="true" />{{ sessionsError }}
          <button type="button" @click="fetchSessions" class="block mx-auto mt-2 text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_settings.security.retry") }}</button>
        </div>
        <div v-else-if="sessions.length === 0" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owner_settings.security.no_sessions") }}</div>
        <div v-else class="space-y-2">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="flex items-center justify-between p-3 rounded-xl border border-[#e7e2d6] dark:border-white/10"
            :class="session.is_current ? 'bg-[#EBF1E7]/60 dark:bg-white/5' : ''"
          >
            <div class="flex items-center gap-3 min-w-0">
              <Monitor class="text-[#9a9d97] dark:text-[#8f938a]" aria-hidden="true" />
              <div class="min-w-0">
                <p class="text-[12.5px] truncate">
                  {{ session.ip_address ?? t("owner_settings.security.unknown_ip") }}
                  <span v-if="session.is_current" class="status-chip chip-success ms-1">{{ t("owner_settings.security.this_device") }}</span>
                </p>
                <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] truncate">{{ session.user_agent ?? "—" }} · {{ t("owner_settings.security.last_active") }}: {{ formatDateTime(session.last_activity) }}</p>
              </div>
            </div>
            <button
              v-if="!session.is_current"
              type="button"
              @click="revokeSession(session.id)"
              :disabled="revokingSessionId === session.id"
              class="text-[11.5px] font-bold text-[#D9534F] hover:underline shrink-0 disabled:opacity-50"
            >
              {{ revokingSessionId === session.id ? "..." : t("owner_settings.security.end_session") }}
            </button>
          </div>
        </div>
      </section>

      <section v-reveal class="glass-card p-6">
        <h2 class="text-[13.5px] font-bold mb-4">{{ t("owner_settings.security.login_log") }}</h2>
        <div v-if="isLoadingLoginLog" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("common.loading") }}</div>
        <div v-else-if="loginLogError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3 text-center">
          <CircleAlert class="me-1.5" aria-hidden="true" />{{ loginLogError }}
          <button type="button" @click="fetchLoginLog" class="block mx-auto mt-2 text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_settings.security.retry") }}</button>
        </div>
        <div v-else-if="loginLog.length === 0" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owner_settings.security.no_login_log") }}</div>
        <table v-else class="w-full text-[12.5px]">
          <thead>
            <tr class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] border-b border-[#f0ece0] dark:border-white/5">
              <th class="text-start py-2 font-bold">{{ t("owner_settings.security.status_col") }}</th>
              <th class="text-start py-2 font-bold">{{ t("owner_settings.security.ip_col") }}</th>
              <th class="text-start py-2 font-bold">{{ t("owner_settings.security.date_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in loginLog" :key="log.id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0">
              <td class="py-2">
                <span class="status-chip" :class="log.event === 'login_succeeded' ? 'chip-success' : 'chip-danger'">
                  {{ log.event === "login_succeeded" ? t("owner_settings.security.login_succeeded") : t("owner_settings.security.login_failed") }}
                </span>
              </td>
              <td class="py-2 text-[#6B6B6B] dark:text-[#a8aaa5] font-mono text-[11.5px]">{{ log.ip ?? "—" }}</td>
              <td class="py-2 text-[#9a9d97] dark:text-[#8f938a] text-[11.5px]">{{ formatDateTime(log.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>

    <!-- ==================== الإعدادات المالية ==================== -->
    <div v-else-if="activeTab === 'financial'" class="space-y-6">
      <section v-reveal class="glass-card p-6">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-4">
          <div>
            <h2 class="text-[13.5px] font-bold mb-1">{{ t("owner_settings.financial.title") }}</h2>
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] max-w-md">
              {{ authStore.hasRole("technician") ? t("owner_settings.financial.subtitle_technician") : t("owner_settings.financial.subtitle_owner") }}
            </p>
          </div>
          <button
            type="button"
            @click="openAddPaymentMethod"
            class="btn-fill relative px-5 py-2.5 rounded-full text-[12px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center gap-2 transition w-fit shrink-0"
          >
            <Plus aria-hidden="true" />
            {{ t("owner_settings.financial.add_button") }}
          </button>
        </div>

        <div v-if="methodDeleteError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3 mb-3">
          <CircleAlert class="me-1.5" aria-hidden="true" />{{ methodDeleteError }}
        </div>

        <div v-if="isLoadingMethods" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("common.loading") }}</div>
        <div v-else-if="methodsError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3 text-center">
          <CircleAlert class="me-1.5" aria-hidden="true" />{{ methodsError }}
          <button type="button" @click="fetchMethods" class="block mx-auto mt-2 text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_settings.security.retry") }}</button>
        </div>
        <div v-else-if="paymentMethods.length === 0" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("owner_settings.financial.empty") }}</div>
        <div v-else class="space-y-2">
          <div
            v-for="method in paymentMethods"
            :key="method.id"
            class="flex items-center justify-between p-3 rounded-xl border border-[#e7e2d6] dark:border-white/10 gap-3"
          >
            <div class="flex items-center gap-3 min-w-0">
              <AppIcon :name="paymentMethodIcon(method.type)" class="text-[#9a9d97] dark:text-[#8f938a]" />
              <div class="min-w-0">
                <p class="text-[12.5px] truncate">
                  {{ t(`owner_settings.financial.type_${method.type}`) }}
                  <span v-if="method.is_default" class="status-chip chip-success ms-1">{{ t("owner_settings.financial.default_badge") }}</span>
                </p>
                <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] truncate">
                  <template v-if="method.type === 'cash'">{{ t("owner_settings.financial.type_cash") }}</template>
                  <template v-else>{{ method.bank_name }} · {{ method.account_name }} · {{ method.account_number_masked }}</template>
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <button type="button" @click="openEditPaymentMethod(method)" class="text-[11.5px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline">
                {{ t("owner_settings.financial.edit_button") }}
              </button>
              <button
                type="button"
                @click="handleDeletePaymentMethod(method)"
                :disabled="isDeletingMethod && deletingMethodId === method.id"
                class="text-[11.5px] font-bold text-[#D9534F] hover:underline disabled:opacity-50"
              >
                {{ isDeletingMethod && deletingMethodId === method.id ? "..." : t("owner_settings.financial.delete_button") }}
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- ==================== الإشعارات ووضع الهدوء ==================== -->
    <div v-else-if="activeTab === 'notifications'" class="space-y-6">
      <div v-if="prefsError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/30 rounded-lg p-3">{{ prefsError }}</div>
      <div v-if="isLoadingPrefs" class="text-center py-8 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("common.loading") }}</div>

      <template v-else>
        <section v-reveal class="glass-card p-6">
          <h2 class="text-[13.5px] font-bold mb-4">{{ t("owner_settings.notifications.types_title") }}</h2>
          <div class="space-y-1">
            <div
              v-for="type in notificationTypesList"
              :key="type.key"
              class="flex items-center justify-between py-3 border-b border-[#f0ece0] dark:border-white/5 last:border-0"
            >
              <span class="text-[12.5px] flex items-center gap-2.5">
                <AppIcon :name="`fa-solid ${type.icon}`" class="text-[#9a9d97] dark:text-[#8f938a] w-4" />
                {{ type.label }}
              </span>
              <button
                type="button"
                @click="toggleNotificationType(type.key, type.enabled)"
                :disabled="isSavingPrefs"
                class="relative w-11 h-6 rounded-full transition-colors shrink-0"
                :class="type.enabled ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D]' : 'bg-[#e7e2d6] dark:bg-white/10'"
              >
                <span
                  class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"
                  :class="type.enabled ? 'translate-x-0.5' : 'translate-x-5'"
                ></span>
              </button>
            </div>
          </div>
        </section>

        <section v-reveal class="glass-card p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-[13.5px] font-bold">{{ t("owner_settings.notifications.dnd_title") }}</h2>
            <button
              type="button"
              @click="dndForm.dnd_enabled = !dndForm.dnd_enabled; saveDndSettings()"
              class="relative w-11 h-6 rounded-full transition-colors shrink-0"
              :class="dndForm.dnd_enabled ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D]' : 'bg-[#e7e2d6] dark:bg-white/10'"
            >
              <span
                class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"
                :class="dndForm.dnd_enabled ? 'translate-x-0.5' : 'translate-x-5'"
              ></span>
            </button>
          </div>

          <div v-if="dndForm.dnd_enabled" class="space-y-4 pt-3 border-t border-[#f0ece0] dark:border-white/5">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.notifications.from_hour") }}</label>
                <input v-model="dndForm.dnd_start_time" type="time" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3 py-2 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
              </div>
              <div>
                <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.notifications.to_hour") }}</label>
                <input v-model="dndForm.dnd_end_time" type="time" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3 py-2 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
              </div>
            </div>

            <div>
              <label class="text-[11.5px] font-bold block mb-2">{{ t("owner_settings.notifications.week_days") }}</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="(day, idx) in DAY_LABELS"
                  :key="idx"
                  type="button"
                  @click="toggleDndDay(idx)"
                  class="w-11 h-9 rounded-xl text-[11.5px] font-bold border transition"
                  :class="
                    dndForm.dnd_days.includes(idx)
                      ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white border-transparent'
                      : 'text-[#6B6B6B] dark:text-[#a8aaa5] border-[#e7e2d6] dark:border-white/10 hover:border-[#8A6D1F]'
                  "
                >
                  {{ day }}
                </button>
              </div>
            </div>

            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#EBF1E7] dark:bg-white/5 rounded-lg p-3">
              <Info class="me-1.5" aria-hidden="true" />
              {{ t("owner_settings.notifications.dnd_critical_notice") }}
            </p>

            <button
              type="button"
              @click="saveDndSettings"
              :disabled="isSavingPrefs"
              class="btn-fill relative px-6 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-60 transition w-fit"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingPrefs" /><Save aria-hidden="true" v-else />
              {{ isSavingPrefs ? t("owner_settings.profile.saving") : t("owner_settings.notifications.save_dnd") }}
            </button>
          </div>
        </section>
      </template>
    </div>

    <!-- ==================== الإجراءات الحساسة ==================== -->
    <div v-else-if="activeTab === 'danger'" class="space-y-6">
      <section v-reveal class="glass-card p-6 border border-[#D9534F]/30">
        <h2 class="text-[13.5px] font-bold text-[#D9534F] mb-2 flex items-center gap-2">
          <TriangleAlert aria-hidden="true" />
          {{ t("owner_settings.danger.delete_account_title") }}
        </h2>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-4">
          {{ t("owner_settings.danger.delete_account_notice") }}
        </p>

        <button
          v-if="!showDeleteConfirm"
          type="button"
          @click="showDeleteConfirm = true"
          class="px-5 py-2.5 rounded-full text-[12.5px] font-bold text-[#D9534F] border border-[#D9534F] hover:bg-[#D9534F] hover:text-white transition"
        >
          {{ t("owner_settings.danger.delete_account_button") }}
        </button>

        <div v-else class="space-y-3 bg-[#D9534F]/5 rounded-xl p-4 border border-[#D9534F]/30">
          <div v-if="deleteAccountError" class="text-[11px] text-[#D9534F]">{{ deleteAccountError }}</div>

          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">{{ t("owner_settings.danger.confirm_password_label") }}</label>
            <input v-model="deletePassword" type="password" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
          </div>
          <div>
            <label class="text-[11.5px] font-bold block mb-1.5">
              {{ t("owner_settings.danger.confirm_phrase_label", { phrase: deleteConfirmPhrase }) }}
            </label>
            <input v-model="deleteConfirmText" type="text" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
          </div>

          <div class="flex gap-2">
            <button
              type="button"
              @click="showDeleteConfirm = false"
              class="flex-1 py-2.5 rounded-full text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] border border-[#e7e2d6] dark:border-white/10"
            >
              {{ t("owner_settings.security.cancel") }}
            </button>
            <button
              type="button"
              @click="confirmDeleteAccount"
              :disabled="isDeletingAccount || deleteConfirmText !== deleteConfirmPhrase || !deletePassword"
              class="flex-1 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-[#D9534F] hover:bg-[#D9534F]/90 disabled:opacity-40 transition"
            >
              {{ isDeletingAccount ? t("owner_settings.danger.deleting") : t("owner_settings.danger.confirm_delete") }}
            </button>
          </div>
        </div>
      </section>
    </div>

    <!-- ===== نافذة إضافة/تعديل وسيلة دفع ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isPaymentMethodModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closePaymentMethodModal">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Landmark aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">
                    {{ isEditingPaymentMethod ? t("owner_settings.financial.edit_modal_title") : t("owner_settings.financial.add_modal_title") }}
                  </h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="closePaymentMethodModal" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="methodSaveError?.message" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ methodSaveError.message }}</div>

              <div>
                <label class="field-label">{{ t("owner_settings.financial.type_label") }}</label>
                <AppDropdownSelect
                  v-model="paymentMethodForm.type"
                  :options="PAYMENT_METHOD_TYPE_OPTIONS"
                  variant="field" width-class="w-full" match-trigger-width
                />
                <p v-if="methodSaveError?.errors?.type" class="text-[11px] text-[#D9534F] mt-1">{{ methodSaveError.errors.type[0] }}</p>
              </div>

              <template v-if="paymentMethodForm.type !== 'cash'">
                <div>
                  <label class="field-label">{{ t("owner_settings.financial.currency_label") }}</label>
                  <AppDropdownSelect
                    v-model="paymentMethodForm.currency"
                    :options="PAYMENT_METHOD_CURRENCY_OPTIONS"
                    variant="field" width-class="w-full" match-trigger-width
                  />
                  <p v-if="methodSaveError?.errors?.currency" class="text-[11px] text-[#D9534F] mt-1">{{ methodSaveError.errors.currency[0] }}</p>
                </div>

                <div>
                  <label class="field-label">{{ t("owner_settings.financial.bank_name_label") }}</label>
                  <input v-model="paymentMethodForm.bank_name" type="text" class="field-input" />
                  <p v-if="methodSaveError?.errors?.bank_name" class="text-[11px] text-[#D9534F] mt-1">{{ methodSaveError.errors.bank_name[0] }}</p>
                </div>

                <div>
                  <label class="field-label">{{ t("owner_settings.financial.account_name_label") }}</label>
                  <input v-model="paymentMethodForm.account_name" type="text" class="field-input" />
                  <p v-if="methodSaveError?.errors?.account_name" class="text-[11px] text-[#D9534F] mt-1">{{ methodSaveError.errors.account_name[0] }}</p>
                </div>

                <div>
                  <label class="field-label">{{ t("owner_settings.financial.account_number_label") }}</label>
                  <input
                    v-model="paymentMethodForm.account_number"
                    type="text"
                    dir="ltr"
                    class="field-input"
                    :placeholder="isEditingPaymentMethod ? t('owner_settings.financial.account_number_keep_placeholder') : ''"
                  />
                  <p v-if="methodSaveError?.errors?.account_number" class="text-[11px] text-[#D9534F] mt-1">{{ methodSaveError.errors.account_number[0] }}</p>
                </div>
              </template>

              <label class="flex items-center gap-2 text-[11.5px] font-bold cursor-pointer">
                <input v-model="paymentMethodForm.is_default" type="checkbox" class="w-4 h-4 accent-[#52733D]" />
                {{ t("owner_settings.financial.is_default_label") }}
              </label>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="closePaymentMethodModal" class="btn-outline-brand">{{ t("owner_settings.security.cancel") }}</button>
              <button
                type="button"
                @click="submitPaymentMethod"
                :disabled="isSavingMethod || !canSubmitPaymentMethod"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingMethod" /><Save aria-hidden="true" v-else />
                {{ isSavingMethod ? t("owner_settings.financial.saving_ellipsis") : (isEditingPaymentMethod ? t("owner_settings.financial.save_button") : t("owner_settings.financial.add_button")) }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>