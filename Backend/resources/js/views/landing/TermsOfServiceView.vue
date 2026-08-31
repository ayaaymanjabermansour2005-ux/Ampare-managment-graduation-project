<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { House, ShieldAlert } from "@lucide/vue";

const { t, locale } = useI18n();

const LAST_UPDATED = "2026-08-31";

const updatedAtLabel = computed(() =>
  t("landing.legal.terms_of_service.updated_at", {
    date: new Intl.DateTimeFormat(locale.value === "ar" ? "ar" : "en", { dateStyle: "long" }).format(
      new Date(LAST_UPDATED),
    ),
  }),
);

const sections = computed(() =>
  Array.from({ length: 11 }, (_, i) => i + 1).map((n) => ({
    title: t(`landing.legal.terms_of_service.s${n}_title`),
    body: t(`landing.legal.terms_of_service.s${n}_body`),
  })),
);
</script>

<template>
  <div class="max-w-2xl mx-auto px-5 sm:px-8 py-16">
    <RouterLink
      :to="{ name: 'landing.home' }"
      class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5] hover:text-[#8A6D1F] dark:hover:text-[#F4E0A5] mb-6"
    >
      <House aria-hidden="true" />
      {{ t("landing.legal.back_to_home") }}
    </RouterLink>

    <h1 class="text-[22px] sm:text-[26px] font-black mb-1.5">{{ t("landing.legal.terms_of_service.title") }}</h1>
    <p class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] mb-6">{{ updatedAtLabel }}</p>

    <div class="glass-card flex items-start gap-2.5 p-3.5 mb-8 text-[11.5px] leading-relaxed text-[#8A6D1F] dark:text-[#F4E0A5]">
      <ShieldAlert class="shrink-0 mt-0.5" aria-hidden="true" />
      <span>{{ t("landing.legal.disclaimer") }}</span>
    </div>

    <p class="text-[13px] leading-relaxed mb-8">{{ t("landing.legal.terms_of_service.intro") }}</p>

    <section v-for="(s, i) in sections" :key="i" class="mb-7">
      <h2 class="text-[14.5px] font-bold mb-2">{{ s.title }}</h2>
      <p class="text-[13px] leading-relaxed text-[#5b5d58] dark:text-[#c8cac5]">{{ s.body }}</p>
    </section>
  </div>
</template>
