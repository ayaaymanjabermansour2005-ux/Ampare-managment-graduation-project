<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { RouterLink, RouterView } from "vue-router";
import { useI18n } from "vue-i18n";
import { motion, AnimatePresence, useReducedMotion } from "motion-v";
import { setLocale } from "@/i18n";
import { useThemeSync } from "@/composables/useThemeSync";
import { useScrollToSection } from "@/composables/useScrollToSection";
import { usePlatformIdentityStore } from "@/stores/platformIdentity";
import { ArrowUp, Mail, MapPin, Menu, Moon, Phone, Sun, X } from "@lucide/vue";


const { t, locale } = useI18n();
const uiStore = useThemeSync();
const prefersReducedMotion = useReducedMotion();
const platformIdentity = usePlatformIdentityStore();
onMounted(() => platformIdentity.fetch());

/* ---------------- ستارة دخول Landing بالكامل (Navbar + Hero + كل الأقسام) ----------------
 * تشتغل مرة وحدة لكل mount فعلي لـ LandingLayout — يعني أول ما تدخل أي صفحة
 * Landing قادم من خارجها (بعد الـ Splash، أو من route تاني متل /login). طالما
 * LandingLayout نفسه ما انعمل unmount (تنقّل بين صفحات Landing الداخلية:
 * الرئيسية/المدونة/المقال/البث المباشر)، العنصر ما بيتكرّر لأنه onMounted ما
 * بيرجع يشتغل. الحركة نفسها CSS transition بحتة (leave transition من Vue
 * Transition)، فما في أي setTimeout أو rAF loop متحكم فيها. */
const showEntrance = ref(!prefersReducedMotion.value);
onMounted(() => {
  if (showEntrance.value) showEntrance.value = false;
});

function toggleTheme() {
  uiStore.toggleTheme();
}
function toggleLanguage() {
  setLocale(locale.value === "ar" ? "en" : "ar");
}

const NAV_LINKS = computed(() => [
  { label: t("landing.nav.home"), sectionId: "home" },
  { label: t("landing.nav.story"), sectionId: "story" },
  { label: t("landing.nav.why"), sectionId: "why" },
  { label: t("landing.nav.features"), sectionId: "features" },
  { label: t("landing.nav.roles"), sectionId: "roles" },
  { label: t("landing.nav.services"), sectionId: "services" },
  { label: t("landing.nav.map"), sectionId: "map" },
  { label: t("landing.nav.testimonials"), sectionId: "testimonials" },
  { label: t("landing.nav.blog"), sectionId: "blog" },
  { label: t("landing.nav.faq"), sectionId: "faq" },
  { label: t("landing.nav.contact"), sectionId: "contact" },
]);

// FIX: (item 11) كانت هذه الدالة تنادي document.getElementById مباشرة —
// شغّالة فقط طالما المستخدم أصلًا على صفحة landing.home (حيث توجد عناصر
// الأقسام). LandingLayout هذا مشترك بين HomeView وArticlesListView/
// ArticleDetailView/LiveScheduleView أيضًا — فالنقر على "تواصل معنا" أو
// "المزايا" من صفحة مقال مثلًا كان لا يفعل شيئًا بصمت (العنصر غير موجود
// بتلك الصفحة). composables/useScrollToSection.js كان مبنيًا بالضبط لحل
// هذه الحالة (ينتقل لـ landing.home أولًا ثم يمرّر) لكنه كان معزولًا بدون
// أي استخدام — وُصل هون الآن.
const { scrollToSection } = useScrollToSection();

const isScrolled = ref(false);
/* ---------------- زر الرجوع لأعلى + حلقة تقدّم القراءة ----------------
 * نفس مستمع scroll الموجود أصلًا (passive، واحد فقط للصفحة كاملة) — لا داعي
 * لمستمع ثانٍ منفصل لكل ميزة scroll-based. */
const showBackToTop = ref(false);
const scrollProgress = ref(0); // 0..1
function onScroll() {
  isScrolled.value = window.scrollY > 20;

  showBackToTop.value = window.scrollY > 480;

  const doc = document.documentElement;
  const scrollable = doc.scrollHeight - doc.clientHeight;
  scrollProgress.value = scrollable > 0 ? Math.min(1, Math.max(0, window.scrollY / scrollable)) : 0;
}
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: prefersReducedMotion.value ? "auto" : "smooth" });
}

