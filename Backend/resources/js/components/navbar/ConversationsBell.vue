<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter, RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { usePermissions } from "@/composables/usePermissions";
import conversationService from "@/services/conversationService";
import { MessageCircleMore } from "@lucide/vue";

const MESSAGES_ROUTE_BY_ROLE = {
  admin: "admin.messages",
  generator_owner: "owner.messages",
  technician: "technician.messages",
  subscriber: "subscriber.messages",
};

const router = useRouter();
const { t } = useI18n();
const { can, hasRole } = usePermissions();

const messagesRoute = Object.keys(MESSAGES_ROUTE_BY_ROLE).find((role) => hasRole(role));
const messagesRouteName = messagesRoute ? MESSAGES_ROUTE_BY_ROLE[messagesRoute] : null;

const isOpen = ref(false);
const conversations = ref([]);
const isLoading = ref(true);
const unreadCount = ref(0);

async function fetchUnreadCount() {
  try {
    const { data } = await conversationService.unreadCount();
    unreadCount.value = data.data.unread_count ?? 0;
  } catch {
    unreadCount.value = 0;
  }
}

async function fetchConversations() {
  isLoading.value = true;
  try {
    const { data } = await conversationService.list({ per_page: 6 });
    const payload = data.data;
    conversations.value = payload.data ?? payload;
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  if (!can("conversations.view")) return;
  fetchConversations();
  fetchUnreadCount();
});

function timeAgo(str) {
  if (!str) return "—";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}

function goToConversation(conv) {
  isOpen.value = false;
  if (!messagesRouteName) return;
  router.push({ name: messagesRouteName, query: { open: conv.id } });
}

function toggle() {
  isOpen.value = !isOpen.value;
}

function handleClickOutside(e) {
  if (!e.target.closest("[data-conversations-scope]")) isOpen.value = false;
}
onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));
</script>

<template>
  <div v-if="can('conversations.view')" class="relative" data-conversations-scope>
    <button type="button" class="icon-btn relative" :title="t('common.conversations')" :aria-label="t('common.conversations')" @click.stop="toggle">
      <MessageCircleMore aria-hidden="true" />
      <span v-if="unreadCount > 0" class="notif-dot"></span>
    </button>
    <div
      v-if="isOpen"
      class="max-md:fixed max-md:inset-x-3 max-md:top-16 md:absolute md:end-0 md:top-[calc(100%+10px)] w-auto md:w-[19rem] max-h-[26rem] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl flex flex-col overflow-hidden"
    >
      <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-[#eee8da] dark:border-white/10">
        <span class="text-[12.5px] font-extrabold">{{ t("common.conversations") }}</span>
        <RouterLink
          v-if="messagesRouteName"
          :to="{ name: messagesRouteName }"
          class="text-[10.5px] font-bold text-[#8A6D1F] hover:underline"
          @click="isOpen = false"
        >
          {{ t("common.view_all") }}
        </RouterLink>
      </div>
      <div class="overflow-y-auto flex-1 divide-y divide-[#eee8da] dark:divide-white/5">
        <div v-if="isLoading" class="dropdown-empty">{{ t("common.loading") }}</div>
        <div v-else-if="conversations.length === 0" class="dropdown-empty">{{ t("common.no_conversations") }}</div>
        <button
          v-for="c in conversations"
          :key="c.id"
          type="button"
          class="dropdown-row w-full text-start"
          :class="{ unread: (c.unread_count ?? 0) > 0 }"
          @click="goToConversation(c)"
        >
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white text-[11px] font-bold shrink-0">
            {{ c.other_participant?.name?.charAt(0) ?? "؟" }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[12px] font-bold truncate">{{ c.other_participant?.name ?? "—" }}</p>
            <p v-if="c.latest_message" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate">{{ c.latest_message.text }}</p>
            <span v-if="c.latest_message" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(c.latest_message.created_at) }}</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
