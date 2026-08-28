<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import authService from "@/services/authService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { CircleCheck, Eye, EyeOff, LoaderCircle } from "@lucide/vue";


const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const form = reactive({
  token: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const isLoading = ref(false);
const error = ref(null);
const fieldErrors = ref({});
const success = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

function fieldError(field) {
  if (field === "password_confirmation" && passwordMismatch.value) {
    return t("auth.password_mismatch");
  }
  return fieldErrors.value?.[field]?.[0] ?? null;
}

// FIX: ما كان في تحقّق محلي من تطابق كلمتي المرور — نفس النمط المُطبَّق
// بصفحة التسجيل.
const passwordMismatch = computed(
  () => form.password_confirmation.length > 0 && form.password !== form.password_confirmation,
);

onMounted(() => {
  form.token = route.query.token ?? "";
  form.email = route.query.email ?? "";
});

async function handleSubmit() {
  if (passwordMismatch.value) return;
  isLoading.value = true;
  error.value = null;
  fieldErrors.value = {};

  try {
    await authService.resetPassword({ ...form });
    success.value = true;
    setTimeout(() => router.push({ name: "login" }), 2000);
  } catch (err) {
    const normalized = normalizeApiError(err, t("auth.reset_password.generic_error"));
    error.value = normalized.message;
    fieldErrors.value = normalized.fieldErrors;
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div>
    <div v-if="success" class="bg-success-bg text-success text-sm rounded-lg p-4 text-center">
      <CircleCheck class="text-xl mb-2 block" aria-hidden="true" />
      {{ t("auth.reset_password.success_message") }}
    </div>

    <form v-else @submit.prevent="handleSubmit">
      <div v-if="error" class="bg-danger-bg text-danger text-[11px] rounded-lg p-2.5 mb-3">
        {{ error }}
      </div>

      <div class="mb-3">
        <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.reset_password.email_label") }}</label>
        <input v-model="form.email" type="email" required class="auth-glass-input" :class="{ 'is-invalid': fieldError('email') }" />
        <p v-if="fieldError('email')" class="auth-field-error">{{ fieldError("email") }}</p>
      </div>

      <div class="mb-3">
        <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.reset_password.new_password_label") }}</label>
        <div class="relative">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            required
            minlength="10"
            class="auth-glass-input auth-input-with-eye"
            :class="{ 'is-invalid': fieldError('password') }"
          />
          <span class="auth-password-eye" role="button" tabindex="0" :aria-label="t('auth.toggle_password_visibility')" @click="showPassword = !showPassword" @keydown.enter.prevent="showPassword = !showPassword" @keydown.space.prevent="showPassword = !showPassword">
            <EyeOff aria-hidden="true" v-if="showPassword" class="text-xs" /><Eye aria-hidden="true" v-else class="text-xs" />
          </span>
        </div>
        <p v-if="fieldError('password')" class="auth-field-error">{{ fieldError("password") }}</p>
      </div>

      <div class="mb-4">
        <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.reset_password.confirm_password_label") }}</label>
        <div class="relative">
          <input
            v-model="form.password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            required
            class="auth-glass-input auth-input-with-eye"
            :class="{ 'is-invalid': passwordMismatch }"
          />
          <span class="auth-password-eye" role="button" tabindex="0" :aria-label="t('auth.toggle_password_visibility')" @click="showPasswordConfirmation = !showPasswordConfirmation" @keydown.enter.prevent="showPasswordConfirmation = !showPasswordConfirmation" @keydown.space.prevent="showPasswordConfirmation = !showPasswordConfirmation">
            <EyeOff aria-hidden="true" v-if="showPasswordConfirmation" class="text-xs" /><Eye aria-hidden="true" v-else class="text-xs" />
          </span>
        </div>
        <p v-if="passwordMismatch" class="auth-field-error">{{ t("auth.password_mismatch") }}</p>
      </div>

      <button type="submit" class="auth-btn-submit" :disabled="isLoading || passwordMismatch">
        <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isLoading" />
        <span>{{ isLoading ? t("auth.reset_password.submit_loading") : t("auth.reset_password.submit_idle") }}</span>
      </button>
    </form>
  </div>
</template>