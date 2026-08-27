<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import "dayjs/locale/ar";
import AppIcon from "@/components/ui/AppIcon.vue";

dayjs.extend(relativeTime);

const props = defineProps({
  notification: { type: Object, required: true },
});

const emit = defineEmits(["read", "close"]);
const router = useRouter();
const authStore = useAuthStore();
const { locale } = useI18n();

const timeAgo = computed(() => dayjs(props.notification.created_at).locale(locale.value).fromNow());

const NOTIF_ICONS = {
  FaultReportedNotification: { icon: "fa-triangle-exclamation", color: "#D9534F" },
  FuelStockLowNotification: { icon: "fa-gas-pump", color: "#D9534F" },
  InvoiceDueSoonNotification: { icon: "fa-file-invoice-dollar", color: "#FFC107" },
  InvoicePaidNotification: { icon: "fa-wallet", color: "#28A745" },
  InvoiceApprovedNotification: { icon: "fa-file-invoice", color: "#28A745" },
  PaymentSubmittedNotification: { icon: "fa-wallet", color: "#17A2B8" },
  ComplaintResolvedNotification: { icon: "fa-comment-dots", color: "#17A2B8" },
  GeneratorHealthReportNotification: { icon: "fa-heart-pulse", color: "#8A6D1F" },
  GeneratorVerifiedNotification: { icon: "fa-circle-check", color: "#28A745" },
  GeneratorRejectedNotification: { icon: "fa-circle-xmark", color: "#D9534F" },
  SubscriptionApprovedNotification: { icon: "fa-file-circle-check", color: "#28A745" },
  TechnicianTaskAssignedNotification: { icon: "fa-user-helmet-safety", color: "#FFC107" },
  TechnicianTaskSubmittedNotification: { icon: "fa-screwdriver-wrench", color: "#52733D" },
  TechnicianTaskApprovedNotification: { icon: "fa-circle-check", color: "#28A745" },
  TechnicianTaskRejectedNotification: { icon: "fa-circle-xmark", color: "#D9534F" },
  GeneratorScheduleAnnouncedNotification: { icon: "fa-calendar-day", color: "#8A6D1F" },
};
const notifMeta = computed(() => NOTIF_ICONS[props.notification.type] || { icon: "fa-bell", color: "#52733D" });

const LINK_ROUTE_MAP = {
  invoice: {
    subscriber: "subscriber.invoices",
    generator_owner: "owner.invoices",
  },
  payment: { generator_owner: "owner.payments" },
  complaint: { subscriber: "subscriber.complaints" },
  fault: { generator_owner: "owner.generators" },
  subscription: { subscriber: "subscriber.subscription" },
  conversation: null,
};

function resolveRouteName(linkType) {
  const rolesMap = LINK_ROUTE_MAP[linkType];
  if (!rolesMap) return null;

  const matchedRole = Object.keys(rolesMap).find((role) =>
    authStore.hasRole(role),
  );
  return matchedRole ? rolesMap[matchedRole] : null;
}

async function handleClick() {
  if (!props.notification.is_read) {
    emit("read", props.notification.id);
  }

  const routeName = resolveRouteName(props.notification.link_type);
  if (routeName) {
    router.push({
      name: routeName,
      query: { highlight: props.notification.link_id },
    });
  }

  emit("close");
}
</script>

<template>
  <button
    type="button"
    @click="handleClick"
    class="dropdown-row w-full text-start"
    :class="{ unread: !notification.is_read }"
  >
    <div class="flex items-start gap-2">
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] shrink-0" :style="{ background: notifMeta.color }">
        <AppIcon :name="notifMeta.icon" />
      </div>
      <span
        v-if="!notification.is_read"
        class="mt-1.5 w-2 h-2 rounded-full bg-[#8A6D1F] dark:bg-[#F4E0A5] shrink-0"
      ></span>
      <div class="min-w-0 flex-1">
        <p class="text-[12px] font-bold truncate">
          {{ notification.title }}
        </p>
        <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 line-clamp-2">
          {{ notification.message }}
        </p>
        <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1 block">{{ timeAgo }}</span>
      </div>
    </div>
  </button>
</template>
