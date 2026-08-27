<script setup>
import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { RefreshCw } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t, tm } = useI18n();

const ROLES = ["admin", "owner", "technician", "subscriber"];
const ICONS = {
  admin: "fa-user-shield",
  owner: "fa-industry",
  technician: "fa-screwdriver-wrench",
  subscriber: "fa-house-user",
};

const flipped = reactive({ admin: false, owner: false, technician: false, subscriber: false });
function toggle(role) {
  flipped[role] = !flipped[role];
}
</script>

<template>
  <section id="roles" class="py-14 sm:py-24 grid-texture">
    <div class="max-w-7xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.roles.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.roles.title") }}</h2>
        <p class="text-[12.5px] sm:text-[13px] text-[#666] dark:text-[#aeb1ab] mt-2 sm:mt-3">{{ t("landing.roles.subtitle") }}</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-5">
        <div
          v-for="(role, i) in ROLES"
          :key="role"
          v-reveal="(i % 4) * 90"
          class="flip-card"
          :class="{ flipped: flipped[role] }"
          role="button"
          tabindex="0"
          :aria-pressed="flipped[role]"
          :aria-label="t(`landing.roles.items.${role}.title`)"
          @click="toggle(role)"
          @keydown.enter.prevent="toggle(role)"
          @keydown.space.prevent="toggle(role)"
        >
          <div class="flip-card-inner">
            <div class="flip-card-front glass-card p-2.5 sm:p-6 items-center justify-center text-center">
              <div class="w-8 h-8 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F] mb-1.5 sm:mb-4 text-[13px] sm:text-lg">
                <AppIcon :name="ICONS[role]" />
              </div>
              <h3 class="font-bold text-[11px] sm:text-[15px] mb-1 sm:mb-2 leading-snug">{{ t(`landing.roles.items.${role}.title`) }}</h3>
              <p class="hidden sm:block text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t(`landing.roles.items.${role}.summary`) }}</p>
              <p class="roles-summary-mobile sm:hidden text-[9.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-snug">{{ t(`landing.roles.items.${role}.summary`) }}</p>
              <span class="flip-hint mt-1.5 sm:mt-4 text-[8.5px] sm:text-[10.5px] text-[#8A6D1F] font-semibold flex items-center gap-1 sm:gap-1.5 opacity-70">
                <RefreshCw aria-hidden="true" /> {{ t("landing.roles.tap_for_details") }}
              </span>
            </div>
            <div class="flip-card-back glass-card p-2.5 sm:p-5">
              <h4 class="font-bold text-[10.5px] sm:text-[13px] mb-1.5 sm:mb-3 text-[#8A6D1F] flex items-center gap-1.5 sm:gap-2 leading-snug">
                <AppIcon :name="ICONS[role]" /> {{ t(`landing.roles.items.${role}.how_title`) }}
              </h4>
              <ul class="space-y-1 sm:space-y-1.5 text-[9px] sm:text-[11px] text-[#555] dark:text-[#c8cac5] leading-snug sm:leading-relaxed list-disc ps-3.5 sm:ps-4">
                <li v-for="(point, i) in tm(`landing.roles.items.${role}.points`)" :key="i">{{ point }}</li>
              </ul>
              <span class="mt-1.5 sm:mt-3 inline-flex items-center gap-1 sm:gap-1.5 text-[8.5px] sm:text-[10.5px] text-[#8A6D1F] font-semibold">
                <RefreshCw aria-hidden="true" /> {{ t("landing.roles.tap_to_go_back") }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>