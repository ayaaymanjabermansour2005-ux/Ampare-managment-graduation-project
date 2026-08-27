<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { motion, useReducedMotion } from "motion-v";
import { useAdminUiStore } from "@/stores/adminUi";
import { useLightningCanvas } from "@/composables/useLightningCanvas";
import { useParallax } from "@/composables/useParallax";
import { vReveal } from "@/directives/reveal";
import { vCountUp } from "@/directives/countUp";
import publicAnalyticsService from "@/services/publicAnalyticsService";
import publicGuestLoginService from "@/services/publicGuestLoginService";
import { ArrowLeft, ArrowRight, CircleAlert, CircleCheck, CirclePlay, ClockAlert, Factory, House, Lightbulb, LoaderCircle, PlugZap, Receipt, ShieldCheck, Users, Video, X } from "@lucide/vue";

const { t } = useI18n();

const uiStore = useAdminUiStore();

/* ---------------- دخول العنوان/الوصف/الأزرار بتتابع (stagger) عبر motion-v ---------------- */
const prefersReducedMotion = useReducedMotion();
const heroStagger = { hidden: {}, show: { transition: { staggerChildren: 0.12, delayChildren: 0.05 } } };
const heroItem = {
  hidden: { opacity: 0, y: 18 },
  show: { opacity: 1, y: 0, transition: { duration: 0.55, ease: [0.16, 1, 0.3, 1] } },
};

const canvasEl = ref(null);
useLightningCanvas(canvasEl, { density: 1, isDark: () => uiStore.isDark });

/* ---------------- تأثير Parallax خفيف على الكرات الضبابية الخلفية ---------------- */
const blobGold = ref(null);
const blobGreen = ref(null);
useParallax([
  { el: blobGold, speed: 0.18 },
  { el: blobGreen, speed: -0.12 },
]);

/* ---------------- بيانات الكروت الطايرة — حقيقية من التحليلات العامة ---------------- */
const cardStats = ref({ monthlyRevenue: 0, activeGenerators: 0, newSubscribers: 0 });
const cardsLoaded = ref(false);

async function loadCardStats() {
  try {
    const { data } = await publicAnalyticsService.platformAnalytics();
    cardStats.value = {
      monthlyRevenue: data.revenue_growth.values.at(-1) ?? 0,
      activeGenerators: data.quick_stats.active_generators_count ?? 0,
      newSubscribers: data.subscriber_growth.values.at(-1) ?? 0,
    };
  } catch {
    // فشل الجلب: الكروت بتضل بقيمة 0 بهدوء
  } finally {
    cardsLoaded.value = true;
  }
}
onMounted(loadCardStats);

/* ---------------- نافذة "جرب كزائر" — تسجيل دخول حقيقي ---------------- */
const showGuestModal = ref(false);
const guestLoginState = ref("idle"); // idle | loading-subscriber | loading-owner | error
const guestLoginError = ref(null);

async function chooseGuestRole(role) {
  guestLoginState.value = role === "owner" ? "loading-owner" : "loading-subscriber";
  guestLoginError.value = null;
  try {
    await publicGuestLoginService.guestLogin(role);
    window.location.assign(role === "owner" ? "/owner" : "/subscriber");
  } catch (err) {
    guestLoginState.value = "idle";
    guestLoginError.value = err.response?.data?.message ?? t("landing.hero.guest_login_error");
  }
}

/* ---------------- نافذة العرض التوضيحي ---------------- */
const showDemoModal = ref(false);
</script>

