<script setup>
import { ChevronLeft } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useOwnerComplaints } from "@/composables/useOwnerComplaints";
import { useSubscriberFaults } from "@/composables/useSubscriberFaults";
import { useServiceRequests } from "@/composables/useServiceRequests";
import { vReveal } from "@/directives/reveal";

import SupportComplaintsPanel from "@/components/support/SupportComplaintsPanel.vue";
import SupportFaultsPanel from "@/components/support/SupportFaultsPanel.vue";
import SupportServiceRequestsPanel from "@/components/support/SupportServiceRequestsPanel.vue";

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

/* ==================== تبويبات ==================== */
const TABS = computed(() => [
  { key: "complaints", label: t("menu.complaints"), icon: "fa-comment-dots" },
  { key: "faults", label: t("menu.faults"), icon: "fa-bolt" },
  { key: "requests", label: t("menu.service_requests"), icon: "fa-hand-holding-hand" },
]);

const validTabKeys = TABS.value.map((tab) => tab.key);
const activeTab = ref(validTabKeys.includes(route.query.tab) ? route.query.tab : "complaints");

function setTab(key) {
  activeTab.value = key;
  router.replace({ query: { ...route.query, tab: key } });
}

/* ==================== الشكاوى ==================== */
const complaints = useOwnerComplaints();

/* ==================== الأعطال ==================== */
const faults = useSubscriberFaults();

/* ==================== طلبات خدمة إضافية ==================== */
const serviceRequests = useServiceRequests();

/* ==================== لون/شارة إجمالية للهيدر ====================
 * تعكس أخطر حالة موجودة عبر التابات الثلاثة كلها (لا التاب النشط فقط)،
 * حتى ينتبه المستخدم لوجود بند يحتاج تصرف حتى لو كان واقف بتاب تاني. */
const hasAnyRejected = computed(
  () =>
    faults.faults.value.some((f) => f.status === "rejected") ||
    serviceRequests.requests.value.some((r) => r.status === "rejected"),
);
const hasAnyPending = computed(
  () =>
    complaints.complaints.value.some((c) => c.status === "pending") ||
    faults.faults.value.some((f) => f.status === "pending_verification") ||
    serviceRequests.requests.value.some((r) => r.status === "pending"),
);

const accent = computed(() => {
  if (hasAnyRejected.value) return { hex: "#D9534F", glow: "rgba(217,83,79,0.22)", icon: "fa-triangle-exclamation" };
  if (hasAnyPending.value) return { hex: "#D4AF37", glow: "rgba(212,175,55,0.22)", icon: "fa-hourglass-half" };
  return { hex: "#52733D", glow: "rgba(82,115,61,0.2)", icon: "fa-headset" };
});

/* شارة صغيرة لكل تاب (نقطة حمراء) — تدل إذا فيه مرفوض داخل ذاك التاب تحديدًا */
const tabHasAlert = (key) => {
  if (key === "complaints") return complaints.complaints.value.some((c) => c.status === "pending");
  if (key === "faults") return faults.faults.value.some((f) => f.status === "rejected");
  if (key === "requests") return serviceRequests.requests.value.some((r) => r.status === "rejected");
  return false;
};

/* ==================== تحميل أولي لكل التابات دفعة وحدة ====================
 * كل الـ composables تُحمَّل عند mount الحاوية (مو عند فتح كل تاب لحاله)،
 * لأن البيانات خفيفة الحجم لمستخدم واحد (مشترك)، والفايدة إظهار شارات
 * التنبيه الصحيحة فورًا فوق التابات غير النشطة بدون انتظار المستخدم يفتحها. */
onMounted(async () => {
  await Promise.all([
    complaints.fetchComplaints(1),
    faults.loadMyGenerator().then(() => faults.fetchFaults()),
    serviceRequests.loadMySubscription().then(() => serviceRequests.fetchRequests()),
  ]);
});
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("menu.support_center") }}</span>
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
            {{ t("menu.support_center") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] max-w-lg">
            {{ t("support_center_page.subtitle") }}
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
    <SupportComplaintsPanel
      v-show="activeTab === 'complaints'"
      :complaints="complaints.complaints.value"
      :pagination="complaints.pagination.value"
      :is-loading="complaints.isLoading.value"
      :error="complaints.error.value"
      :has-complaints="complaints.hasComplaints.value"
      :is-submitting-new="complaints.isSubmittingNew.value"
      :new-complaint-error="complaints.newComplaintError.value"
      @submit="complaints.submitComplaint($event)"
      @paginate="complaints.fetchComplaints($event)"
    />

    <SupportFaultsPanel
      v-show="activeTab === 'faults'"
      :faults="faults.faults.value"
      :is-loading="faults.isLoading.value"
      :error="faults.error.value"
      :my-generator-id="faults.myGeneratorId.value"
      :my-generator-name="faults.myGeneratorName.value"
      :is-submitting="faults.isSubmitting.value"
      :submit-error="faults.submitError.value"
      @submit="faults.reportFault($event)"
    />

    <SupportServiceRequestsPanel
      v-show="activeTab === 'requests'"
      :requests="serviceRequests.requests.value"
      :is-loading="serviceRequests.isLoading.value"
      :error="serviceRequests.error.value"
      :my-subscription-id="serviceRequests.mySubscriptionId.value"
      :is-submitting="serviceRequests.isSubmitting.value"
      :submit-error="serviceRequests.submitError.value"
      :cancelling-id="serviceRequests.cancellingId.value"
      @submit="serviceRequests.submitRequest($event)"
      @cancel="serviceRequests.cancelRequest($event)"
    />
  </div>
</template>