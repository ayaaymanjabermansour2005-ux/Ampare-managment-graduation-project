<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { ArrowLeft, ArrowRight, CircleAlert, Eye, EyeOff, FileLock, LoaderCircle, Lock, MailCheck, PlugZap, User } from "@lucide/vue";


import authService from "@/services/authService";
import neighborhoodService from "@/services/neighborhoodService";
import AuthSelect from "@/components/auth/AuthSelect.vue";
import AuthDocumentField from "@/components/auth/AuthDocumentField.vue";

const { t, locale } = useI18n();
const router = useRouter();

const ALLOWED_DOC_EXTENSIONS = ["pdf", "jpg", "jpeg", "png", "webp"];
const ALLOWED_PHOTO_EXTENSIONS = ["jpg", "jpeg", "png", "webp"];

const form = reactive({
  name: "",
  email: "",
  phone: "",
  notes: "",
  password: "",
  password_confirmation: "",

  generator_name: "",
  generator_price_per_kw: "",
  generator_currency: "ILS",
  generator_capacity_kw: "",
  generator_city: "",
  generator_neighborhood_id: "",
  generator_address: "",
});

const neighborhoods = ref([]);
const isLoadingNeighborhoods = ref(true);

const currencyOptions = computed(() => [
  { value: "ILS", label: t("auth.owner_application.currency_ils") },
  { value: "USD", label: t("auth.owner_application.currency_usd") },
]);

const neighborhoodOptions = computed(() =>
  neighborhoods.value.map((n) => ({
    value: n.id,
    label: locale.value === "en" ? n.name_en || n.name : n.name,
  }))
);

const documentFields = [
  {
    key: "id_document",
    labelKey: "auth.owner_application.doc_id_label",
    hintKey: "auth.owner_application.doc_id_hint",
    icon: "fa-id-card",
    accept: ".pdf,.jpg,.jpeg,.png,.webp",
    extensions: ALLOWED_DOC_EXTENSIONS,
  },
  {
    key: "business_license",
    labelKey: "auth.owner_application.doc_business_license_label",
    hintKey: "auth.owner_application.doc_business_license_hint",
    icon: "fa-file-contract",
    accept: ".pdf,.jpg,.jpeg,.png,.webp",
    extensions: ALLOWED_DOC_EXTENSIONS,
  },
  {
    key: "generator_photo",
    labelKey: "auth.owner_application.doc_generator_photo_label",
    hintKey: "auth.owner_application.doc_generator_photo_hint",
    icon: "fa-camera",
    accept: ".jpg,.jpeg,.png,.webp",
    extensions: ALLOWED_PHOTO_EXTENSIONS,
  },
  {
    key: "ownership_contract",
    labelKey: "auth.owner_application.doc_ownership_contract_label",
    hintKey: "auth.owner_application.doc_ownership_contract_hint",
    icon: "fa-file-signature",
    accept: ".pdf,.jpg,.jpeg,.png,.webp",
    extensions: ALLOWED_DOC_EXTENSIONS,
  },
];

const documents = reactive({
  id_document: null,
  business_license: null,
  generator_photo: null,
  ownership_contract: null,
});
const documentErrors = reactive({
  id_document: null,
  business_license: null,
  generator_photo: null,
  ownership_contract: null,
});

function onDocumentSelected(field, file) {
  documentErrors[field.key] = null;

  const ext = file.name.split(".").pop()?.toLowerCase();
  if (!field.extensions.includes(ext)) {
    documentErrors[field.key] = t("auth.owner_application.doc_invalid_type");
    return;
  }
  documents[field.key] = file;
}

function removeDocument(key) {
  documents[key] = null;
  documentErrors[key] = null;
}

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const submitState = ref("idle"); // idle | loading | success
const errors = ref(null);
const generalError = ref(null);
const successEmail = ref("");

function fieldError(field) {
  if (field === "password_confirmation" && passwordMismatch.value) {
    return t("auth.password_mismatch");
  }
  return errors.value?.[field]?.[0] ?? errors.value?.[`${field}.0`]?.[0] ?? null;
}

// FIX: ما كان في تحقّق محلي من تطابق كلمتي المرور — نفس النمط المُطبَّق
// بصفحات التسجيل الأخرى.
const passwordMismatch = computed(
  () => form.password_confirmation.length > 0 && form.password !== form.password_confirmation,
);

async function loadNeighborhoods() {
  try {
    const { data } = await neighborhoodService.list();
    neighborhoods.value = data.data;
  } finally {
    isLoadingNeighborhoods.value = false;
  }
}

