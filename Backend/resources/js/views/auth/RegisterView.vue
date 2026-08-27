<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { ArrowLeft, ArrowRight, Check, Eye, EyeOff, LoaderCircle } from "@lucide/vue";

import neighborhoodService from "@/services/neighborhoodService";
import AuthSelect from "@/components/auth/AuthSelect.vue";
import { PENDING_SUBSCRIBE_GENERATOR_KEY } from "@/utils/pendingSubscribeGenerator";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t, locale } = useI18n();

/* ---------------- تسجيل جاي من رابط "اشتراك" بخريطة اللاندنج بيج ----------------
 * نحفظ generator_id محليًا هون، ويُستهلَك تلقائيًا أول ما يسجّل الدخول
 * (راجع LoginView.vue) — بما إنه التحقّق من البريد مطلوب قبل الدخول،
 * ما بقدر نحوّله فورًا لمركز الاشتراكات الآن.
 */
onMounted(() => {
  const generatorId = route.query.generator_id;
  if (generatorId) {
    try {
      localStorage.setItem(PENDING_SUBSCRIBE_GENERATOR_KEY, String(generatorId));
    } catch {
      // localStorage غير متاح (وضع خاص مثلًا) — تجاهل بهدوء، مش حرِج للتسجيل نفسه.
    }
  }
});

const form = reactive({
  name: "",
  email: "",
  phone: "",
  neighborhood_id: "",
  address: "",
  password: "",
  password_confirmation: "",
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const neighborhoods = ref([]);
const isLoadingNeighborhoods = ref(true);
const submitState = ref("idle");

const neighborhoodOptions = computed(() =>
  neighborhoods.value.map((n) => ({
    value: n.id,
    label: locale.value === "en" ? n.name_en || n.name : n.name,
  }))
);

async function loadNeighborhoods() {
  try {
    const { data } = await neighborhoodService.list();
    neighborhoods.value = data.data;
  } finally {
    isLoadingNeighborhoods.value = false;
  }
}

function fieldError(field) {
  return authStore.errors?.[field]?.[0] ?? null;
}

async function handleSubmit() {
  submitState.value = "loading";
  try {
    await authStore.register({ ...form });
    submitState.value = "success";
    setTimeout(() => {
      router.push({ name: "login", query: { registered: "1" } });
    }, 500);
  } catch {
    submitState.value = "idle";
  }
}

onMounted(loadNeighborhoods);
</script>

<template>
  <div>
    <div v-if="authStore.errorMessage" class="text-[11px] bg-danger-bg text-danger rounded-lg p-2.5 mb-3">
      {{ authStore.errorMessage }}
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-3">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.register.full_name_label") }}</label>
          <input
            v-model="form.name"
            type="text"
            required
            minlength="3"
            maxlength="100"
            class="auth-glass-input"
            :class="{ 'is-invalid': fieldError('name') }"
          />
          <p v-if="fieldError('name')" class="auth-field-error">{{ fieldError("name") }}</p>
        </div>

        <div>
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.register.email_label") }}</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="auth-glass-input"
            :class="{ 'is-invalid': fieldError('email') }"
          />
          <p v-if="fieldError('email')" class="auth-field-error">{{ fieldError("email") }}</p>
        </div>

        <div>
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">
            {{ t("auth.register.phone_label") }} <span class="text-[#6B6B6B] dark:text-[#a8aaa5] font-normal">{{ t("auth.register.phone_optional") }}</span>
          </label>
          <input
            v-model="form.phone"
            type="tel"
            :placeholder="t('auth.register.phone_placeholder')"
            class="auth-glass-input"
            :class="{ 'is-invalid': fieldError('phone') }"
          />
          <p v-if="fieldError('phone')" class="auth-field-error">{{ fieldError("phone") }}</p>
        </div>

        <div>
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.register.neighborhood_label") }}</label>
          <AuthSelect
            v-model="form.neighborhood_id"
            :options="neighborhoodOptions"
            :disabled="isLoadingNeighborhoods"
            :invalid="!!fieldError('neighborhood_id')"
            :placeholder="isLoadingNeighborhoods ? t('auth.register.neighborhood_loading') : t('auth.register.neighborhood_placeholder')"
          />
          <p v-if="fieldError('neighborhood_id')" class="auth-field-error">{{ fieldError("neighborhood_id") }}</p>
        </div>
      </div>

      <div>
        <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.register.address_label") }}</label>
        <input
          v-model="form.address"
          type="text"
          required
          maxlength="255"
          :placeholder="t('auth.register.address_placeholder')"
          class="auth-glass-input"
          :class="{ 'is-invalid': fieldError('address') }"
        />
        <p v-if="fieldError('address')" class="auth-field-error">{{ fieldError("address") }}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.register.password_label") }}</label>
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
          <p class="text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("auth.register.password_hint") }}</p>
          <p v-if="fieldError('password')" class="auth-field-error">{{ fieldError("password") }}</p>
        </div>

        <div>
          <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.register.password_confirmation_label") }}</label>
          <div class="relative">
            <input
              v-model="form.password_confirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              required
              class="auth-glass-input auth-input-with-eye"
            />
            <span class="auth-password-eye" role="button" tabindex="0" :aria-label="t('auth.toggle_password_visibility')" @click="showPasswordConfirmation = !showPasswordConfirmation" @keydown.enter.prevent="showPasswordConfirmation = !showPasswordConfirmation" @keydown.space.prevent="showPasswordConfirmation = !showPasswordConfirmation">
              <EyeOff aria-hidden="true" v-if="showPasswordConfirmation" class="text-xs" /><Eye aria-hidden="true" v-else class="text-xs" />
            </span>
          </div>
        </div>
      </div>

      <button
        type="submit"
        class="auth-btn-submit"
        :class="{ 'is-success': submitState === 'success' }"
        :disabled="submitState !== 'idle'"
      >
        <LoaderCircle v-if="submitState === 'loading'" class="animate-spin" aria-hidden="true" />
        <Check v-else-if="submitState === 'success'" aria-hidden="true" />
        <span v-if="submitState === 'idle'">{{ t("auth.register.submit_idle") }}</span>
        <span v-else-if="submitState === 'loading'">{{ t("auth.register.submit_loading") }}</span>
        <span v-else>{{ t("auth.register.submit_success") }}</span>
        <ArrowLeft v-if="submitState === 'idle'" class="rtl:inline-block ltr:hidden" aria-hidden="true" />
        <ArrowRight v-if="submitState === 'idle'" class="ltr:inline-block rtl:hidden" aria-hidden="true" />
      </button>
    </form>

    <div class="flex items-center justify-between mt-4">
      <button type="button" class="auth-link-gradient text-[11px] font-bold" @click="router.push({ name: 'register' })">
        <ArrowRight class="rtl:inline-block ltr:hidden me-1" aria-hidden="true" />
        <ArrowLeft class="ltr:inline-block rtl:hidden me-1" aria-hidden="true" />
        {{ t("auth.register.back_to_role_picker") }}
      </button>
    </div>

    <div class="text-center my-3 relative">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full auth-divider-line"></div>
      </div>
      <span class="auth-divider-text px-2 text-[10.5px] relative z-10 bg-transparent">{{ t("auth.register.divider_or") }}</span>
    </div>

    <p class="auth-below-text text-center text-[11px] leading-relaxed">
      {{ t("auth.register.have_account") }}
      <RouterLink :to="{ name: 'login' }" class="auth-link-gradient font-bold">{{ t("auth.register.login_link") }}</RouterLink>
    </p>
  </div>
</template>