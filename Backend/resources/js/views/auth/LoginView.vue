<script setup>
import { ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { resolveHomeRouteName } from "@/utils/roleRedirect";
import { consumePendingSubscribeGeneratorId } from "@/utils/pendingSubscribeGenerator";
import authService from "@/services/authService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ArrowLeft, ArrowRight, Check, Eye, EyeOff, LoaderCircle, ShieldUser, Factory, House, Wrench } from "@lucide/vue";


const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();

/* ---------------- أزرار تعبئة سريعة للمشرفين — dev فقط ----------------
 * بيانات ثابتة — أربعة حسابات تجريبية حقيقية موجودة مسبقًا بالقاعدة، وحدة
 * لكل دور. حساب الأدمن الوحيد المسموح به بالنظام (admin@ampare.test) بكلمة
 * مرور DEV_ADMIN_PASSWORD من .env (افتراضيًا Password123! — راجع
 * config/seeding.php وRoleSeeder)، لا علاقة لها بكلمة مرور PlatformUsersSeeder
 * الموحّدة (Ampare@2026) المستخدَمة لبقية الحسابات الثلاثة. الشرط الثلاثي
 * (مش v-if بالتمبلت لحاله) مهم أمنيًا: import.meta.env.DEV يُستبدَل بقيمة
 * ثابتة وقت الـ build، فـ Terser فعليًا بيحذف مصفوفة بيانات الدخول بالكامل
 * من حزمة الإنتاج (مش بس يخفيها بالـ DOM) — تأكّدت فعليًا إنه grep على
 * public/build ما بيلاقي "Ampare@2026" ولا أي بريد تجريبي بعد هالتعديل. لو
 * كان بس v-if على التمبلت، السلسلة كانت تضل موجودة بالحزمة القابلة للتنزيل
 * حتى لو مخفية بصريًا.
 */
const isProductionBuild = import.meta.env.PROD;
const QUICK_LOGIN_ACCOUNTS = import.meta.env.DEV
  ? [
      { key: "admin", icon: ShieldUser, login: "admin@ampare.test", password: "Password123!" },
      { key: "owner", icon: Factory, login: "mahmoud.abushamala@example.test", password: "Ampare@2026" },
      { key: "subscriber", icon: House, login: "ahmad.alhilu@example.test", password: "Ampare@2026" },
      { key: "technician", icon: Wrench, login: "majed.abuamra@example.test", password: "Ampare@2026" },
    ]
  : [];
function quickLogin(account) {
  form.value.login = account.login;
  form.value.password = account.password;
  handleSubmit();
}

const form = ref({ login: "", password: "", remember: false });
const submitError = ref(null);
const showPassword = ref(false);

const submitState = ref("idle");

const statusMessage = computed(() => {
  if (route.query.registered === "1") {
    return { type: "success", text: t("auth.login.status_registered_success") };
  }
  if (route.query.verified === "1") {
    return { type: "success", text: t("auth.login.status_verified_success") };
  }
  if (route.query.verified === "expired") {
    return { type: "error", text: t("auth.login.status_verified_expired") };
  }
  if (route.query.verified === "0") {
    return { type: "error", text: t("auth.login.status_verified_invalid") };
  }
  return null;
});

const isEmailNotVerified = ref(false);
const isLoginValueAnEmail = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.login));
const resendEmail = ref("");
const resendState = ref("idle"); // idle | loading | sent
const resendError = ref(null);

async function handleResendVerification() {
  const email = isLoginValueAnEmail.value ? form.value.login : resendEmail.value.trim();
  if (!email) return;

  resendState.value = "loading";
  resendError.value = null;
  try {
    await authService.resendVerificationEmail(email);
    resendState.value = "sent";
  } catch (error) {
    resendState.value = "idle";
    resendError.value = normalizeApiError(error, t("auth.login.generic_error")).message;
  }
}

async function handleSubmit() {
  submitError.value = null;
  isEmailNotVerified.value = false;
  resendState.value = "idle";
  submitState.value = "loading";
  try {
    await authStore.login(form.value);
    submitState.value = "success";

    setTimeout(() => {
      const redirectTo = route.query.redirect;
      if (redirectTo) {
        router.push(redirectTo);
        return;
      }

      const homeRouteName = resolveHomeRouteName(authStore);

      // مستخدم جاي حديثًا من رابط "اشتراك" بخريطة اللاندنج بيج، وهو
      // مشترك فعليًا → نوجّهه مباشرة لمركز الاشتراكات على نفس المولد
      // (نفس آلية ?highlight= الموجودة أصلًا)، بدل ما يختاره يدويًا مرة ثانية.
      if (homeRouteName === "subscriber.dashboard") {
        const pendingGeneratorId = consumePendingSubscribeGeneratorId();
        if (pendingGeneratorId) {
          router.push({ name: "subscriber.subscription", query: { highlight: pendingGeneratorId } });
          return;
        }
      }

      router.push({ name: homeRouteName });
    }, 500);
  } catch (error) {
    submitState.value = "idle";
    const normalized = normalizeApiError(error, t("auth.login.generic_error"));
    submitError.value = normalized.fieldError("login") ?? normalized.message;

    // ملاحظة: errors.code هنا ليست حقل تحقّق عادي (مصفوفة رسائل) بل قيمة
    // نصية مباشرة (راجع EmailNotVerifiedException) — تُقرأ من fieldErrors
    // خامًا، لا عبر fieldError() اللي بتفترض شكل مصفوفة.
    if (normalized.fieldErrors.code === "EMAIL_NOT_VERIFIED") {
      isEmailNotVerified.value = true;
    }
  }
}
</script>

