<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import authService from "@/services/authService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { LoaderCircle, MailCheck } from "@lucide/vue";

const { t } = useI18n();

const email = ref("");
const isLoading = ref(false);
const error = ref(null);
const success = ref(false);
const successMessage = ref("");

async function handleSubmit() {
  isLoading.value = true;
  error.value = null;

  try {
    const response = await authService.forgotPassword(email.value);
    successMessage.value = response.message;
    success.value = true;
  } catch (err) {
    error.value = normalizeApiError(err, t("auth.forgot_password.generic_error")).message;
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div>
    <div v-if="success" class="bg-success-bg text-success text-sm rounded-lg p-4 text-center">
      <MailCheck class="text-xl mb-2 block" aria-hidden="true" />
      {{ successMessage }}
      <div class="mt-4">
        <RouterLink :to="{ name: 'login' }" class="auth-link-gradient text-sm font-bold">
          {{ t("auth.forgot_password.back_to_login") }}
        </RouterLink>
      </div>
    </div>

    <template v-else>
      <p class="auth-brand-desc text-[11px] text-center leading-relaxed mb-4">
        {{ t("auth.forgot_password.intro") }}
      </p>

      <form @submit.prevent="handleSubmit">
        <div v-if="error" class="bg-danger-bg text-danger text-[11px] rounded-lg p-2.5 mb-3">
          {{ error }}
        </div>

        <div class="mb-4">
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.forgot_password.email_label") }}</label>
          <input v-model="email" type="email" required class="auth-glass-input" />
        </div>

        <button type="submit" class="auth-btn-submit" :disabled="isLoading">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isLoading" />
          <span>{{ isLoading ? t("auth.forgot_password.submit_loading") : t("auth.forgot_password.submit_idle") }}</span>
        </button>
      </form>

      <p class="auth-below-text text-center text-[11px] leading-relaxed mt-4">
        {{ t("auth.forgot_password.remembered_password") }}
        <RouterLink :to="{ name: 'login' }" class="auth-link-gradient font-bold">{{ t("auth.forgot_password.login_link") }}</RouterLink>
      </p>
    </template>
  </div>
</template>