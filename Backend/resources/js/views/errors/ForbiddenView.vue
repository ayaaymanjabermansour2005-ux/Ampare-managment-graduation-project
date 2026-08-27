<script setup>
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { resolveHomeRouteName } from "@/utils/roleRedirect";
import { ArrowLeft, ArrowRight, Flag, Headset, House, Lock } from "@lucide/vue";


const router = useRouter();
const authStore = useAuthStore();

function goHome() {
  router.push({ name: resolveHomeRouteName(authStore) });
}

function goBack() {
  if (window.history.length > 1) router.back();
  else goHome();
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-[#f7f4ea] dark:bg-[#15171a]">
    <!-- توهجات خلفية بنفس هوية بقية الصفحات -->
    <div class="absolute -start-24 -top-24 w-96 h-96 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/15 rounded-full blur-[110px] pointer-events-none"></div>
    <div class="absolute -end-24 -bottom-24 w-96 h-96 bg-[#52733D]/20 dark:bg-[#8cc35a]/10 rounded-full blur-[110px] pointer-events-none"></div>

    <div class="glass-card relative w-full max-w-md p-8 lg:p-10 text-center overflow-hidden">
      <div class="absolute -start-16 -top-16 w-56 h-56 bg-danger/15 dark:bg-danger/20 rounded-full blur-[80px] pointer-events-none"></div>
      <div class="absolute -end-14 -bottom-16 w-56 h-56 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/15 rounded-full blur-[80px] pointer-events-none"></div>

      <div class="relative">
        <span class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#D9534F] to-[#8A2E2A] text-white flex items-center justify-center text-3xl shadow-lg mx-auto mb-5">
          <Lock aria-hidden="true" />
        </span>

        <p class="text-[11px] font-bold text-danger tracking-wide mb-1.5">{{ $t("errors.forbidden_eyebrow") }}</p>
        <h1 class="text-xl lg:text-2xl font-extrabold mb-2.5">
          {{ $t("errors.forbidden_title") }}
        </h1>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] leading-relaxed mb-7 max-w-sm mx-auto">
          {{ $t("errors.forbidden_message") }}
        </p>

        <div class="flex flex-wrap items-center justify-center gap-2.5">
          <button
            type="button"
            @click="goBack"
            class="text-[12.5px] font-bold px-5 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors"
          >
            <ArrowRight class="rtl:inline-block ltr:hidden ms-1" aria-hidden="true" />
            <ArrowLeft class="ltr:inline-block rtl:hidden ms-1" aria-hidden="true" />
            {{ $t("common.back") }}
          </button>
          <button
            type="button"
            @click="goHome"
            class="btn-fill relative text-[12.5px] font-bold px-6 py-2.5 rounded-full shadow-md text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] flex items-center gap-2"
          >
            <House aria-hidden="true" />
            {{ $t("errors.back_to_dashboard") }}
          </button>
        </div>

        <div class="mt-5 pt-5 border-t border-[#eee8da] dark:border-white/10">
          <RouterLink
            :to="{ name: 'landing.home', hash: '#contact' }"
            class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] hover:underline"
          >
            <Flag class="text-[10px]" aria-hidden="true" />
            {{ $t("errors.report_problem") }}
          </RouterLink>
          <span class="mx-2 text-[#c9cdc2] dark:text-white/15">|</span>
          <a
            href="mailto:support@ampere.ps?subject=%D8%A8%D9%84%D8%A7%D8%BA%20%D8%B9%D9%86%20%D9%85%D8%B4%D9%83%D9%84%D8%A9%20-%20%D8%AE%D8%B7%D8%A3%20403"
            class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline"
          >
            <Headset class="text-[10px]" aria-hidden="true" />
            {{ $t("errors.contact_support") }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>