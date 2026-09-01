<script setup>
import { reactive, ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import publicContactService from "@/services/publicContactService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleCheck, CircleX, LoaderCircle, Send } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();

const subjectOptions = computed(() => [
  { value: "general", label: t("landing.contact.form.subject_general") },
  { value: "owner", label: t("landing.contact.form.subject_owner") },
  { value: "technical", label: t("landing.contact.form.subject_technical") },
  { value: "partnership", label: t("landing.contact.form.subject_partnership") },
]);

const form = reactive({ name: "", phone: "", email: "", subject: "general", message: "" });
const submitted = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref(null);
const fieldErrors = ref({});

function fieldError(field) {
  return fieldErrors.value?.[field]?.[0] ?? null;
}

const touched = reactive({ phone: false, email: false });
const PHONE_PATTERN = /^\+?[0-9]{7,15}$/;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const isPhoneValid = computed(() => (form.phone ? PHONE_PATTERN.test(form.phone.trim()) : null));
const isEmailValid = computed(() => (form.email ? EMAIL_PATTERN.test(form.email.trim()) : null));

async function handleSubmit() {
  isSubmitting.value = true;
  errorMessage.value = null;
  fieldErrors.value = {};

  try {
    await publicContactService.submit({ ...form });
    submitted.value = true;
    Object.assign(form, { name: "", phone: "", email: "", subject: "general", message: "" });
    touched.phone = false;
    touched.email = false;
    setTimeout(() => (submitted.value = false), 4000);
  } catch (err) {
    const normalized = normalizeApiError(err, t("landing.contact.generic_error"));
    if (normalized.status === 422) {
      fieldErrors.value = normalized.fieldErrors;
    } else {
      errorMessage.value = normalized.message;
    }
  } finally {
    isSubmitting.value = false;
  }
}

const INFO_CARDS = [
  { icon: "fa-phone", key: "phone", value: "+970 597939790", dir: "ltr" },
  { icon: "fa-envelope", key: "email", value: "support@ampir.ps", dir: "ltr" },
  { icon: "fa-location-dot", key: "location", value: null },
  { icon: "fa-clock", key: "hours", value: null },
];
</script>

<template>
  <section id="contact" class="py-14 sm:py-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.contact.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.contact.title") }}</h2>
      </div>

      <div class="grid lg:grid-cols-5 gap-4 sm:gap-6">
        <div class="lg:col-span-2 space-y-3 sm:space-y-4" v-reveal>
          <div v-for="card in INFO_CARDS" :key="card.key" class="glass-card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-11 sm:h-11 shrink-0 rounded-xl bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F] text-[13px] sm:text-base">
              <AppIcon :name="card.icon" />
            </div>
            <div class="min-w-0">
              <p class="text-[10.5px] sm:text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t(`landing.contact.info.${card.key}_label`) }}</p>
              <p class="font-bold text-[13px] sm:text-[14px] truncate" :dir="card.dir">{{ card.value ?? t(`landing.contact.info.${card.key}_value`) }}</p>
            </div>
          </div>
        </div>

        <div class="lg:col-span-3 glass-card p-4 sm:p-8" v-reveal>
          <div v-if="submitted" class="text-[12.5px] sm:text-[13px] font-bold text-[#28A745] bg-[#28A745]/10 rounded-xl p-3 mb-4 text-center">
            {{ t("landing.contact.sent_confirmation") }}
          </div>
          <div v-if="errorMessage" class="text-[11.5px] sm:text-[12px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-3 mb-4 text-center">
            {{ errorMessage }}
          </div>

          <form class="grid sm:grid-cols-2 gap-3 sm:gap-4" @submit.prevent="handleSubmit">
            <div>
              <label for="contact-name" class="form-label">{{ t("landing.contact.form.name_label") }}</label>
              <input id="contact-name" v-model="form.name" required type="text" autocomplete="name" class="form-field" />
              <p v-if="fieldError('name')" class="text-[10px] text-[#D9534F] mt-1">{{ fieldError("name") }}</p>
            </div>
            <div>
              <label for="contact-phone" class="form-label">{{ t("landing.contact.form.phone_label") }}</label>
              <div class="relative">
                <input
                  id="contact-phone"
                  v-model="form.phone"
                  required
                  type="tel"
                  inputmode="tel"
                  autocomplete="tel"
                  pattern="^\+?[0-9]{7,15}$"
                  :title="t('landing.contact.form.phone_invalid_hint')"
                  class="form-field pe-9"
                  :class="{ '!border-[#28A745]': touched.phone && isPhoneValid === true, '!border-[#D9534F]': touched.phone && isPhoneValid === false }"
                  dir="ltr"
                  placeholder="+970 5X XXX XXXX"
                  @blur="touched.phone = true"
                />
                <CircleCheck
                  class="absolute top-1/2 -translate-y-1/2 end-3 text-[13px] text-[#28A745]"
                  aria-hidden="true"
                  v-if="touched.phone && isPhoneValid === true"
                />
                <CircleX
                  class="absolute top-1/2 -translate-y-1/2 end-3 text-[13px] text-[#D9534F]"
                  aria-hidden="true"
                  v-else-if="touched.phone && isPhoneValid === false"
                />
              </div>
              <p v-if="touched.phone && isPhoneValid === false && !fieldError('phone')" class="text-[10px] text-[#D9534F] mt-1">{{ t("landing.contact.form.phone_invalid_hint") }}</p>
              <p v-if="fieldError('phone')" class="text-[10px] text-[#D9534F] mt-1">{{ fieldError("phone") }}</p>
            </div>
            <div class="sm:col-span-2">
              <label for="contact-email" class="form-label">{{ t("landing.contact.form.email_label") }}</label>
              <div class="relative">
                <input
                  id="contact-email"
                  v-model="form.email"
                  required
                  type="email"
                  inputmode="email"
                  autocomplete="email"
                  class="form-field pe-9"
                  :class="{ '!border-[#28A745]': touched.email && isEmailValid === true, '!border-[#D9534F]': touched.email && isEmailValid === false }"
                  dir="ltr"
                  placeholder="example@email.com"
                  @blur="touched.email = true"
                />
                <CircleCheck
                  class="absolute top-1/2 -translate-y-1/2 end-3 text-[13px] text-[#28A745]"
                  aria-hidden="true"
                  v-if="touched.email && isEmailValid === true"
                />
                <CircleX
                  class="absolute top-1/2 -translate-y-1/2 end-3 text-[13px] text-[#D9534F]"
                  aria-hidden="true"
                  v-else-if="touched.email && isEmailValid === false"
                />
              </div>
              <p v-if="touched.email && isEmailValid === false && !fieldError('email')" class="text-[10px] text-[#D9534F] mt-1">{{ t("landing.contact.form.email_invalid_hint") }}</p>
              <p v-if="fieldError('email')" class="text-[10px] text-[#D9534F] mt-1">{{ fieldError("email") }}</p>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label">{{ t("landing.contact.form.subject_label") }}</label>
              <AppDropdownSelect
                v-model="form.subject"
                :options="subjectOptions"
                variant="field"
                width-class="w-full"
                :match-trigger-width="true"
              />
            </div>
            <div class="sm:col-span-2">
              <label for="contact-message" class="form-label">{{ t("landing.contact.form.message_label") }}</label>
              <textarea id="contact-message" v-model="form.message" required rows="4" class="form-field"></textarea>
              <p v-if="fieldError('message')" class="text-[10px] text-[#D9534F] mt-1">{{ fieldError("message") }}</p>
            </div>
            <div class="sm:col-span-2">
              <button type="submit" :disabled="isSubmitting" class="w-full btn-fill bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white font-bold text-sm px-6 py-3.5 rounded-full shadow-lg hover:-translate-y-0.5 transition-transform duration-300 flex items-center justify-center gap-2 disabled:opacity-60">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" />
                <template v-else>{{ t("landing.contact.form.submit") }} <Send aria-hidden="true" /></template>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>