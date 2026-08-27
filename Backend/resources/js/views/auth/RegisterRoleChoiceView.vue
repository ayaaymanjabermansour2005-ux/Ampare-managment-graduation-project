<script setup>
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { ArrowLeft, ArrowRight, House, Info, PlugZap } from "@lucide/vue";

const { t } = useI18n();
const router = useRouter();

function chooseSubscriber() {
  router.push({ name: "register.subscriber" });
}
function chooseOwner() {
  router.push({ name: "register.owner" });
}
</script>

<template>
  <div>
    <p class="auth-brand-desc text-[11.5px] text-center leading-relaxed mb-6">
      {{ t("auth.role_picker.subtitle") }}
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <!-- مشترك -->
      <button
        type="button"
        class="group relative overflow-hidden text-center p-6 rounded-3xl border border-white/40 dark:border-white/10 bg-white/60 dark:bg-white/[0.04] backdrop-blur-xl shadow-[0_10px_30px_rgba(31,45,20,0.08)] hover:shadow-[0_18px_40px_rgba(82,115,61,0.18)] hover:-translate-y-1.5 hover:border-[#52733D]/50 transition-all duration-300"
        @click="chooseSubscriber"
      >
        <div class="absolute -top-10 -end-10 w-32 h-32 rounded-full bg-[#52733D]/10 blur-2xl group-hover:bg-[#52733D]/20 transition-colors duration-300"></div>

        <div
          class="relative w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-[#3E582E] to-[#52733D] flex items-center justify-center text-white text-2xl shadow-lg group-hover:scale-110 transition-transform duration-300"
        >
          <House aria-hidden="true" />
        </div>

        <h3 class="relative text-[15px] font-extrabold mb-2">{{ t("auth.role_picker.subscriber_title") }}</h3>
        <p class="relative text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed mb-4">
          {{ t("auth.role_picker.subscriber_desc") }}
        </p>

        <span class="relative inline-flex items-center gap-1.5 text-[11px] font-bold text-[#3E582E] dark:text-[#8cc35a]">
          {{ t("auth.role_picker.continue") }}
          <ArrowLeft class="rtl:inline-block ltr:hidden group-hover:-translate-x-1 transition-transform duration-300" aria-hidden="true" />
          <ArrowRight class="ltr:inline-block rtl:hidden group-hover:translate-x-1 transition-transform duration-300" aria-hidden="true" />
        </span>
      </button>

      <!-- صاحب مولد -->
      <button
        type="button"
        class="group relative overflow-hidden text-center p-6 rounded-3xl border border-white/40 dark:border-white/10 bg-white/60 dark:bg-white/[0.04] backdrop-blur-xl shadow-[0_10px_30px_rgba(31,45,20,0.08)] hover:shadow-[0_18px_40px_rgba(212,175,55,0.2)] hover:-translate-y-1.5 hover:border-[#D4AF37]/50 transition-all duration-300"
        @click="chooseOwner"
      >
        <div class="absolute -top-10 -end-10 w-32 h-32 rounded-full bg-[#D4AF37]/10 blur-2xl group-hover:bg-[#D4AF37]/20 transition-colors duration-300"></div>

        <div
          class="relative w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center text-white text-2xl shadow-lg group-hover:scale-110 transition-transform duration-300"
        >
          <PlugZap aria-hidden="true" />
        </div>

        <h3 class="relative text-[15px] font-extrabold mb-2">{{ t("auth.role_picker.owner_title") }}</h3>
        <p class="relative text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed mb-4">
          {{ t("auth.role_picker.owner_desc") }}
        </p>

        <span class="relative inline-flex items-center gap-1.5 text-[11px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5]">
          {{ t("auth.role_picker.continue") }}
          <ArrowLeft class="rtl:inline-block ltr:hidden group-hover:-translate-x-1 transition-transform duration-300" aria-hidden="true" />
          <ArrowRight class="ltr:inline-block rtl:hidden group-hover:translate-x-1 transition-transform duration-300" aria-hidden="true" />
        </span>
      </button>
    </div>

    <div class="flex items-start gap-2.5 mt-5 p-3.5 rounded-2xl bg-[#17A2B8]/10 text-[10.5px] leading-relaxed text-[#0f6c7d] dark:text-[#5fd3e6]">
      <Info class="mt-0.5 shrink-0" aria-hidden="true" />
      <span>{{ t("auth.role_picker.owner_note") }}</span>
    </div>

    <p class="auth-below-text text-center text-[11px] leading-relaxed mt-5">
      <span>{{ t("auth.register.have_account") }} </span>
      <RouterLink :to="{ name: 'login' }" class="auth-link-gradient font-bold">{{ t("auth.register.login_link") }}</RouterLink>
    </p>
  </div>
</template>