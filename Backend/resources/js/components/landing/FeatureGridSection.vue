<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t } = useI18n();

// featured: true يحدّد البطاقة "البطلة" (Hero Tile) بالـ Bento Grid — بطاقة
// واحدة بس لازم تكون featured (تاخذ 2×2 خلايا)، والباقي 1×1 تلقائيًا
// (شوف .bento-item--hero بالـ CSS). لتغيير البطلة: انقلي featured:true لعنصر تاني.
const FEATURES = [
  { icon: "fa-plug-circle-bolt", key: "generators", featured: true },
  { icon: "fa-users", key: "subscribers" },
  { icon: "fa-gauge-high", key: "readings" },
  { icon: "fa-file-invoice-dollar", key: "billing" },
  { icon: "fa-wallet", key: "payments" },
  { icon: "fa-gas-pump", key: "fuel" },
  { icon: "fa-screwdriver-wrench", key: "maintenance" },
  { icon: "fa-user-gear", key: "technicians" },
  { icon: "fa-chart-line", key: "reports" },
];

/* ---------------- خلفية شبكية متحركة: كل خط يُرسم بتوقيت مستقل عن الباقي ---------------- */
const VERTICAL_LINES = computed(() =>
  Array.from({ length: 18 }, (_, i) => ({
    x: (i / 17) * 100,
    delay: ((i * 0.37) % 3.2).toFixed(2),
  })),
);
const HORIZONTAL_LINES = computed(() =>
  Array.from({ length: 10 }, (_, i) => ({
    y: (i / 9) * 100,
    delay: ((i * 0.53 + 0.6) % 3.2).toFixed(2),
  })),
);
</script>

<template>
  <section id="features" class="py-24 relative overflow-hidden">
    <svg class="feature-grid-bg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
      <defs>
        <radialGradient id="featureGridFade" cx="50%" cy="50%" r="65%">
          <stop offset="0%" stop-color="#fff" stop-opacity="1" />
          <stop offset="70%" stop-color="#fff" stop-opacity="0.55" />
          <stop offset="100%" stop-color="#fff" stop-opacity="0" />
        </radialGradient>
        <mask id="featureGridMask">
          <rect x="0" y="0" width="100" height="100" fill="url(#featureGridFade)" />
        </mask>
      </defs>
      <g mask="url(#featureGridMask)">
        <line
          v-for="(l, i) in VERTICAL_LINES" :key="`v${i}`"
          :x1="l.x" y1="0" :x2="l.x" y2="100"
          class="feature-grid-line" :style="{ animationDelay: `${l.delay}s` }"
        />
        <line
          v-for="(l, i) in HORIZONTAL_LINES" :key="`h${i}`"
          x1="0" :y1="l.y" x2="100" :y2="l.y"
          class="feature-grid-line" :style="{ animationDelay: `${l.delay}s` }"
        />
      </g>
    </svg>

    <div class="max-w-7xl mx-auto px-5 sm:px-8 relative">
      <div class="text-center max-w-xl mx-auto mb-14" v-reveal>
        <span class="text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.features.eyebrow") }}</span>
        <h2 class="text-3xl sm:text-4xl font-extrabold mt-2 mb-4">{{ t("landing.features.title") }}</h2>
        <p class="text-[14px] text-[#666] dark:text-[#aeb1ab]">{{ t("landing.features.subtitle") }}</p>
      </div>

      <!-- ===== Bento Grid: من مقاس lg فما فوق — كل المحتوى ظاهر مباشرة، بدون Hover ===== -->
      <div class="bento-grid hidden lg:grid" v-reveal>
        <article
          v-for="f in FEATURES"
          :key="f.key"
          class="bento-item glass-card"
          :class="{ 'bento-item--hero': f.featured }"
        >
          <span v-if="f.featured" class="bento-item__watermark" aria-hidden="true">
            <AppIcon :name="f.icon" />
          </span>

          <div
            class="feature-icon-wrap rounded-full flex items-center justify-center text-white shrink-0"
            :class="f.featured ? 'w-16 h-16 text-[22px]' : 'w-12 h-12 text-[16px]'"
          >
            <AppIcon :name="f.icon" />
          </div>

          <h3
            class="bento-item__title font-bold"
            :class="f.featured ? 'text-[20px] mt-4' : 'text-[14px] mt-3'"
          >
            {{ t(`landing.features.items.${f.key}_title`) }}
          </h3>
          <p
            class="bento-item__desc text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed"
            :class="f.featured ? 'text-[13.5px] mt-2' : 'text-[12px] mt-1'"
          >
            {{ t(`landing.features.items.${f.key}_desc`) }}
          </p>
        </article>
      </div>

      <!-- ===== Fallback: كارد واحد يجمع كل المزايا (موبايل/تابلت) — بدون تغيير ===== -->
      <div class="glass-card feature-list-card lg:hidden divide-y divide-black/5 dark:divide-white/5" v-reveal>
        <div
          v-for="f in FEATURES" :key="f.key"
          class="feature-list-item flex items-center gap-4 py-4 px-5 first:pt-5 last:pb-5"
        >
          <div class="feature-icon-wrap w-11 h-11 shrink-0 rounded-full flex items-center justify-center text-white">
            <AppIcon :name="f.icon" class="text-[15px]" />
          </div>
          <div class="min-w-0">
            <h3 class="font-bold text-[14px] mb-0.5">{{ t(`landing.features.items.${f.key}_title`) }}</h3>
            <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ t(`landing.features.items.${f.key}_desc`) }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>