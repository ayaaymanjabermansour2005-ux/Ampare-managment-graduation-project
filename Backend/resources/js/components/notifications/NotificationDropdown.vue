<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useNotificationStore } from "@/stores/notification";
import NotificationItem from "./NotificationItem.vue";

const emit = defineEmits(["close"]);
const notificationStore = useNotificationStore();
const { t } = useI18n();
const dropdownRef = ref(null);

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    emit("close");
  }
}

onMounted(() => document.addEventListener("click", handleClickOutside, true));
onUnmounted(() =>
  document.removeEventListener("click", handleClickOutside, true),
);

async function handleRead(id) {
  await notificationStore.markAsRead(id);
}

async function handleMarkAllRead() {
  await notificationStore.markAllAsRead();
}
</script>

<template>
  <div
    ref="dropdownRef"
    class="max-md:fixed max-md:inset-x-3 max-md:top-16 md:absolute md:end-0 md:top-[calc(100%+10px)] w-auto md:w-80 max-h-[26rem] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl flex flex-col overflow-hidden"
  >
    <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-[#eee8da] dark:border-white/10">
      <span class="text-[12.5px] font-extrabold">{{ t("common.notifications") }}</span>
      <button
        v-if="notificationStore.unreadCount > 0"
        type="button"
        class="text-[10.5px] font-bold text-[#8A6D1F] hover:underline"
        @click="handleMarkAllRead"
      >
        {{ t("common.mark_all_read") }}
      </button>
    </div>

    <div class="overflow-y-auto flex-1 divide-y divide-[#eee8da] dark:divide-white/5">
      <div v-if="notificationStore.loading" class="dropdown-empty">{{ t("common.loading") }}</div>
      <div v-else-if="!notificationStore.notifications.length" class="dropdown-empty">{{ t("common.no_notifications") }}</div>

      <NotificationItem
        v-else
        v-for="notification in notificationStore.notifications"
        :key="notification.id"
        :notification="notification"
        @read="handleRead"
        @close="emit('close')"
      />
    </div>
  </div>
</template>
