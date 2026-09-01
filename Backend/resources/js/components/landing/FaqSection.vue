<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { ChevronDown, ChevronUp } from "@lucide/vue";

const { t, tm } = useI18n();
const openIndex = ref(null);
function toggle(i) {
  openIndex.value = openIndex.value === i ? null : i;
}

const INITIAL_COUNT = 4;
const expanded = ref(false);
const allItems = computed(() => tm("landing.faq.items") || []);
const visibleItems = computed(() => (expanded.value ? allItems.value : allItems.value.slice(0, INITIAL_COUNT)));
const hasMore = computed(() => allItems.value.length > INITIAL_COUNT);
</script>

<template>
  <section id="faq" class="py-14 sm:py-24 grid-texture section-tint-sage">
    <div class="max-w-3xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.faq.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.faq.title") }}</h2>
      </div>
      <div class="space-y-2.5 sm:space-y-3">
        <div
          v-for="(item, i) in visibleItems"
          :key="i"
          v-reveal
          class="glass-card p-4 sm:p-5 cursor-pointer"
          role="button"
          tabindex="0"
          :aria-expanded="openIndex === i"
          @click="toggle(i)"
          @keydown.enter.prevent="toggle(i)"
          @keydown.space.prevent="toggle(i)"
        >
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-bold text-[12.5px] sm:text-[13.5px] leading-snug">{{ item.q }}</h3>
            <ChevronDown class="text-[#8A6D1F] text-[13px] sm:text-base shrink-0 transition-transform duration-300" :class="openIndex === i ? 'rotate-180' : ''" aria-hidden="true" />
          </div>
          <div class="grid transition-all duration-300 ease-out" :style="{ gridTemplateRows: openIndex === i ? '1fr' : '0fr' }">
            <div class="overflow-hidden">
              <p class="text-[11.5px] sm:text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed pt-2.5 sm:pt-3">{{ item.a }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasMore" class="text-center mt-6 sm:mt-8">
        <button
          type="button"
          class="btn-outline-fill px-5 py-2.5 sm:px-6 sm:py-3 rounded-full text-[12.5px] sm:text-[13px] font-bold"
          @click="expanded = !expanded"
        >
          <span v-if="!expanded" class="inline-flex items-center gap-1.5 whitespace-nowrap">
            {{ t("landing.faq.showMore", { count: allItems.length - INITIAL_COUNT }) }}
            <ChevronDown class="text-[10px]" aria-hidden="true" />
          </span>
          <span v-else class="inline-flex items-center gap-1.5 whitespace-nowrap">
            {{ t("landing.faq.showLess") }}
            <ChevronUp class="text-[10px]" aria-hidden="true" />
          </span>
        </button>
      </div>
    </div>
  </section>
</template>