const RING_RADIUS = 15.5;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;
const ringDashoffset = computed(() => RING_CIRCUMFERENCE * (1 - scrollProgress.value));

onMounted(() => {
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();
});
onUnmounted(() => window.removeEventListener("scroll", onScroll));

const isMobileOpen = ref(false);
function handleMobileNavClick(id) {
  isMobileOpen.value = false;
  scrollToSection(id);
}
</script>

<template>
  <div
    class="landing-shell min-h-screen flex flex-col bg-[#f8faf7] dark:bg-[#0f1112] text-[#333] dark:text-[#eee] transition-colors duration-500"
    :class="{ dark: uiStore.isDark }"
    :dir="locale === 'ar' ? 'rtl' : 'ltr'"
  >
    <Transition name="landing-entrance">
      <div v-if="showEntrance" class="landing-entrance-overlay" aria-hidden="true"></div>
    </Transition>

    <!-- ===================== NAVBAR ===================== -->
    <!-- Full-bleed 3-zone grid header: brand / centered nav / actions. Grid (not flex+justify-between)
         so the center nav stays truly centered on the viewport regardless of how wide the brand and
         actions zones are — and explicit grid-column indices keep that true even when the center nav
         is `hidden` below xl (a display:none item drops out of grid auto-placement entirely, which
         would otherwise shove the actions zone into the middle column). -->
    <motion.header
      class="landing-nav fixed top-0 inset-x-0 z-50 transition-colors duration-300"
      :class="isScrolled ? 'landing-nav--scrolled' : 'landing-nav--top'"
      :initial="prefersReducedMotion ? false : { y: -20, opacity: 0 }"
      :animate="{ y: 0, opacity: 1 }"
      :transition="{ duration: prefersReducedMotion ? 0 : 0.55, ease: [0.16, 1, 0.3, 1] }"
    >
      <div class="landing-nav-grid px-5 sm:px-8 lg:px-10 py-3">
        <button type="button" class="landing-nav-brand" @click="scrollToSection('home')">
          <div class="w-14 h-14 flex items-center justify-center overflow-hidden">
            <img :src="platformIdentity.logoUrl || '/images/logo.png'" :alt="t('auth.logo_alt')" class="w-full h-full object-contain" onerror="this.style.display='none'" />
          </div>
          <span class="text-xl font-extrabold brand-gradient-text">{{ platformIdentity.siteName || t("auth.brand_name") }}</span>
        </button>

        <nav class="landing-nav-center hidden xl:flex items-center gap-5 text-[14.5px] font-semibold text-[#444] dark:text-[#cfd2cb]">
          <motion.button
            v-for="link in NAV_LINKS"
            :key="link.sectionId"
            type="button"
            class="landing-nav-link hover:text-[#8A6D1F] dark:hover:text-[#F4E0A5] transition-colors"
            :while-hover="prefersReducedMotion ? {} : { y: -2 }"
            :while-tap="prefersReducedMotion ? {} : { scale: 0.96 }"
            @click="scrollToSection(link.sectionId)"
          >
            {{ link.label }}
          </motion.button>
        </nav>

        <div class="landing-nav-actions flex items-center gap-2">
          <div class="icon-btn-group">
            <button type="button" class="icon-btn" :title="t('auth.toggle_theme')" @click="toggleTheme">
              <Sun aria-hidden="true" v-if="uiStore.isDark" /><Moon aria-hidden="true" v-else />
            </button>
            <button type="button" class="icon-btn text-[10px] font-bold" :title="t('auth.toggle_language')" @click="toggleLanguage">
              {{ locale === "ar" ? "EN" : "AR" }}
            </button>
          </div>

          <RouterLink
            :to="{ name: 'login' }"
            class="hidden sm:inline-block btn-outline-fill font-bold text-[13px] px-4 py-2 rounded-full"
          >
            {{ t("landing.nav.login") }}
          </RouterLink>
          <RouterLink
            :to="{ name: 'register' }"
            class="hidden sm:inline-block btn-fill bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[13px] font-bold px-4 py-2 rounded-full shadow-md hover:-translate-y-0.5 transition-transform duration-300"
          >
            {{ t("landing.nav.get_started") }}
          </RouterLink>

          <button :aria-label="isMobileOpen ? $t('common.close_menu') : $t('common.open_menu')" type="button" class="icon-btn xl:hidden" @click="isMobileOpen = !isMobileOpen">
            <X aria-hidden="true" v-if="isMobileOpen" /><Menu aria-hidden="true" v-else />
          </button>
        </div>
      </div>

      <AnimatePresence>
        <motion.div
          v-if="isMobileOpen"
          key="mobile-nav"
          class="xl:hidden landing-nav--scrolled border-t border-[#eee8da] dark:border-white/10 px-5 py-4 space-y-1 max-h-[calc(100vh-4rem)] overflow-y-auto"
          :initial="prefersReducedMotion ? false : { opacity: 0, height: 0 }"
          :animate="{ opacity: 1, height: 'auto' }"
          :exit="prefersReducedMotion ? { opacity: 0 } : { opacity: 0, height: 0 }"
          :transition="{ duration: prefersReducedMotion ? 0 : 0.28, ease: 'easeOut' }"
          style="overflow: hidden"
        >
          <button
            v-for="link in NAV_LINKS"
            :key="link.sectionId"
            type="button"
            class="block w-full text-start px-3 py-2.5 rounded-lg text-[13px] font-semibold hover:bg-[#f4efe5]/60 dark:hover:bg-white/5"
            @click="handleMobileNavClick(link.sectionId)"
          >
            {{ link.label }}
          </button>
          <div class="flex items-center gap-2 pt-3 mt-2 border-t border-[#e2e2e2] dark:border-white/10 sm:hidden">
            <RouterLink :to="{ name: 'login' }" class="flex-1 text-center text-[13px] font-semibold px-3 py-2.5 rounded-full border border-[#D4AF37]/40">
              {{ t("landing.nav.login") }}
            </RouterLink>
            <RouterLink :to="{ name: 'register' }" class="flex-1 text-center bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[13px] font-bold px-3 py-2.5 rounded-full shadow-md">
              {{ t("landing.nav.get_started") }}
            </RouterLink>
          </div>
        </motion.div>
      </AnimatePresence>
    </motion.header>

    <main class="flex-1">
      <RouterView v-slot="{ Component, route }">
        <Transition name="page-fade" mode="out-in">
          <component :is="Component" :key="route.path" />
        </Transition>
      </RouterView>
    </main>

    <!-- ===================== FOOTER ===================== -->
    <footer class="bg-gradient-to-b from-[#1b2116] to-[#101208] text-[#c8cac5] pt-16 pb-8 relative overflow-hidden">
      <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-l from-transparent via-[#D4AF37]/50 to-transparent"></div>
      <div class="absolute -top-24 start-1/4 w-72 h-72 bg-[#52733D]/10 rounded-full blur-[110px] pointer-events-none"></div>
      <div class="absolute -top-24 end-1/4 w-72 h-72 bg-[#D4AF37]/10 rounded-full blur-[110px] pointer-events-none"></div>
      <div class="max-w-7xl mx-auto px-5 sm:px-8 relative">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 pb-12 border-b border-white/10">
          <div>
            <div class="flex items-center gap-2.5 mb-4">
              <div class="w-12 h-12 flex items-center justify-center overflow-hidden">
                <img :src="platformIdentity.logoUrl || '/images/logo.png'" :alt="t('auth.logo_alt')" class="w-full h-full object-contain" onerror="this.style.display='none'" />
              </div>
              <span class="text-lg font-extrabold text-[#F4E0A5]">{{ platformIdentity.siteName || t("auth.brand_name") }}</span>
            </div>
            <p class="text-[13px] leading-relaxed text-[#9a9d97] mb-5">{{ t("landing.footer.description") }}</p>
            <!-- No Ampere-specific social accounts exist yet — these link to each
                 platform's own generic public homepage (not a fabricated Ampere
                 profile) per explicit product decision, pending real account
                 links from the business. -->
            <div class="flex items-center gap-2">
              <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 transition-colors flex items-center justify-center"><i class="fab fa-facebook-f text-xs"></i></a>
              <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 transition-colors flex items-center justify-center"><i class="fab fa-instagram text-xs"></i></a>
              <a href="https://whatsapp.com" target="_blank" rel="noopener" aria-label="WhatsApp" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 transition-colors flex items-center justify-center"><i class="fab fa-whatsapp text-xs"></i></a>
              <a href="https://x.com" target="_blank" rel="noopener" aria-label="X (Twitter)" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 transition-colors flex items-center justify-center"><i class="fab fa-x-twitter text-xs"></i></a>
            </div>
          </div>

          <div>
            <h4 class="font-bold text-[13px] text-white mb-4">{{ t("landing.footer.quick_links") }}</h4>
            <ul class="space-y-2.5 text-[12.5px]">
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('home')">{{ t("landing.nav.home") }}</button></li>
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('story')">{{ t("landing.nav.story") }}</button></li>
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('features')">{{ t("landing.nav.features") }}</button></li>
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('services')">{{ t("landing.nav.services") }}</button></li>
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('map')">{{ t("landing.nav.map") }}</button></li>
            </ul>
          </div>

          <div>
            <h4 class="font-bold text-[13px] text-white mb-4">{{ t("landing.footer.support") }}</h4>
            <ul class="space-y-2.5 text-[12.5px]">
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('blog')">{{ t("landing.nav.blog") }}</button></li>
              <li><RouterLink :to="{ name: 'landing.live-schedule' }" class="hover:text-[#F4E0A5]">{{ t("landing.nav.live_schedule") }}</RouterLink></li>
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('faq')">{{ t("landing.nav.faq") }}</button></li>
              <li><button type="button" class="hover:text-[#F4E0A5]" @click="scrollToSection('contact')">{{ t("landing.nav.contact") }}</button></li>
              <li><RouterLink :to="{ name: 'login' }" class="hover:text-[#F4E0A5]">{{ t("landing.nav.login") }}</RouterLink></li>
              <li><RouterLink :to="{ name: 'register' }" class="hover:text-[#F4E0A5]">{{ t("landing.nav.get_started") }}</RouterLink></li>
            </ul>
          </div>

          <div>
            <h4 class="font-bold text-[13px] text-white mb-4">{{ t("landing.nav.contact") }}</h4>
            <ul class="space-y-3 text-[12.5px]">
              <li class="flex items-center gap-2"><Phone class="text-[#8A6D1F]" aria-hidden="true" /> <span dir="ltr">+970 59 123 4567</span></li>
              <li class="flex items-center gap-2"><Mail class="text-[#8A6D1F]" aria-hidden="true" /> <span dir="ltr">support@ampir.ps</span></li>
              <li class="flex items-center gap-2"><MapPin class="text-[#8A6D1F]" aria-hidden="true" /> {{ t("landing.contact.info.location_value") }}</li>
            </ul>
          </div>
        </div>

        <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11.5px] text-[#8a8f83]">
          <p>{{ t("landing.footer.copyright", { year: new Date().getFullYear() }) }}</p>
          <div class="flex items-center gap-4">
            <RouterLink :to="{ name: 'landing.privacy-policy' }" class="hover:text-[#F4E0A5]">{{ t("landing.footer.privacy") }}</RouterLink>
            <RouterLink :to="{ name: 'landing.terms-of-service' }" class="hover:text-[#F4E0A5]">{{ t("landing.footer.terms") }}</RouterLink>
          </div>
        </div>
      </div>
    </footer>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <button
        v-if="showBackToTop"
        type="button"
        class="back-to-top-btn"
        :aria-label="t('landing.back_to_top')"
        @click="scrollToTop"
      >
        <svg class="back-to-top-btn__ring" viewBox="0 0 36 36" aria-hidden="true">
          <circle class="back-to-top-btn__ring-track" cx="18" cy="18" r="15.5" />
          <circle
            class="back-to-top-btn__ring-progress"
            cx="18" cy="18" r="15.5"
            :stroke-dasharray="RING_CIRCUMFERENCE"
            :stroke-dashoffset="ringDashoffset"
          />
        </svg>
        <ArrowUp class="back-to-top-btn__icon" aria-hidden="true" />
      </button>
    </Transition>
  </div>
</template>