<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useNotificationStore } from "@/stores/notification";
import { vReveal } from "@/directives/reveal";
import NotificationItem from "@/components/notifications/NotificationItem.vue";
import { Bell, CheckCheck, ChevronLeft, ChevronRight, Trash2 } from "@lucide/vue";

const { t } = useI18n();
const notificationStore = useNotificationStore();

async function handleRead(id) {
  await notificationStore.markAsRead(id);
}

async function handleDelete(id) {
  await notificationStore.deleteNotification(id);
}

onMounted(() => notificationStore.fetchNotifications());
</script>

<template>
  <div class="space-y-6">
    <!-- ===================== رأس الصفحة ===================== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("common.account_breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("common.notifications") }}</span>
      </nav>

      <div class="relative flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-xl lg:text-2xl font-extrabold flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base relative">
            <Bell aria-hidden="true" />
            <span
              v-if="notificationStore.unreadCount > 0"
              class="absolute -top-1 -end-1 min-w-[16px] h-4 px-1 rounded-full bg-[#D9534F] text-white text-[9px] font-bold flex items-center justify-center border-2 border-white dark:border-[#1c1e20]"
            >{{ notificationStore.unreadCount }}</span>
          </span>
          {{ t("common.notifications") }}
        </h1>

        <button
          v-if="notificationStore.unreadCount > 0"
          type="button"
          @click="notificationStore.markAllAsRead()"
          class="btn-outline-brand shrink-0"
        >
          <CheckCheck aria-hidden="true" />
          <span>{{ t("common.mark_all_read") }}</span>
        </button>
      </div>
    </section>

    <!-- ===================== قائمة الإشعارات ===================== -->
    <div v-if="notificationStore.loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="glass-card h-16 thumb-loading"></div>
    </div>

    <section
      v-else-if="notificationStore.notifications.length === 0"
      v-reveal
      class="glass-card p-10 text-center max-w-md mx-auto"
    >
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <Bell aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("notifications_page.empty_title") }}</h3>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("notifications_page.empty_message") }}</p>
    </section>

    <section v-else v-reveal class="glass-card p-0 overflow-hidden">
      <div
        v-for="notification in notificationStore.notifications"
        :key="notification.id"
        class="group relative border-b border-[#f0ece0] dark:border-white/5 last:border-0 hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition-colors"
      >
        <NotificationItem :notification="notification" @read="handleRead" />
        <button
          type="button"
          @click.stop="handleDelete(notification.id)"
          class="action-btn action-btn--delete absolute top-1/2 -translate-y-1/2 end-3 opacity-0 group-hover:opacity-100"
          :title="t('common.delete')"
        >
          <Trash2 aria-hidden="true" />
        </button>
      </div>
    </section>

    <div v-if="notificationStore.pagination.last_page > 1" class="flex items-center justify-between text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
      <span>{{ t("notifications_page.pagination_text", { current: notificationStore.pagination.current_page, last: notificationStore.pagination.last_page, total: notificationStore.pagination.total }) }}</span>
      <div class="flex items-center gap-1">
        <button :aria-label="t('common.previous_page')" type="button" :disabled="notificationStore.pagination.current_page <= 1" @click="notificationStore.fetchNotifications(notificationStore.pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
          <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
          <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
        </button>
        <button :aria-label="t('common.next_page')" type="button" :disabled="notificationStore.pagination.current_page >= notificationStore.pagination.last_page" @click="notificationStore.fetchNotifications(notificationStore.pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
          <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
          <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
        </button>
      </div>
    </div>
  </div>
</template>