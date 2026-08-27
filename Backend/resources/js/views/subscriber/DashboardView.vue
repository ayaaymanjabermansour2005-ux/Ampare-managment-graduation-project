<script setup>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useSubscriberDashboard } from "@/composables/useSubscriberDashboard";
import { vReveal } from "@/directives/reveal";
import { Bell, ChevronLeft, CircleAlert, CircleCheck, CreditCard, FilePenLine, Gauge, HandCoins, House, MessageCircleMore, PlugZap, TriangleAlert, Zap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();

const {
  subscription,
  activeSubscription,
  latestInvoice,
  outstandingTotal,
  generatorStatus,
  upcomingSchedule,
  recentNotifications,
  isLoading,
  error,
  load,
  extraStats,
  isLoadingExtraStats,
  extraStatsError,
  loadExtraStats,
} = useSubscriberDashboard();
const router = useRouter();

const STATUS_LABELS = computed(() => ({
  pending: t("subscriptions_page.status_pending"),
  active: t("subscriptions_page.status_active"),
  suspended: t("subscriptions_page.status_suspended"),
  cancelled: t("subscriptions_page.status_cancelled"),
  rejected: t("subscriptions_page.status_rejected"),
}));
const SCHEDULE_LABELS = computed(() => ({
  day: t("browse_generators_page.schedule_day"),
  night: t("browse_generators_page.schedule_night"),
  "24h": t("browse_generators_page.schedule_24h"),
  custom: t("browse_generators_page.schedule_custom"),
}));

const NOTIF_ICONS = {
  invoice: "fa-file-invoice",
  generator: "fa-bolt",
  complaint: "fa-comment-dots",
  service_request: "fa-clipboard-list",
  announcement: "fa-bullhorn",
};

function fmtMoney(n) {
  return "₪ " + Number(n ?? 0).toLocaleString("ar-EG");
}

onMounted(() => {
  load();
  loadExtraStats();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER + BREADCRUMB ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("common.home") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("subscriber_dashboard.breadcrumb") }}</span>
      </nav>

      <div class="relative">
        <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
            <House aria-hidden="true" />
          </span>
          {{ t("subscriber_dashboard.welcome") }}
        </h1>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
          {{ t("subscriber_dashboard.subtitle") }}
        </p>
      </div>
    </section>

    <!-- ===== LOADING ===== -->
    <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="h-40 rounded-2xl thumb-loading"></div>
    </div>

    <!-- ===== ERROR ===== -->
    <div v-else-if="error" class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
      <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
      {{ error }}
    </div>

    <template v-else>
      <!-- ===== KPI CARDS ===== -->
      <div v-if="extraStatsError" class="glass-card p-4 text-[12px] text-[#D9534F]">
        <CircleAlert class="me-1" aria-hidden="true" />{{ extraStatsError }}
      </div>

      <section v-if="isLoadingExtraStats" v-reveal class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="glass-card h-[76px] thumb-loading"></div>
      </section>

      <section v-else v-reveal class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- المبلغ المستحق -->
        <div class="glass-card kpi-card" style="--kpi-color:#D9534F;--kpi-color2:#8A2E2A">
          <span class="kpi-icon"><HandCoins aria-hidden="true" /></span>
          <p class="text-[11px] text-[#9a9d97] font-bold">{{ t("subscriber_dashboard.outstanding_label") }}</p>
          <p class="font-mono font-data text-lg font-extrabold">
            {{ outstandingTotal > 0 ? fmtMoney(outstandingTotal) : t("subscriber_dashboard.no_value") }}
          </p>
        </div>

        <!-- حالة الاشتراك -->
        <div class="glass-card kpi-card" style="--kpi-color:#52733D;--kpi-color2:#3E582E">
          <span class="kpi-icon"><FilePenLine aria-hidden="true" /></span>
          <p class="text-[11px] text-[#9a9d97] font-bold">{{ t("subscriptions_page.subscription_status_label") }}</p>
          <p class="text-[13.5px] font-extrabold">
            {{ subscription ? (STATUS_LABELS[subscription.status] ?? subscription.status) : t("subscriber_dashboard.no_value") }}
          </p>
        </div>

        <!-- موعد القراءة القادمة -->
        <div class="glass-card kpi-card" style="--kpi-color:#17A2B8;--kpi-color2:#0f6c7d">
          <span class="kpi-icon"><Gauge aria-hidden="true" /></span>
          <p class="text-[11px] text-[#9a9d97] font-bold">{{ t("subscriber_dashboard.next_reading_label") }}</p>
          <p class="font-mono text-[13.5px] font-extrabold">
            {{ subscription?.next_reading_due_date ?? "—" }}
          </p>
        </div>

        <!-- شكاوى مفتوحة -->
        <div class="glass-card kpi-card" style="--kpi-color:#D4AF37;--kpi-color2:#8A6D1F">
          <span class="kpi-icon"><MessageCircleMore aria-hidden="true" /></span>
          <p class="text-[11px] text-[#9a9d97] font-bold">{{ t("subscriber_dashboard.open_complaints_label") }}</p>
          <p class="font-mono text-lg font-extrabold">{{ extraStats?.open_complaints_count ?? 0 }}</p>
        </div>
      </section>

      <!-- ===== لا يوجد اشتراك بعد ===== -->
      <section v-if="!subscription" v-reveal class="glass-card text-center py-14 px-6">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center text-white text-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] shadow-lg">
          <Zap aria-hidden="true" />
        </div>
        <h3 class="text-[15px] font-extrabold mb-1.5">{{ t("subscriber_dashboard.no_subscription_title") }}</h3>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-5 max-w-sm mx-auto">
          {{ t("subscriber_dashboard.no_subscription_message") }}
        </p>
        <RouterLink
          :to="{ name: 'subscriber.subscription' }"
          class="btn-fill relative inline-flex items-center gap-2 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white px-6 py-2.5 rounded-full text-[12.5px] font-bold shadow-md"
        >
          <PlugZap aria-hidden="true" />
          {{ t("menu.browse_generators") }}
        </RouterLink>
      </section>

      <template v-else>
        <div v-reveal class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- ===== بطاقة الاشتراك ===== -->
          <div class="glass-card p-5">
            <div class="flex items-center justify-between mb-4">
              <p class="text-[11px] font-bold text-[#9a9d97]">{{ t("subscription_center_page.tab_subscription") }}</p>
              <span v-if="!activeSubscription" class="status-chip chip-warning">
                {{ STATUS_LABELS[subscription.status] ?? subscription.status }}
              </span>
              <span v-else class="status-chip chip-success">{{ t("subscriptions_page.status_active") }}</span>
            </div>

            <h3 class="font-extrabold text-[15px] flex items-center gap-2">
              <Zap class="text-[#8A6D1F] text-xs" aria-hidden="true" />
              {{ subscription.generator?.name }}
            </h3>

            <div class="grid grid-cols-2 gap-3 mt-4">
              <div class="info-row-simple flex-col !items-start">
                <span class="text-[10.5px]">{{ t("subscriber_dashboard.price_label") }}</span>
                <b class="font-mono font-data text-[13px]">
                  {{ subscription.agreed_price_per_kw }} {{ subscription.currency }}/kW
                </b>
              </div>
              <div class="info-row-simple flex-col !items-start">
                <span class="text-[10.5px]">{{ t("subscriber_dashboard.schedule_label") }}</span>
                <b class="text-[13px]">{{ SCHEDULE_LABELS[subscription.schedule] ?? subscription.schedule }}</b>
              </div>
            </div>

            <RouterLink
              :to="{ name: 'subscriber.subscription' }"
              class="inline-flex items-center gap-1.5 mt-4 text-[11.5px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline"
            >
              {{ t("subscriber_dashboard.view_full_details") }}
              <ChevronLeft class="text-[9px]" aria-hidden="true" />
            </RouterLink>
          </div>

          <!-- ===== بطاقة آخر فاتورة ===== -->
          <div class="glass-card p-5">
            <p class="text-[11px] font-bold text-[#9a9d97] mb-4">{{ t("subscriber_dashboard.latest_invoice_title") }}</p>

            <template v-if="latestInvoice">
              <p class="font-mono font-data text-2xl font-extrabold">
                {{ latestInvoice.final_amount }}
                <span class="text-sm font-sans text-[#9a9d97]">{{ latestInvoice.currency }}</span>
              </p>

              <p v-if="outstandingTotal > 0" class="status-chip chip-warning mt-2">
                <TriangleAlert aria-hidden="true" />
                {{ t("subscriber_dashboard.remaining_total", { amount: outstandingTotal.toFixed(2) }) }}
              </p>
              <p v-else class="status-chip chip-success mt-2">
                <CircleCheck aria-hidden="true" />
                {{ t("subscriber_dashboard.no_outstanding_amount") }}
              </p>

              <RouterLink
                v-if="['pending', 'partially_paid', 'overdue'].includes(latestInvoice.status)"
                :to="{ name: 'payments.gateway', params: { id: latestInvoice.id } }"
                class="btn-fill relative inline-flex items-center gap-2 mt-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white px-5 py-2 rounded-full text-[11.5px] font-bold shadow-md"
              >
                <CreditCard aria-hidden="true" />
                {{ t("payment_gateway.pay_now") }}
              </RouterLink>
              <RouterLink
                v-else
                :to="{ name: 'subscriber.invoices' }"
                class="inline-flex items-center gap-1.5 mt-4 text-[11.5px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline"
              >
                {{ t("subscriber_dashboard.view_all_invoices") }}
                <ChevronLeft class="text-[9px]" aria-hidden="true" />
              </RouterLink>
            </template>
            <p v-else class="text-[12.5px] text-[#9a9d97]">{{ t("subscriber_dashboard.no_invoices_yet") }}</p>
          </div>

          <!-- ===== بطاقة حالة المولد ===== -->
          <div class="glass-card p-5">
            <p class="text-[11px] font-bold text-[#9a9d97] mb-4">{{ t("subscriber_dashboard.generator_status_title") }}</p>

            <template v-if="generatorStatus">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-3 h-3 rounded-full shrink-0"
                  :class="generatorStatus.is_running ? 'bg-[#28A745] status-dot-live' : 'bg-[#c9cdc2]'"
                  style="color:#28A745"
                ></span>
                <p class="font-extrabold text-[13.5px]">
                  {{ generatorStatus.is_running ? t("subscriber_dashboard.generator_running") : t("subscriber_dashboard.generator_stopped") }}
                </p>
              </div>

              <p v-if="generatorStatus.next_change_at" class="text-[11.5px] text-[#9a9d97] mt-2.5">
                {{ generatorStatus.is_running ? t("subscriber_dashboard.expected_stop_time") : t("subscriber_dashboard.expected_start_time") }}:
                <span class="font-mono text-[#3d3d3d] dark:text-[#e5e5e5]">{{ generatorStatus.next_change_at }}</span>
              </p>

              <RouterLink
                :to="{ name: 'subscriber.generator' }"
                class="inline-flex items-center gap-1.5 mt-4 text-[11.5px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline"
              >
                {{ t("subscriber_dashboard.view_generator_schedule") }}
                <ChevronLeft class="text-[9px]" aria-hidden="true" />
              </RouterLink>
            </template>
            <p v-else class="text-[12.5px] text-[#9a9d97]">{{ t("subscriber_dashboard.no_generator_status") }}</p>
          </div>

          <!-- ===== بطاقة جدول التشغيل القادم ===== -->
          <div class="glass-card p-5">
            <p class="text-[11px] font-bold text-[#9a9d97] mb-4">{{ t("subscriber_dashboard.upcoming_schedule_title") }}</p>

            <ul v-if="upcomingSchedule?.length" class="space-y-2.5">
              <li
                v-for="(slot, i) in upcomingSchedule.slice(0, 3)"
                :key="i"
                class="info-row-simple"
              >
                <span>{{ slot.label }}</span>
                <b class="font-mono text-[12.5px]">{{ slot.starts_at }} – {{ slot.ends_at }}</b>
              </li>
            </ul>
            <p v-else class="text-[12.5px] text-[#9a9d97]">{{ t("subscriber_dashboard.no_upcoming_schedule") }}</p>
          </div>
        </div>

        <!-- ===== آخر الإشعارات ===== -->
        <section v-reveal class="glass-card overflow-hidden">
          <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#eee8da] dark:border-white/10">
            <h3 class="font-extrabold text-[13px] flex items-center gap-2">
              <Bell class="text-[#8A6D1F] text-xs" aria-hidden="true" />
              {{ t("subscriber_dashboard.recent_notifications_title") }}
            </h3>
            <RouterLink
              :to="{ name: 'subscriber.notifications' }"
              class="text-[11.5px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline"
            >
              {{ t("common.view_all") }}
            </RouterLink>
          </div>

          <div v-if="recentNotifications?.length">
            <div
              v-for="notif in recentNotifications"
              :key="notif.id"
              class="dropdown-row"
              :class="{ unread: !notif.read_at }"
            >
              <div class="w-8 h-8 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#52733D] dark:text-[#8cc35a] shrink-0">
                <AppIcon :name="NOTIF_ICONS[notif.type] ?? 'fa-bell'" class="text-xs" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-[12.5px] font-semibold truncate">{{ notif.title }}</p>
                <p class="text-[10.5px] text-[#9a9d97] mt-0.5">{{ notif.created_at }}</p>
              </div>
              <span v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-[#52733D] shrink-0 mt-1.5"></span>
            </div>
          </div>
          <div v-else class="dropdown-empty">{{ t("subscriber_dashboard.no_notifications_yet") }}</div>
        </section>
      </template>
    </template>
  </div>
</template>