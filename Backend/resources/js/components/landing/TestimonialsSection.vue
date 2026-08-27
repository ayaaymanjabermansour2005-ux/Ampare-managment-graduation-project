<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { ChevronDown, ChevronUp, Star } from "@lucide/vue";

const { t, tm } = useI18n();

const INITIAL_COUNT = 3;
const expanded = ref(false);

const allItems = computed(() => tm("landing.testimonials.items") || []);
const visibleItems = computed(() =>
  expanded.value ? allItems.value : allItems.value.slice(0, INITIAL_COUNT)
);
const hasMore = computed(() => allItems.value.length > INITIAL_COUNT);
</script>

<template>
  <section id="testimonials" class="py-14 sm:py-24 grid-texture">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.testimonials.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.testimonials.title") }}</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <div
          v-for="(item, i) in visibleItems"
          :key="item.name + i"
          v-reveal="(i % 3) * 90"
          class="glass-card p-4 sm:p-6"
        >
          <div class="star-row flex gap-1 mb-2.5 sm:mb-3 text-[12px] sm:text-sm" :aria-label="t('common.rate_n_stars', { n: item.stars })" role="img">
            <Star
              v-for="s in 5"
              :key="s"
              aria-hidden="true"
              :fill="s <= item.stars ? 'currentColor' : 'none'"
              :class="s <= item.stars ? 'text-[#D4AF37]' : 'text-[#D4AF37]/30'"
            />
          </div>
          <p class="text-[12.5px] sm:text-[13px] text-[#555] dark:text-[#c8cac5] leading-relaxed mb-3 sm:mb-4">"{{ item.text }}"</p>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gradient-to-br from-[#8A6D1F] to-[#52733D] flex items-center justify-center text-white text-[11px] sm:text-xs font-bold shrink-0">
              {{ item.name.slice(0, 1) }}
            </div>
            <div>
              <p class="text-[11.5px] sm:text-[12px] font-bold">{{ item.name }}</p>
              <p class="text-[9.5px] sm:text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ item.role }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasMore" class="text-center mt-6 sm:mt-9">
        <button
          type="button"
          class="btn-outline-fill px-5 py-2.5 sm:px-6 sm:py-3 rounded-full text-[12.5px] sm:text-[13px] font-bold"
          @click="expanded = !expanded"
        >
          <span v-if="!expanded" class="inline-flex items-center gap-1.5 whitespace-nowrap">
            {{ t("landing.testimonials.showMore", { count: allItems.length - INITIAL_COUNT }) }}
            <ChevronDown class="text-[10px]" aria-hidden="true" />
          </span>
          <span v-else class="inline-flex items-center gap-1.5 whitespace-nowrap">
            {{ t("landing.testimonials.showLess") }}
            <ChevronUp class="text-[10px]" aria-hidden="true" />
          </span>
        </button>
      </div>
    </div>
  </section>
</template>