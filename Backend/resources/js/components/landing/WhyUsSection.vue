<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { useCardStack } from "@/composables/useCardStack";
import { ChevronLeft, ChevronRight } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === "ar");

const ITEMS = [
  { icon: "fa-clock", key: "time", bg: "linear-gradient(135deg, #52733D, #3E582E)" },
  { icon: "fa-shield-halved", key: "secure", bg: "linear-gradient(135deg, #8A6D1F, #D4AF37)" },
  { icon: "fa-robot", key: "billing", bg: "linear-gradient(135deg, #3E582E, #52733D)" },
  { icon: "fa-layer-group", key: "central", bg: "linear-gradient(135deg, #D4AF37, #8A6D1F)" },
  { icon: "fa-chart-pie", key: "analytics", bg: "linear-gradient(135deg, #52733D, #8A6D1F)" },
  { icon: "fa-user-lock", key: "roles", bg: "linear-gradient(135deg, #8A6D1F, #3E582E)" },
  { icon: "fa-cloud", key: "cloud", bg: "linear-gradient(135deg, #3E582E, #8A6D1F)" },
  { icon: "fa-triangle-exclamation", key: "errors", bg: "#E73F3F" },
];

const {
  activeIndex, cardStyle, next, prev, goTo,
  onPointerDown, onPointerMove, onPointerUp,
  isPaused, observeVisibility,
} = useCardStack(ITEMS.length, { rtl: isRtl, maxDepth: 3 });

function onSectionMounted(el) {
  if (el) observeVisibility(el);
}

function onKeydown(e) {
  if (e.key === "ArrowRight") { isRtl.value ? prev() : next(); e.preventDefault(); }
  if (e.key === "ArrowLeft") { isRtl.value ? next() : prev(); e.preventDefault(); }
}
</script>

<template>
  <!-- SVG turbulence/displacement filter giving .why-card's ::before outline its
       hand-drawn, uneven-thickness look — see .why-card::before in landing-glass.css. -->
  <svg width="0" height="0" style="position: absolute" aria-hidden="true">
    <filter id="why-card-sketch-filter" x="-20%" y="-20%" width="140%" height="140%">
      <feTurbulence type="fractalNoise" baseFrequency="0.012 0.05" numOctaves="2" seed="7" result="noise" />
      <feDisplacementMap in="SourceGraphic" in2="noise" scale="7" xChannelSelector="R" yChannelSelector="G" />
    </filter>
  </svg>

  <section id="why" :ref="onSectionMounted" class="py-14 sm:py-24 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.why.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.why.title") }}</h2>
      </div>

      <div
        class="stack-wrap"
        role="region"
        :aria-label="t('landing.why.title')"
        tabindex="0"
        @mouseenter="isPaused = true"
        @mouseleave="isPaused = false"
        @focusin="isPaused = true"
        @focusout="isPaused = false"
        @keydown="onKeydown"
      >
        <button type="button" class="why-nav why-nav--prev hidden sm:flex" :aria-label="t('common.previous')" @click="prev">
          <ChevronRight aria-hidden="true" v-if="isRtl" /><ChevronLeft aria-hidden="true" v-else />
        </button>

        <div class="stack-track">
          <div
            v-for="(item, i) in ITEMS"
            :key="item.key"
            class="stack-card"
            :class="{ 'stack-card--active': activeIndex === i }"
            :style="cardStyle(i)"
            :aria-hidden="activeIndex !== i"
            role="group"
            :aria-label="t(`landing.why.items.${item.key}`)"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
          >
            <div class="why-card glass-card p-5 sm:p-7">
              <div class="why-card-icon" :style="{ '--why-icon-bg': item.bg, '--why-icon-fg': '#fff' }">
                <AppIcon :name="item.icon" />
              </div>
              <p class="font-bold text-[15px] sm:text-[19px] mb-2 sm:mb-3 leading-snug">{{ t(`landing.why.items.${item.key}`) }}</p>
              <p class="text-[12.5px] sm:text-[14.5px] leading-relaxed text-[#6B6B6B] dark:text-[#a8aaa5]">
                {{ t(`landing.why.items.${item.key}_desc`) }}
              </p>
            </div>
          </div>
        </div>

        <button type="button" class="why-nav why-nav--next hidden sm:flex" :aria-label="t('common.next')" @click="next">
          <ChevronLeft aria-hidden="true" v-if="isRtl" /><ChevronRight aria-hidden="true" v-else />
        </button>
      </div>

      <div class="why-dots" role="tablist" :aria-label="t('landing.why.title')">
        <button
          v-for="(item, i) in ITEMS"
          :key="item.key"
          type="button"
          class="why-dot"
          :class="{ 'why-dot--active': activeIndex === i }"
          role="tab"
          :aria-selected="activeIndex === i"
          :aria-label="t(`landing.why.items.${item.key}`)"
          @click="goTo(i)"
        ></button>
      </div>
    </div>
  </section>
</template>