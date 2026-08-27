<script setup>
import { ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { ChevronLeft } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


import SubscriptionBrowsePanel from "@/components/subscription/SubscriptionBrowsePanel.vue";
import SubscriptionDetailsPanel from "@/components/subscription/SubscriptionDetailsPanel.vue";

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

/* ==================== تبويبات ==================== */
const TABS = computed(() => [
  { key: "browse", label: t("subscription_center_page.tab_browse"), icon: "fa-plug-circle-bolt" },
  { key: "subscription", label: t("subscription_center_page.tab_subscription"), icon: "fa-file-signature" },
]);

const validTabKeys = TABS.value.map((tab) => tab.key);

/* لو المستخدم جاي من رابط "اشترك" باللاندنج بيج (?highlight=ID)، لازم
 * نفتحه على تاب "تصفح المولدات" مباشرة حتى تشتغل ?highlight= متل ما هي. */
const initialTab = route.query.highlight
  ? "browse"
  : validTabKeys.includes(route.query.tab)
    ? route.query.tab
    : "subscription";

const activeTab = ref(initialTab);

function setTab(key) {
  activeTab.value = key;
  router.replace({ query: { ...route.query, tab: key } });
}

/* ==================== حالة الاشتراك — تجي من تاب "اشتراكي الحالي" ====================
 * منستخدمها لتلوين الهيدر ونقطة التنبيه فوق التاب، وكمان لتبديل التاب
 * تلقائيًا لـ"تصفح المولدات" أول ما نعرف إنه ما في اشتراك فعّال/معلّق —
 * هيك مثل سلوك التوجيه (redirect) القديم بالضبط بس بدون تغيير رابط. */
const subscriptionStatus = ref(null);
const hasActiveOrPendingSubscription = ref(false);
const detailsLoaded = ref(false);
let autoSwitchedAlready = false;

function onDetailsLoaded(payload) {
  subscriptionStatus.value = payload.status;
  hasActiveOrPendingSubscription.value = payload.hasActiveOrPendingSubscription;
  detailsLoaded.value = !payload.isLoading;

  const noExplicitTab = !route.query.tab && !route.query.highlight;
  if (
    detailsLoaded.value &&
    !autoSwitchedAlready &&
    noExplicitTab &&
    activeTab.value === "subscription" &&
    !hasActiveOrPendingSubscription.value
  ) {
    autoSwitchedAlready = true;
    activeTab.value = "browse";
  }
}

function onSubscribed() {
  // لما يخلص المستخدم يشترك، منروّح مباشرة لتاب "اشتراكي" حتى يشوف حالة الطلب
  setTab("subscription");
}

/* ==================== لون/شارة الهيدر ==================== */
const accent = computed(() => {
  if (!detailsLoaded.value) return { hex: "#52733D", glow: "rgba(82,115,61,0.2)", icon: "fa-bolt" };
  if (subscriptionStatus.value === "rejected") return { hex: "#D9534F", glow: "rgba(217,83,79,0.22)", icon: "fa-triangle-exclamation" };
  if (subscriptionStatus.value === "pending") return { hex: "#D4AF37", glow: "rgba(212,175,55,0.22)", icon: "fa-hourglass-half" };
  return { hex: "#52733D", glow: "rgba(82,115,61,0.2)", icon: "fa-bolt" };
});

/* شارة صغيرة (نقطة حمراء/ذهبية) فوق تاب "اشتراكي الحالي" لما يحتاج انتباه */
const tabHasAlert = (key) => {
  if (key === "subscription") return subscriptionStatus.value === "pending" || subscriptionStatus.value === "rejected";
  return false;
};
</script>

<template>
  <div class="space-y-6">
    <!-- ===================== رأس الصفحة + تبويبات ===================== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div
        class="absolute -start-16 -top-16 w-72 h-72 rounded-full blur-[100px] pointer-events-none transition-colors duration-500"
        :style="{ background: accent.glow }"
      ></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("common.account_breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("subscription_center_page.breadcrumb") }}</span>
      </nav>

      <div class="relative flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold flex items-center gap-2.5 mb-1.5">
            <span
              class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-base transition-colors duration-500"
              :style="{ background: `linear-gradient(135deg, ${accent.hex}, #3E582E)` }"
            >
              <AppIcon :name="accent.icon" />
            </span>
            {{ t("subscription_center_page.breadcrumb") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] max-w-lg">
            {{ t("subscription_center_page.subtitle") }}
          </p>
        </div>

        <!-- تبديل التابات -->
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 w-fit">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            @click="setTab(tab.key)"
            class="relative flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
            :class="
              activeTab === tab.key
                ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
            "
          >
            <AppIcon :name="tab.icon" />
            {{ tab.label }}
            <span
              v-if="tabHasAlert(tab.key)"
              class="absolute -top-1 -end-1 w-2.5 h-2.5 rounded-full bg-[#D9534F] border-2 border-white dark:border-[#1c1e20]"
            ></span>
          </button>
        </div>
      </div>
    </section>

    <!-- ===================== محتوى التابات (v-show يحافظ على الحالة) ===================== -->
    <SubscriptionBrowsePanel v-show="activeTab === 'browse'" @subscribed="onSubscribed" />

    <SubscriptionDetailsPanel v-show="activeTab === 'subscription'" @loaded="onDetailsLoaded" />
  </div>
</template>