async function handleSubmit() {
  let hasMissingDocument = false;
  for (const field of documentFields) {
    if (!documents[field.key]) {
      documentErrors[field.key] = t("auth.owner_application.doc_required");
      hasMissingDocument = true;
    }
  }
  if (hasMissingDocument) return;
  if (passwordMismatch.value) return;

  submitState.value = "loading";
  errors.value = null;
  generalError.value = null;

  try {
    await authService.submitOwnerApplication({
      ...form,
      id_document: documents.id_document,
      business_license: documents.business_license,
      generator_photo: documents.generator_photo,
      ownership_contract: documents.ownership_contract,
    });
    successEmail.value = form.email;
    submitState.value = "success";
  } catch (error) {
    submitState.value = "idle";
    errors.value = error.response?.data?.errors ?? null;
    if (!errors.value) {
      generalError.value = error.response?.data?.message ?? t("auth.owner_application.generic_error");
    }
  }
}

onMounted(loadNeighborhoods);
</script>

<template>
  <div>
    <!-- حالة النجاح -->
    <div v-if="submitState === 'success'" class="text-center">
      <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-[#28A745]/10 flex items-center justify-center">
        <MailCheck class="text-[#28A745] text-xl" aria-hidden="true" />
      </div>
      <h3 class="text-[14px] font-extrabold mb-2">{{ t("auth.owner_application.success_title") }}</h3>
      <p class="auth-brand-desc text-[11.5px] leading-relaxed px-1">
        {{ t("auth.owner_application.success_message", { email: successEmail }) }}
      </p>
      <RouterLink :to="{ name: 'login' }" class="auth-btn-submit mt-5 inline-flex">
        {{ t("auth.owner_application.back_to_login") }}
      </RouterLink>
    </div>

    <!-- الفورم -->
    <template v-else>
      <p class="auth-brand-desc text-[11px] text-center leading-relaxed mb-4">
        {{ t("auth.owner_application.intro") }}
      </p>

      <div v-if="generalError" class="text-[11px] bg-danger-bg text-danger rounded-lg p-2.5 mb-3">
        <CircleAlert class="me-1" aria-hidden="true" />{{ generalError }}
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-3">
        <!-- ================= بيانات المالك ================= -->
        <h4 class="auth-section-title">
          <User aria-hidden="true" />
          {{ t("auth.owner_application.section_owner") }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.full_name_label") }}</label>
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
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.email_label") }}</label>
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
              {{ t("auth.owner_application.phone_label") }} <span class="text-[#6B6B6B] dark:text-[#a8aaa5] font-normal">{{ t("auth.owner_application.phone_optional") }}</span>
            </label>
            <input
              v-model="form.phone"
              type="tel"
              :placeholder="t('auth.owner_application.phone_placeholder')"
              class="auth-glass-input"
              :class="{ 'is-invalid': fieldError('phone') }"
            />
            <p v-if="fieldError('phone')" class="auth-field-error">{{ fieldError("phone") }}</p>
          </div>
        </div>

        <!-- ================= بيانات المولد ================= -->
        <h4 class="auth-section-title">
          <PlugZap aria-hidden="true" />
          {{ t("auth.owner_application.section_generator") }}
        </h4>
        <p class="auth-section-hint">{{ t("auth.owner_application.section_generator_hint") }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.generator_name_label") }}</label>
            <input
              v-model="form.generator_name"
              type="text"
              required
              maxlength="150"
              :placeholder="t('auth.owner_application.generator_name_placeholder')"
              class="auth-glass-input"
              :class="{ 'is-invalid': fieldError('generator_name') }"
            />
            <p v-if="fieldError('generator_name')" class="auth-field-error">{{ fieldError("generator_name") }}</p>
          </div>

          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">
              {{ t("auth.owner_application.generator_neighborhood_label") }}
              <span class="text-[#6B6B6B] dark:text-[#a8aaa5] font-normal">{{ t("auth.owner_application.optional") }}</span>
            </label>
            <AuthSelect
              v-model="form.generator_neighborhood_id"
              :options="neighborhoodOptions"
              :disabled="isLoadingNeighborhoods"
              :invalid="!!fieldError('generator_neighborhood_id')"
              :placeholder="isLoadingNeighborhoods ? t('auth.register.neighborhood_loading') : t('auth.owner_application.generator_neighborhood_placeholder')"
            />
            <p v-if="fieldError('generator_neighborhood_id')" class="auth-field-error">{{ fieldError("generator_neighborhood_id") }}</p>
          </div>

          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.generator_price_label") }}</label>
            <input
              v-model="form.generator_price_per_kw"
              type="number"
              step="0.01"
              min="0"
              required
              class="auth-glass-input"
              :class="{ 'is-invalid': fieldError('generator_price_per_kw') }"
            />
            <p v-if="fieldError('generator_price_per_kw')" class="auth-field-error">{{ fieldError("generator_price_per_kw") }}</p>
          </div>

          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.generator_city_label") }}</label>
            <input
              v-model="form.generator_city"
              type="text"
              required
              maxlength="100"
              :placeholder="t('auth.owner_application.generator_city_placeholder')"
              class="auth-glass-input"
              :class="{ 'is-invalid': fieldError('generator_city') }"
            />
            <p v-if="fieldError('generator_city')" class="auth-field-error">{{ fieldError("generator_city") }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.generator_currency_label") }}</label>
            <AuthSelect v-model="form.generator_currency" :options="currencyOptions" />
          </div>
          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.generator_capacity_label") }}</label>
            <input
              v-model="form.generator_capacity_kw"
              type="number"
              min="1"
              class="auth-glass-input"
              :class="{ 'is-invalid': fieldError('generator_capacity_kw') }"
            />
            <p v-if="fieldError('generator_capacity_kw')" class="auth-field-error">{{ fieldError("generator_capacity_kw") }}</p>
          </div>

          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">
              {{ t("auth.owner_application.generator_address_label") }}
              <span class="text-[#6B6B6B] dark:text-[#a8aaa5] font-normal">{{ t("auth.owner_application.optional") }}</span>
            </label>
            <input
              v-model="form.generator_address"
              type="text"
              maxlength="255"
              class="auth-glass-input"
              :class="{ 'is-invalid': fieldError('generator_address') }"
            />
            <p v-if="fieldError('generator_address')" class="auth-field-error">{{ fieldError("generator_address") }}</p>
          </div>

          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.notes_label") }}</label>
            <textarea
              v-model="form.notes"
              rows="1"
              maxlength="2000"
              class="auth-glass-input resize-none"
              :class="{ 'is-invalid': fieldError('notes') }"
            ></textarea>
            <p v-if="fieldError('notes')" class="auth-field-error">{{ fieldError("notes") }}</p>
          </div>
        </div>

        <!-- ================= المستندات الأربعة الإلزامية ================= -->
        <h4 class="auth-section-title">
          <FileLock aria-hidden="true" />
          {{ t("auth.owner_application.section_documents") }}
        </h4>
        <p class="auth-section-hint">{{ t("auth.owner_application.section_documents_hint") }}</p>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <AuthDocumentField
            v-for="field in documentFields"
            :key="field.key"
            :label="t(field.labelKey)"
            :hint="t(field.hintKey)"
            :icon="field.icon"
            :accept="field.accept"
            required
            :file="documents[field.key]"
            :error="documentErrors[field.key] || fieldError(field.key)"
            :remove-label="t('auth.owner_application.documents_remove')"
            @select="(file) => onDocumentSelected(field, file)"
            @remove="removeDocument(field.key)"
          />
        </div>

        <!-- ================= كلمة السر ================= -->
        <h4 class="auth-section-title">
          <Lock aria-hidden="true" />
          {{ t("auth.owner_application.password_label") }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.password_label") }}</label>
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
            <p class="text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("auth.owner_application.password_hint") }}</p>
            <p v-if="fieldError('password')" class="auth-field-error">{{ fieldError("password") }}</p>
          </div>

          <div>
            <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">{{ t("auth.owner_application.password_confirmation_label") }}</label>
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
        </div>

        <button
          type="submit"
          class="auth-btn-submit"
          :disabled="submitState !== 'idle' || passwordMismatch"
        >
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="submitState === 'loading'" />
          <span>{{ submitState === "loading" ? t("auth.owner_application.submit_loading") : t("auth.owner_application.submit_idle") }}</span>
        </button>
      </form>

      <div class="flex items-center justify-between mt-4">
        <button type="button" class="auth-link-gradient text-[11px] font-bold" @click="router.push({ name: 'register' })">
          <ArrowRight class="rtl:inline-block ltr:hidden me-1" aria-hidden="true" />
          <ArrowLeft class="ltr:inline-block rtl:hidden me-1" aria-hidden="true" />
          {{ t("auth.owner_application.back_to_role_picker") }}
        </button>
      </div>

      <p class="auth-below-text text-center text-[11px] leading-relaxed mt-4">
        {{ t("auth.owner_application.have_account") }}
        <RouterLink :to="{ name: 'login' }" class="auth-link-gradient font-bold">{{ t("auth.owner_application.login_link") }}</RouterLink>
      </p>
    </template>
  </div>
</template>