<template>
  <div>
    <div
      v-if="statusMessage"
      :class="statusMessage.type === 'success' ? 'bg-success-bg text-success' : 'bg-danger-bg text-danger'"
      class="text-[11px] rounded-lg p-2.5 mb-3"
    >
      {{ statusMessage.text }}
    </div>

    <div v-if="submitError" class="bg-danger-bg text-danger text-[11px] rounded-lg p-2.5 mb-3">
      {{ submitError }}
    </div>

    <!-- إعادة إرسال بريد التفعيل — تظهر فقط عند فشل الدخول بسبب بريد غير مفعّل -->
    <div v-if="isEmailNotVerified" class="bg-warning-bg text-warning text-[11px] rounded-lg p-2.5 mb-3 space-y-2">
      <template v-if="resendState === 'sent'">
        <p>{{ t("auth.login.resend_sent") }}</p>
      </template>
      <template v-else>
        <p>{{ t("auth.login.resend_prompt") }}</p>
        <input
          v-if="!isLoginValueAnEmail"
          v-model="resendEmail"
          type="email"
          :placeholder="t('auth.login.resend_email_placeholder')"
          class="auth-glass-input !text-[11px]"
        />
        <p v-if="resendError" class="auth-field-error">{{ resendError }}</p>
        <button
          type="button"
          class="auth-link-gradient font-bold text-[11px]"
          :disabled="resendState === 'loading' || (!isLoginValueAnEmail && !resendEmail.trim())"
          @click="handleResendVerification"
        >
          {{ resendState === "loading" ? t("auth.login.resend_loading") : t("auth.login.resend_button") }}
        </button>
      </template>
    </div>

    <form @submit.prevent="handleSubmit">
      <!-- البريد/الهاتف -->
      <div class="mb-3">
        <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">
          {{ t("auth.login.email_or_phone_label") }}
        </label>
        <input
          v-model="form.login"
          type="text"
          class="auth-glass-input"
          autocomplete="username"
          :placeholder="t('auth.login.email_or_phone_placeholder')"
          required
        />
      </div>

      <!-- كلمة المرور -->
      <div class="mb-3">
        <div class="flex justify-between items-center mb-[3px]">
          <label class="auth-field-label text-[10.5px] font-semibold">{{ t("auth.login.password_label") }}</label>
          <RouterLink :to="{ name: 'forgot-password' }" class="auth-link-gradient text-[10px] font-medium">
            {{ t("auth.login.forgot_password") }}
          </RouterLink>
        </div>
        <div class="relative">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="auth-glass-input auth-input-with-eye"
            :placeholder="t('auth.login.password_placeholder')"
            required
          />
          <span class="auth-password-eye" role="button" tabindex="0" :aria-label="t('auth.toggle_password_visibility')" @click="showPassword = !showPassword" @keydown.enter.prevent="showPassword = !showPassword" @keydown.space.prevent="showPassword = !showPassword">
            <EyeOff aria-hidden="true" v-if="showPassword" class="text-xs" /><Eye aria-hidden="true" v-else class="text-xs" />
          </span>
        </div>
      </div>

      <!-- تذكرني -->
      <div class="flex items-center gap-[5px] my-2.5">
        <input id="remember" v-model="form.remember" type="checkbox" class="w-3.5 h-3.5 accent-primary-500 cursor-pointer" />
        <label for="remember" class="auth-remember-label text-[10px] cursor-pointer">{{ t("auth.login.remember_me") }}</label>
      </div>

      <!-- تعبئة سريعة (dev فقط) -->
      <div v-if="!isProductionBuild" class="mb-3">
        <p class="text-[9.5px] font-bold text-[#9a9d97] mb-1.5 text-center">{{ t("auth.login.quick_login_label") }}</p>
        <div class="grid grid-cols-4 gap-1.5">
          <button
            v-for="account in QUICK_LOGIN_ACCOUNTS"
            :key="account.key"
            type="button"
            :title="t(`auth.login.quick_login_${account.key}`)"
            class="auth-quick-login-btn"
            :disabled="submitState !== 'idle'"
            @click="quickLogin(account)"
          >
            <component :is="account.icon" aria-hidden="true" />
            <span>{{ t(`auth.login.quick_login_${account.key}`) }}</span>
          </button>
        </div>
      </div>

      <!-- زر الدخول -->
      <button
        type="submit"
        class="auth-btn-submit"
        :class="{ 'is-success': submitState === 'success' }"
        :disabled="submitState !== 'idle'"
      >
        <LoaderCircle class="animate-spin" aria-hidden="true" v-if="submitState === 'loading'" />
        <Check aria-hidden="true" v-else-if="submitState === 'success'" />
        <span v-if="submitState === 'idle'">{{ t("auth.login.submit_idle") }}</span>
        <span v-else-if="submitState === 'loading'">{{ t("auth.login.submit_loading") }}</span>
        <span v-else>{{ t("auth.login.submit_success") }}</span>
        <ArrowLeft v-if="submitState === 'idle'" class="rtl:inline-block ltr:hidden" aria-hidden="true" />
        <ArrowRight v-if="submitState === 'idle'" class="ltr:inline-block rtl:hidden" aria-hidden="true" />
      </button>
    </form>

    <!-- فاصل -->
    <div class="text-center my-3 relative">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full auth-divider-line"></div>
      </div>
      <span class="auth-divider-text px-2 text-[10.5px] relative z-10 bg-transparent">{{ t("auth.login.divider_or") }}</span>
    </div>

    <!-- أسفل الفورم -->
    <div class="auth-below-text text-center text-[11px] leading-relaxed">
      <span>{{ t("auth.login.no_account") }} </span>
      <RouterLink :to="{ name: 'register' }" class="auth-link-gradient font-bold">{{ t("auth.login.create_account") }}</RouterLink>
    </div>
  </div>
</template>