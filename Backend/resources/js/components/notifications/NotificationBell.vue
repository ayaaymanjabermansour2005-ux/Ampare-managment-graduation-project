<script setup>
import { ref, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useNotificationStore } from "@/stores/notification";
import { useRealtimeNotifications } from "@/composables/useRealtimeNotifications";
import NotificationDropdown from "./NotificationDropdown.vue";
import { Bell } from "@lucide/vue";

const notificationStore = useNotificationStore();
const { t } = useI18n();
const isOpen = ref(false);
const justReceived = ref(false);

useRealtimeNotifications();

onMounted(() => {
  notificationStore.fetchNotifications();
});

watch(
  () => notificationStore.unreadCount,
  (newVal, oldVal) => {
    if (newVal > oldVal) {
      justReceived.value = true;
      setTimeout(() => {
        justReceived.value = false;
      }, 2000);
    }
  },
);

function toggle() {
  isOpen.value = !isOpen.value;
}
</script>

<template>
  <div class="relative" data-dropdown-scope>
    <button type="button" class="icon-btn relative" :title="t('common.notifications')" :aria-label="t('common.notifications')" @click.stop="toggle">
      <Bell :class="{ 'fa-shake': justReceived }" aria-hidden="true" />
      <span v-if="notificationStore.unreadCount > 0" class="notif-dot"></span>
    </button>

    <NotificationDropdown v-if="isOpen" @close="isOpen = false" />
  </div>
</template>
