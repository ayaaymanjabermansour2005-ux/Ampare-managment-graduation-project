<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useMenu } from "@/composables/useMenu";
import { useAuthStore } from "@/stores/auth";
import { useAdminUiStore } from "@/stores/adminUi";
import { useSidebarBadgesStore } from "@/stores/sidebarBadges";
import { useLightningCanvas } from "@/composables/useLightningCanvas";
import { useConfirm } from "@/composables/useConfirm";
import { ChevronsLeft, ChevronsRight, LogOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const router = useRouter();
const authStore = useAuthStore();
const ui = useAdminUiStore();
const badges = useSidebarBadgesStore();
const { visibleMenu } = useMenu();
const { t, locale } = useI18n();
const { confirm } = useConfirm();

const hasBadgeItems = computed(() => visibleMenu.value.some((item) => item.badgeKey));
onMounted(() => {
  if (hasBadgeItems.value) badges.startAutoRefresh();
});
onUnmounted(() => {
  if (hasBadgeItems.value) badges.stopAutoRefresh();
});

function badgeCount(item) {
  return item.badgeKey ? (badges.counts[item.badgeKey] ?? 0) : 0;
}

/* تجميع عناصر القائمة حسب حقل group */
const groupedMenu = computed(() => {
  const groups = [];
  const index = new Map();
  visibleMenu.value.forEach((item) => {
    const groupName = item.group || "عام";
    if (!index.has(groupName)) {
      index.set(groupName, { title: groupName, titleKey: item.groupKey, items: [] });
      groups.push(index.get(groupName));
    }
    index.get(groupName).items.push(item);
  });
  return groups;
});
const showGroupTitles = computed(() => groupedMenu.value.length > 1);

function itemLabel(item) {
  return item.labelKey ? t(item.labelKey) : item.label;
}
function groupLabel(group) {
  return group.titleKey ? t(group.titleKey) : group.title;
}

const sidebarCanvas = ref(null);
useLightningCanvas(sidebarCanvas, { density: 0.4, isDark: () => ui.isDark });

const userInitials = computed(() => {
  const name = authStore.user?.name?.trim();
  if (!name) return "؟";
  const parts = name.split(/\s+/);
  return parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0].slice(0, 2);
});

const ROLE_LABELS = {
  admin: "common.role_admin",
  generator_owner: "common.role_owner",
  subscriber: "common.role_subscriber",
  technician: "common.role_technician",
};
const roleLabel = computed(() => {
  const key = ROLE_LABELS[authStore.roles[0]];
  return key ? t(key) : authStore.roles[0];
});

async function handleLogout() {
  const confirmed = await confirm({
    title: t("common.logout"),
    message: t("common.logout_confirm"),
    confirmLabel: t("common.logout"),
    variant: "danger",
  });
  if (!confirmed) return;

  await authStore.logout();
  router.push({ name: "login" });
}

function handleNavClick() {
  if (window.innerWidth < 768) ui.closeMobile();
}

/* سحب لإغلاق القائمة على الجوال */
let touchStartX = 0;
function onTouchStart(e) { touchStartX = e.touches[0].clientX; }
function onTouchEnd(e) {
  const deltaX = e.changedTouches[0].clientX - touchStartX;
  if (deltaX > 55) ui.closeMobile();
}
</script>

<template>
  <aside
    id="adminSidebar"
    class="overflow-hidden flex flex-col fixed inset-y-0 start-0 z-40 h-screen border-e border-[#e7e2d6] dark:border-white/10 bg-white/95 dark:bg-[#15171a]/95 md:bg-white/80 dark:md:bg-[#15171a]/90 backdrop-blur-xl shadow-2xl md:shadow-none"
    :class="{ collapsed: ui.isCollapsed, 'mobile-open': ui.isMobileOpen }"
    :aria-label="t('common.main_nav')"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
  >
    <canvas ref="sidebarCanvas" class="absolute inset-0 w-full h-full pointer-events-none opacity-50 z-0"></canvas>

    <div class="relative z-10 flex items-center gap-2.5 px-5 h-[72px] border-b border-[#eee8da] dark:border-white/10 sidebar-brand-row">
      <div class="logo-box w-10 h-10 rounded-xl flex items-center justify-center shrink-0 overflow-hidden p-1">
        <img
          src="/images/logo.png"
          :alt="t('auth.logo_alt')"
          class="w-full h-full object-contain"
          onerror="this.replaceWith(Object.assign(document.createElement('i'), { className: 'fa-solid fa-bolt text-lg text-[#8A6D1F] dark:text-[#F4E0A5]' }))"
        />
      </div>
      <span class="sidebar-brand-text text-base font-extrabold brand-gradient-text flex-1 min-w-0 truncate">{{ t("common.brand") }}</span>
    </div>

    <nav class="relative z-10 flex-1 overflow-y-auto px-3 py-2 space-y-0.5">
      <template v-for="group in groupedMenu" :key="group.title">
        <div v-if="showGroupTitles" class="sidebar-group-title">{{ groupLabel(group) }}</div>
        <RouterLink
          v-for="item in group.items"
          :key="item.labelKey + (item.query?.tab || '')"
          :to="{ name: item.route, query: item.query || {} }"
          class="sidebar-item"
          active-class="active"
          @click="handleNavClick"
        >
          <AppIcon :name="item.icon" />
          <span class="sidebar-label flex-1">{{ itemLabel(item) }}</span>
          <span v-if="item.badgeKey && badgeCount(item) > 0" class="sidebar-badge">{{ badgeCount(item) > 99 ? "99+" : badgeCount(item) }}</span>
          <span class="sidebar-tooltip">{{ itemLabel(item) }}</span>
        </RouterLink>
      </template>
    </nav>

    <div class="relative z-10 p-3 border-t border-[#eee8da] dark:border-white/10">
      <div class="sidebar-profile flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white text-[11px] font-bold shrink-0">
          {{ userInitials }}
        </div>
        <div class="sidebar-label flex-1 min-w-0">
          <div class="text-[12px] font-bold truncate">{{ authStore.user?.name ?? "—" }}</div>
          <div class="text-[10px] text-[#9a9d97] font-normal truncate">{{ roleLabel }}</div>
        </div>
        <button
          type="button"
          class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5 !text-[#D9534F] shrink-0"
          :title="t('common.logout')"
          :aria-label="t('common.logout')"
          @click="handleLogout"
        >
          <LogOut aria-hidden="true" />
        </button>
        <button
          id="adminSidebarToggle"
          type="button"
          class="hidden lg:flex icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5 shrink-0"
          :title="ui.isCollapsed ? t('common.expand_menu') : t('common.collapse_menu')"
          @click="ui.toggleCollapse()"
        >
          <ChevronsRight aria-hidden="true" v-if="ui.isCollapsed" /><ChevronsLeft aria-hidden="true" v-else />
        </button>
      </div>
    </div>
  </aside>

  <!-- طبقة إغلاق عند الضغط خارج القائمة على الجوال -->
  <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
    <div v-if="ui.isMobileOpen" class="fixed inset-0 z-30 bg-black/40 md:hidden" @click="ui.closeMobile()"></div>
  </Transition>
</template>