<template>
  <!--
    ترانزيشن "تعبئة" الهيرو (Fill/Empty Entrance):
    - يعبى (clip-path من الأسفل للأعلى) مرة وحدة أول ما يركب الكومبوننت appear.
    - يتفضى بنفس الحركة معكوسة لو انشال الكومبوننت من الشجرة مستقبلاً.
    - محايد لـ prefers-reduced-motion: بنفصل اسم الـ Transition بالكامل فهالحالة
      (name يصير undefined) فما بتنطبق أي كلاسات CSS ويظهر المحتوى فورًا بدون حركة.
    - التزامن مع Curtain الصفحة العام (.landing-entrance-overlay بـ landing-glass.css،
      مدته 1.5s): معطين delay (--hero-fill-delay بـ landing-glass.css) أطول شوي من
      مدة الـ Curtain عشان التأثير يضل مرئي لما ينكشف الـ Curtain، مش يخلص وهو
      مخفي وراه. لو غيّرتي مدة الـ Curtain بـ LandingLayout.vue، عدّلي نفس المتغير هناك.
  -->
  <Transition :name="prefersReducedMotion ? undefined : 'hero-fill'" appear>
      <section id="home" class="relative pt-24 sm:pt-28 lg:pt-32 pb-16 sm:pb-20 lg:pb-24 overflow-hidden">
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" style="position: absolute">
          <div class="absolute -top-32 end-0 w-[600px] h-[400px] bg-[#D4AF37]/15 dark:bg-[#D4AF37]/25 rounded-full blur-[120px]" ref="blobGold"></div>
          <div class="absolute top-40 -start-20 w-[500px] h-[400px] bg-[#52733D]/15 dark:bg-[#8cc35a]/20 rounded-full blur-[120px]" ref="blobGreen"></div>
          <canvas ref="canvasEl" class="absolute inset-0 w-full h-full opacity-60"></canvas>
        </div>
  
        <div class="max-w-7xl mx-auto px-5 sm:px-8 relative z-10 grid lg:grid-cols-2 gap-10 sm:gap-12 lg:gap-14 items-center">
          <motion.div
            :initial="prefersReducedMotion ? 'show' : 'hidden'"
            while-in-view="show"
            :viewport="{ once: true, amount: 0.4 }"
            :variants="heroStagger"
          >
            <motion.span :variants="heroItem" class="inline-flex items-center gap-2 text-[10px] sm:text-[11px] font-bold text-[#52733D] dark:text-[#8cc35a] bg-[#EBF1E7] dark:bg-white/5 border border-[#D4AF37]/30 rounded-full px-2.5 py-1 sm:px-3 sm:py-1.5 mb-4 sm:mb-5">
              <img src="/images/logo.png" :alt="t('auth.logo_alt')" class="w-3.5 h-3.5 sm:w-4 sm:h-4 object-contain" onerror="this.style.display='none'" /> {{ t("landing.hero.badge") }}
            </motion.span>
            <motion.h1 :variants="heroItem" class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-[1.25] mb-4 sm:mb-5">
              {{ t("landing.hero.title_line1") }}
              <span class="brand-gradient-text block">{{ t("landing.hero.title_line2") }}</span>
            </motion.h1>
            <motion.p :variants="heroItem" class="text-[14px] sm:text-[15px] text-[#666] dark:text-[#aeb1ab] leading-relaxed mb-7 sm:mb-8 max-w-lg">
              {{ t("landing.hero.description") }}
            </motion.p>
            <motion.div :variants="heroItem" class="flex flex-row flex-nowrap sm:flex-wrap items-stretch sm:items-center gap-2 sm:gap-3 mb-8 sm:mb-10 overflow-x-auto sm:overflow-visible">
              <RouterLink
                :to="{ name: 'login' }"
                class="btn-fill bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white font-bold text-[12px] sm:text-sm px-3.5 sm:px-6 py-2.5 sm:py-3.5 rounded-full shadow-lg hover:-translate-y-0.5 transition-transform duration-300 flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap shrink-0"
              >
                {{ t("landing.hero.cta_login") }}
              </RouterLink>
              <button type="button" class="btn-outline-fill font-bold text-[12px] sm:text-sm px-3.5 sm:px-6 py-2.5 sm:py-3.5 rounded-full flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap shrink-0" @click="showGuestModal = true">
                <ClockAlert aria-hidden="true" /> {{ t("landing.hero.cta_guest") }}
              </button>
              <button type="button" class="btn-outline-fill font-bold text-[12px] sm:text-sm px-3.5 sm:px-6 py-2.5 sm:py-3.5 rounded-full flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap shrink-0" @click="showDemoModal = true">
                <CirclePlay aria-hidden="true" /> {{ t("landing.hero.cta_demo") }}
              </button>
            </motion.div>
            <motion.div :variants="heroItem" class="flex flex-wrap items-center gap-x-6 gap-y-2 text-[11px] sm:text-[12px] text-[#6B6B6B] dark:text-[#9a9d97]">
              <span class="flex items-center gap-1.5"><CircleCheck class="text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" /> {{ t("landing.hero.trust_no_card") }}</span>
              <span class="flex items-center gap-1.5"><CircleCheck class="text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" /> {{ t("landing.hero.trust_quick_setup") }}</span>
            </motion.div>
          </motion.div>
  
          <div class="relative hero-preview-glow" v-reveal>
            <svg class="hero-wrap-ribbon hero-wrap-ribbon--tl" viewBox="0 0 140 140" preserveAspectRatio="none" aria-hidden="true">
              <path d="M4,90 C-6,55 14,15 55,6 C80,0 108,8 118,28 C104,20 82,16 65,24 C48,32 44,50 58,56 C72,62 90,52 96,66 C64,64 20,110 4,90 Z" />
            </svg>
            <svg class="hero-wrap-ribbon hero-wrap-ribbon--br" viewBox="0 0 140 140" preserveAspectRatio="none" aria-hidden="true">
              <path d="M136,50 C146,85 126,125 85,134 C60,140 32,132 22,112 C36,120 58,124 75,116 C92,108 96,90 82,84 C68,78 50,88 44,74 C76,76 120,30 136,50 Z" />
            </svg>
            <div class="glass-card !rounded-tl-[2.25rem] !rounded-br-[2.25rem] sm:!rounded-tl-[3rem] sm:!rounded-br-[3rem] p-3.5 sm:p-6">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                  <div class="w-2.5 h-2.5 rounded-full bg-[#EF4444]/70"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-[#FBBF24]/70"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-[#10B981]/70"></div>
                </div>
                <span class="text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] font-semibold">{{ t("landing.hero.dashboard_label") }}</span>
              </div>
              <div class="relative rounded-xl bg-gradient-to-br from-[#EBF1E7] to-white dark:from-[#22301c]/40 dark:to-[#1a1c1a] p-3 sm:p-4 overflow-hidden">
                <div class="absolute -top-8 -end-8 w-32 h-32 bg-[#D4AF37]/15 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative flex items-end gap-1.5 sm:gap-2 h-24 sm:h-32 mb-3">
                  <svg class="absolute inset-x-0 bottom-0 w-full h-full pointer-events-none" viewBox="0 0 240 128" preserveAspectRatio="none">
                    <polyline
                      points="20,90 60,68 100,42 140,60 180,35 220,12"
                      fill="none"
                      stroke="#D4AF37"
                      stroke-width="2.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      class="hero-trend-line"
                    />
                    <circle cx="220" cy="12" r="4" fill="#D4AF37" class="hero-trend-dot" />
                  </svg>
                  <div class="w-1/6 bg-gradient-to-t from-[#52733D] to-[#8cc35a] rounded-t-md" style="height: 40%"></div>
                  <div class="w-1/6 bg-gradient-to-t from-[#52733D] to-[#8cc35a] rounded-t-md" style="height: 65%"></div>
                  <div class="w-1/6 bg-gradient-to-t from-[#D4AF37] to-[#F4E0A5] rounded-t-md" style="height: 85%"></div>
                  <div class="w-1/6 bg-gradient-to-t from-[#52733D] to-[#8cc35a] rounded-t-md" style="height: 55%"></div>
                  <div class="w-1/6 bg-gradient-to-t from-[#52733D] to-[#8cc35a] rounded-t-md" style="height: 75%"></div>
                  <div class="w-1/6 bg-gradient-to-t from-[#D4AF37] to-[#F4E0A5] rounded-t-md" style="height: 95%"></div>
                </div>
                <p class="relative text-[10px] sm:text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] flex items-center gap-1.5 mb-3">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] status-dot-live"></span>
                  {{ t("landing.hero.preview_caption") }}
                </p>
                <div class="relative grid grid-cols-3 gap-1.5 sm:gap-2 pt-3 border-t border-[#e7e2d6] dark:border-white/10">
                  <div class="flex items-center gap-1 sm:gap-1.5">
                    <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg bg-gradient-to-br from-[#52733D] to-[#3E582E] flex items-center justify-center text-white text-[9px] sm:text-[10px] shrink-0"><PlugZap aria-hidden="true" /></span>
                    <span class="text-[9px] sm:text-[10px] font-semibold truncate">{{ t("landing.hero.preview_chip_generators") }}</span>
                  </div>
                  <div class="flex items-center gap-1 sm:gap-1.5">
                    <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center text-white text-[9px] sm:text-[10px] shrink-0"><Users aria-hidden="true" /></span>
                    <span class="text-[9px] sm:text-[10px] font-semibold truncate">{{ t("landing.hero.preview_chip_subscribers") }}</span>
                  </div>
                  <div class="flex items-center gap-1 sm:gap-1.5">
                    <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg bg-gradient-to-br from-[#3E582E] to-[#52733D] flex items-center justify-center text-white text-[9px] sm:text-[10px] shrink-0"><Receipt aria-hidden="true" /></span>
                    <span class="text-[9px] sm:text-[10px] font-semibold truncate">{{ t("landing.hero.preview_chip_billing") }}</span>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- الكروت الثلاثة: بيانات حقيقية (revenue_growth/subscriber_growth آخر شهر + عدد المولدات النشطة)، بعدّاد متحرك عند الظهور -->
            <div class="glass-card float-card absolute -top-3 end-1 sm:-top-6 sm:-end-4 lg:-end-8 w-28 sm:w-40 p-2 sm:p-3">
              <p class="text-[9px] sm:text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-0.5 sm:mb-1">{{ t("landing.hero.badge_billing") }}</p>
              <p v-if="cardsLoaded" class="text-[11px] sm:text-[13px] font-extrabold text-[#8A6D1F]">₪<span v-count-up="cardStats.monthlyRevenue">0</span></p>
              <LoaderCircle class="text-[#8A6D1F] text-[10px] sm:text-[11px] animate-spin" aria-hidden="true" v-else />
            </div>
            <div class="glass-card float-card delay-1 absolute -bottom-3 start-1 sm:-bottom-6 sm:-start-10 w-32 sm:w-44 p-2 sm:p-3">
              <p class="text-[9px] sm:text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-0.5 sm:mb-1">{{ t("landing.hero.badge_realtime") }}</p>
              <div class="flex items-center gap-1.5 sm:gap-2">
                <div class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></div>
                <p class="text-[11px] sm:text-[12px] font-bold">
                  <template v-if="cardsLoaded"><span v-count-up="cardStats.activeGenerators">0</span> {{ t("landing.hero.badge_realtime_unit") }}</template>
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-else />
                </p>
              </div>
            </div>
            <div class="glass-card float-card delay-2 absolute top-1/2 -start-6 sm:-start-14 w-32 p-2.5 hidden sm:block">
              <p class="text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-1">{{ t("landing.hero.badge_subscribers") }}</p>
              <p v-if="cardsLoaded" class="text-[13px] font-extrabold text-[#52733D] dark:text-[#8cc35a]">+<span v-count-up="cardStats.newSubscribers">0</span></p>
              <LoaderCircle class="text-[#52733D] text-[11px] animate-spin" aria-hidden="true" v-else />
            </div>
          </div>
        </div>
  
        <!-- موجة عضوية تفصل الهيرو عن باقي الصفحة -->
        <svg class="hero-diagonal-divider absolute bottom-0 inset-x-0 z-10" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path d="M0,30 C180,90 320,10 500,55 C680,100 780,20 960,60 C1140,100 1260,30 1440,55 L1440,120 L0,120 Z" />
        </svg>
  
        <!-- ===== نافذة اختيار لوحة التجربة (زائر) ===== -->
        <Teleport to="body">
          <Transition name="guest-modal-fade">
            <div v-if="showGuestModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#12140f]/60 backdrop-blur-md" @click.self="showGuestModal = false">
              <Transition name="guest-modal-pop" appear>
                <div
                  class="landing-shell relative w-full max-w-lg rounded-[1.75rem] overflow-hidden shadow-[0_30px_80px_rgba(0,0,0,0.35)]"
                  :class="{ dark: uiStore.isDark }"
                >
                  <!-- رأسية بتدرج مزخرف -->
                  <div class="relative bg-gradient-to-br from-[#3E582E] via-[#52733D] to-[#8A6D1F] px-5 sm:px-7 pt-6 sm:pt-7 pb-12 sm:pb-14 overflow-hidden">
                    <div class="absolute -top-10 -end-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="absolute -bottom-16 -start-10 w-40 h-40 rounded-full bg-[#D4AF37]/25 blur-2xl"></div>
  
                    <button :aria-label="t('common.close')"
                      type="button"
                      class="absolute top-5 end-5 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-white transition-colors"
                      @click="showGuestModal = false"
                    >
                      <X class="text-[12px]" aria-hidden="true" />
                    </button>
  
                    <div class="relative w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-4 shadow-lg">
                      <ClockAlert class="text-white text-xl" aria-hidden="true" />
                    </div>
                    <h3 class="relative text-[19px] font-extrabold text-white mb-1.5">{{ t("landing.hero.guest_modal_title") }}</h3>
                    <p class="relative text-[12.5px] text-white/80 leading-relaxed max-w-sm">{{ t("landing.hero.guest_modal_desc") }}</p>
                  </div>
  
                  <!-- المحتوى -->
                  <div class="bg-[#f8faf7] dark:bg-[#1c1e20] px-5 sm:px-7 pt-5 sm:pt-6 pb-6 sm:pb-7 -mt-8 rounded-t-[1.75rem] relative">
                    <div v-if="guestLoginError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl p-3 mb-4 flex items-center gap-2">
                      <CircleAlert class="shrink-0" aria-hidden="true" /> {{ guestLoginError }}
                    </div>
  
                    <div class="grid sm:grid-cols-2 gap-3.5">
                      <button
                        type="button"
                        :disabled="guestLoginState !== 'idle'"
                        class="group relative overflow-hidden text-center p-4 sm:p-5 rounded-2xl border border-[#e7e2d6] dark:border-white/10 bg-white dark:bg-white/[0.03] hover:border-[#52733D] hover:shadow-[0_14px_34px_rgba(82,115,61,0.18)] hover:-translate-y-1 transition-all duration-300 disabled:opacity-50 disabled:pointer-events-none"
                        @click="chooseGuestRole('subscriber')"
                      >
                        <div class="w-[52px] h-[52px] mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#3E582E] to-[#52733D] flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                          <LoaderCircle class="text-white animate-spin" aria-hidden="true" v-if="guestLoginState === 'loading-subscriber'" />
                          <House class="text-white text-lg" aria-hidden="true" v-else />
                        </div>
                        <p class="text-[13.5px] font-extrabold mb-1">{{ t("landing.hero.guest_subscriber_title") }}</p>
                        <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ t("landing.hero.guest_subscriber_desc") }}</p>
                      </button>
  
                      <button
                        type="button"
                        :disabled="guestLoginState !== 'idle'"
                        class="group relative overflow-hidden text-center p-4 sm:p-5 rounded-2xl border border-[#e7e2d6] dark:border-white/10 bg-white dark:bg-white/[0.03] hover:border-[#D4AF37] hover:shadow-[0_14px_34px_rgba(212,175,55,0.2)] hover:-translate-y-1 transition-all duration-300 disabled:opacity-50 disabled:pointer-events-none"
                        @click="chooseGuestRole('owner')"
                      >
                        <div class="w-[52px] h-[52px] mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                          <LoaderCircle class="text-white animate-spin" aria-hidden="true" v-if="guestLoginState === 'loading-owner'" />
                          <Factory class="text-white text-lg" aria-hidden="true" v-else />
                        </div>
                        <p class="text-[13.5px] font-extrabold mb-1">{{ t("landing.hero.guest_owner_title") }}</p>
                        <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ t("landing.hero.guest_owner_desc") }}</p>
                      </button>
                    </div>
  
                    <div class="flex items-center gap-2 mt-5 pt-5 border-t border-[#e7e2d6] dark:border-white/10">
                      <ShieldCheck class="text-[#8A6D1F] text-[11px] shrink-0" aria-hidden="true" />
                      <p class="text-[10.5px] text-[#9a9d97] leading-relaxed">{{ t("landing.hero.guest_modal_note") }}</p>
                    </div>
  
                    <button
                      type="button"
                      class="w-full mt-4 text-[12px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] hover:text-[#8A6D1F] flex items-center justify-center gap-1.5 transition-colors"
                      @click="showGuestModal = false"
                    >
                      <ArrowRight class="rtl:inline-block ltr:hidden" aria-hidden="true" /><ArrowLeft class="ltr:inline-block rtl:hidden" aria-hidden="true" />
                      {{ t("landing.hero.back_to_home") }}
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </Transition>
        </Teleport>
  
        <!-- ===== نافذة العرض التوضيحي ===== -->
        <Teleport to="body">
          <Transition name="guest-modal-fade">
            <div v-if="showDemoModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#12140f]/60 backdrop-blur-md" @click.self="showDemoModal = false">
              <Transition name="guest-modal-pop" appear>
                <div
                  class="landing-shell relative w-full max-w-2xl rounded-[1.75rem] overflow-hidden shadow-[0_30px_80px_rgba(0,0,0,0.35)]"
                  :class="{ dark: uiStore.isDark }"
                >
                  <!-- رأسية بتدرج مزخرف -->
                  <div class="relative bg-gradient-to-br from-[#3E582E] via-[#52733D] to-[#8A6D1F] px-5 sm:px-7 pt-6 sm:pt-7 pb-12 sm:pb-14 overflow-hidden">
                    <div class="absolute -top-10 -end-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="absolute -bottom-16 -start-10 w-40 h-40 rounded-full bg-[#D4AF37]/25 blur-2xl"></div>
  
                    <button :aria-label="t('common.close')"
                      type="button"
                      class="absolute top-5 end-5 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-white transition-colors"
                      @click="showDemoModal = false"
                    >
                      <X class="text-[12px]" aria-hidden="true" />
                    </button>
  
                    <div class="relative w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-4 shadow-lg">
                      <CirclePlay class="text-white text-xl" aria-hidden="true" />
                    </div>
                    <h3 class="relative text-[19px] font-extrabold text-white mb-1.5">{{ t("landing.hero.cta_demo") }}</h3>
                    <p class="relative text-[12.5px] text-white/80 leading-relaxed max-w-sm">{{ t("landing.hero.demo_modal_desc") }}</p>
                  </div>
  
                  <!-- المحتوى -->
                  <div class="bg-[#f8faf7] dark:bg-[#1c1e20] px-5 sm:px-7 pt-5 sm:pt-6 pb-6 sm:pb-7 -mt-8 rounded-t-[1.75rem] relative">
                    <div class="rounded-2xl bg-[#12140f] aspect-video flex flex-col items-center justify-center gap-3 text-white/70 border border-white/10">
                      <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center">
                        <Video class="text-xl" aria-hidden="true" />
                      </div>
                      <p class="text-[13px] font-semibold">{{ t("landing.hero.demo_coming_soon") }}</p>
                    </div>
  
                    <div class="flex items-center gap-2 mt-5 pt-5 border-t border-[#e7e2d6] dark:border-white/10">
                      <Lightbulb class="text-[#8A6D1F] text-[11px] shrink-0" aria-hidden="true" />
                      <p class="text-[10.5px] text-[#9a9d97] leading-relaxed">{{ t("landing.hero.demo_modal_note") }}</p>
                    </div>
  
                    <button
                      type="button"
                      class="w-full mt-4 text-[12px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] hover:text-[#8A6D1F] flex items-center justify-center gap-1.5 transition-colors"
                      @click="showDemoModal = false"
                    >
                      <ArrowRight class="rtl:inline-block ltr:hidden" aria-hidden="true" /><ArrowLeft class="ltr:inline-block rtl:hidden" aria-hidden="true" />
                      {{ t("landing.hero.back_to_home") }}
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </Transition>
        </Teleport>
      </section>
    </Transition>
</template>