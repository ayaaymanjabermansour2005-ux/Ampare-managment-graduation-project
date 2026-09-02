<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import conversationService from "@/services/conversationService";
import { Menu } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const emit = defineEmits(["open-more"]);

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const unreadMessages = ref(0);
let refreshTimer = null;

async function fetchUnreadCount() {
  try {
    const { data } = await conversationService.unreadCount();
    unreadMessages.value = data.data.unread_count ?? 0;
  } catch (err) {
    console.error("Failed to fetch unread messages count.", err);
  }
}

onMounted(() => {
  fetchUnreadCount();
  refreshTimer = setInterval(fetchUnreadCount, 60_000);
});
onUnmounted(() => clearInterval(refreshTimer));

const NAV_ITEMS = computed(() => [
  { key: "tasks", label: t("technician_portal.nav_tasks"), icon: "fa-list-check", tab: "tasks" },
  { key: "readings", label: t("technician_portal.nav_readings"), icon: "fa-gauge", tab: "readings" },
  { key: "messages", label: t("technician_portal.nav_messages"), icon: "fa-comment-dots", routeName: "technician.messages" },
  { key: "payments", label: t("technician_portal.nav_payments"), icon: "fa-wallet", tab: "payments" },
]);

function isActive(item) {
  if (item.routeName) return route.name === item.routeName;
  return route.name === "technician.dashboard" && (route.query.tab ?? "tasks") === item.tab;
}

function go(item) {
  if (item.routeName) {
    router.push({ name: item.routeName });
    return;
  }
  router.push({ name: "technician.dashboard", query: { tab: item.tab } });
}
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-40 bg-white/85 dark:bg-[#1c1e20]/90 backdrop-blur-xl border-t border-[#eee8da] dark:border-white/10 pb-[env(safe-area-inset-bottom)]"
  >
    <div class="max-w-lg mx-auto grid grid-cols-5">
      <button
        v-for="item in NAV_ITEMS"
        :key="item.key"
        type="button"
        @click="go(item)"
        class="relative flex flex-col items-center justify-center gap-1 py-2.5 min-h-[56px] text-[10.5px] font-bold transition-colors"
        :class="isActive(item) ? 'text-[#3E582E] dark:text-[#a8d19a]' : 'text-[#a5a99e] dark:text-[#787c73]'"
      >
        <span
          v-if="isActive(item)"
          class="absolute top-0 inset-x-[30%] h-[3px] rounded-b-full bg-gradient-to-l from-[#3E582E] to-[#8A6D1F]"
          aria-hidden="true"
        ></span>
        <span class="relative">
          <AppIcon :name="item.icon" class="text-lg" />
          <span
            v-if="item.key === 'messages' && unreadMessages > 0"
            class="absolute -top-1.5 -end-2 min-w-[16px] h-4 px-1 rounded-full bg-[#D9534F] text-white text-[9px] font-mono font-extrabold flex items-center justify-center"
          >
            {{ unreadMessages > 9 ? "9+" : unreadMessages }}
          </span>
        </span>
        {{ item.label }}
      </button>

      <button
        type="button"
        @click="emit('open-more')"
        class="flex flex-col items-center justify-center gap-1 py-2.5 min-h-[56px] text-[10.5px] font-bold text-[#a5a99e] dark:text-[#787c73] transition-colors"
      >
        <Menu class="text-lg" aria-hidden="true" />
        {{ t("technician_portal.more_label") }}
      </button>
    </div>
  </nav>
</template>