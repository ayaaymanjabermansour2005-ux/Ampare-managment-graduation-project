<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter, RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAdminUiStore } from "@/stores/adminUi";
import { useConnectivityStore } from "@/stores/connectivity";
import { useMenu } from "@/composables/useMenu";
import { usePermissions } from "@/composables/usePermissions";
import { setLocale } from "@/i18n";
import NotificationBell from "@/components/notifications/NotificationBell.vue";
import ConversationsBell from "@/components/navbar/ConversationsBell.vue";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";
import invoiceService from "@/services/invoiceService";
import adminQuickActions from "@/config/adminQuickActions";
import ownerQuickActions from "@/config/ownerQuickActions";
import technicianQuickActions from "@/config/technicianQuickActions";
import subscriberQuickActions from "@/config/subscriberQuickActions";
import { ArrowLeft, ArrowRight, Circle, CircleX, Expand, Menu, Moon, Plus, RefreshCw, Search, Shrink, Sun } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const router = useRouter();
const ui = useAdminUiStore();
const connectivityStore = useConnectivityStore();
const { visibleMenu } = useMenu();
const { can, hasRole } = usePermissions();
const { t, locale } = useI18n();

/* ---------------- الدور الحالي ---------------- */
const ROLE_ORDER = ["admin", "generator_owner", "technician", "subscriber"];
const currentRole = computed(() => ROLE_ORDER.find((r) => hasRole(r)) ?? null);

/* ---------------- اللغة والثيم ---------------- */
const langButtonLabel = computed(() => (locale.value === "ar" ? "EN" : "AR"));
function toggleLanguage() {
  setLocale(locale.value === "ar" ? "en" : "ar");
}

/* ---------------- الساعة الحية وشارة حالة النظام ---------------- */
const now = ref(new Date());
let clockInterval = null;
onMounted(() => {
  clockInterval = setInterval(() => (now.value = new Date()), 1000);
});
onUnmounted(() => clearInterval(clockInterval));
const liveClock = computed(() =>
  now.value.toLocaleTimeString(locale.value === "ar" ? "ar-EG" : "en-US", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  }),
);

/* ---------------- البحث الشامل: يتحدد نطاقه تلقائيًا حسب الدور، وكل الكيانات مضبوطة Backend-side لترجع فقط بيانات صاحب الجلسة ---------------- */
const SEARCH_ENTITY_CONFIG = {
  admin: {
    generators: { routeName: "admin.generator-owners", groupLabelKey: "menu.generator_owners" },
    subscribers: { routeName: "admin.subscribers", groupLabelKey: "menu.subscribers" },
    invoices: { routeName: "admin.invoices", groupLabelKey: "menu.invoices" },
  },
  generator_owner: {
    generators: { routeName: "owner.generators", groupLabelKey: "menu.generators" },
    invoices: { routeName: "owner.invoices", groupLabelKey: "menu.invoices" },
  },
  // لا يوجد نطاق بحث للفني — AppNavbar لا يُحمَّل أصلًا لدور الفني
  // (يستخدم TechnicianPortalLayout المستقل)، وصفحة "مولداتي" القديمة
  // (technician.generators) أُزيلت لأنها ليست جزءًا من مواصفة البوابة الجديدة.
  technician: {},
  subscriber: {
    invoices: { routeName: "subscriber.invoices", groupLabelKey: "menu.my_invoices" },
  },
};

const staticPages = computed(() =>
  visibleMenu.value.map((item) => ({
    label: item.labelKey ? t(item.labelKey) : item.label,
    icon: item.icon.replace("fa-solid ", ""),
    route: item.route,
  })),
);

async function searchGenerators(query, cfg) {
  const { data } = await generatorService.list({ search: query, per_page: 4 });
  const payload = data.data;
  const list = payload.data ?? payload;
  const results = list.map((g) => ({
    primary: g.name,
    secondary: g.location?.city ?? "",
    icon: "fa-plug-circle-bolt",
    groupLabel: t(cfg.groupLabelKey),
    to: { name: cfg.routeName, query: { q: g.name } },
  }));
  const total = payload.meta?.total ?? payload.total ?? results.length;
  return {
    results,
    viewAllLink: total > results.length ? { label: t(cfg.groupLabelKey), to: { name: cfg.routeName, query: { q: query } } } : null,
  };
}

async function searchSubscribers(query, cfg) {
  const { data } = await userService.list({ role: "subscriber", search: query, per_page: 4 });
  const payload = data.data;
  const list = payload.data ?? payload;
  const results = list.map((u) => ({
    primary: u.name,
    secondary: u.email ?? u.phone ?? "",
    icon: "fa-user",
    groupLabel: t(cfg.groupLabelKey),
    to: { name: cfg.routeName, query: { q: u.name } },
  }));
  const total = payload.meta?.total ?? payload.total ?? results.length;
  return {
    results,
    viewAllLink: total > results.length ? { label: t(cfg.groupLabelKey), to: { name: cfg.routeName, query: { q: query } } } : null,
  };
}

async function searchInvoices(query, cfg) {
  const { data } = await invoiceService.list({ search: query, per_page: 4 });
  const payload = data.data;
  const list = payload.data ?? payload;
  const results = list.map((inv) => ({
    primary: t("common.invoice_hash", { id: inv.id }),
    secondary: inv.subscriber?.name ?? inv.generator?.name ?? "",
    icon: "fa-file-invoice-dollar",
    groupLabel: t(cfg.groupLabelKey),
    to: { name: cfg.routeName, query: { q: String(inv.id) } },
  }));
  const total = payload.meta?.total ?? payload.total ?? results.length;
  return {
    results,
    viewAllLink: total > results.length ? { label: t(cfg.groupLabelKey), to: { name: cfg.routeName, query: { q: query } } } : null,
  };
}

const searchQuery = ref("");
const searchResults = ref([]);
const isSearchOpen = ref(false);
const viewAllLinks = ref([]);
const isSearching = ref(false);
const activeResultIndex = ref(-1);
const searchInputEl = ref(null);
let searchDebounce = null;

async function runSearch(q) {
  const query = q.trim();
  if (!query) {
    searchResults.value = [];
    isSearchOpen.value = false;
    activeResultIndex.value = -1;
    viewAllLinks.value = [];
    return;
  }
  isSearching.value = true;
  isSearchOpen.value = true;

  try {
    const config = SEARCH_ENTITY_CONFIG[currentRole.value] ?? {};
    const tasks = [];
    if (config.generators) tasks.push(searchGenerators(query, config.generators));
    if (config.subscribers) tasks.push(searchSubscribers(query, config.subscribers));
    if (config.invoices) tasks.push(searchInvoices(query, config.invoices));

    const groups = tasks.length ? await Promise.all(tasks) : [];
    const entityResults = groups.flatMap((g) => g.results);
    const links = groups.map((g) => g.viewAllLink).filter(Boolean);

    const pages = staticPages.value
      .filter((p) => p.label.includes(query))
      .map((p) => ({
        primary: p.label,
        secondary: "",
        icon: p.icon,
        groupLabel: t("common.pages_group_label"),
        to: { name: p.route },
      }));

    searchResults.value = [...entityResults, ...pages];
    activeResultIndex.value = searchResults.value.length > 0 ? 0 : -1;
    viewAllLinks.value = links;
  } finally {
    isSearching.value = false;
  }
}

function onSearchInput(e) {
  clearTimeout(searchDebounce);
  const value = e.target.value;
  searchDebounce = setTimeout(() => runSearch(value), 300);
}
function clearSearch() {
  searchQuery.value = "";
  searchResults.value = [];
  isSearchOpen.value = false;
  activeResultIndex.value = -1;
  viewAllLinks.value = [];
}
function goToResult(item) {
  isSearchOpen.value = false;
  activeResultIndex.value = -1;
  router.push(item.to);
}

function onSearchKeydown(e) {
  if (!isSearchOpen.value || searchResults.value.length === 0) {
    if (e.key === "Escape") clearSearch();
    return;
  }

  if (e.key === "ArrowDown") {
    e.preventDefault();
    activeResultIndex.value = (activeResultIndex.value + 1) % searchResults.value.length;
  } else if (e.key === "ArrowUp") {
    e.preventDefault();
    activeResultIndex.value = (activeResultIndex.value - 1 + searchResults.value.length) % searchResults.value.length;
  } else if (e.key === "Enter") {
    e.preventDefault();
    const selected = searchResults.value[activeResultIndex.value];
    if (selected) goToResult(selected);
  } else if (e.key === "Escape") {
    clearSearch();
    searchInputEl.value?.blur();
  }
}

/* ---------------- إضافة سريعة ---------------- */
const QUICK_ACTIONS_BY_ROLE = {
  admin: adminQuickActions,
  generator_owner: ownerQuickActions,
  technician: technicianQuickActions,
  subscriber: subscriberQuickActions,
};
const visibleQuickActions = computed(() => {
  const list = QUICK_ACTIONS_BY_ROLE[currentRole.value] ?? [];
  return list.filter((a) => !a.permission || can(a.permission));
});
function quickActionLabel(action) {
  return action.labelKey ? t(action.labelKey) : action.label;
}
const isQuickAddOpen = ref(false);

/* ---------------- ملء الشاشة ---------------- */
const isFullscreen = ref(false);
function toggleFullscreen() {
  if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
  else document.exitFullscreen?.();
}
function onFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement;
}
onMounted(() => document.addEventListener("fullscreenchange", onFullscreenChange));
onUnmounted(() => document.removeEventListener("fullscreenchange", onFullscreenChange));

/* ---------------- إغلاق القوائم عند الضغط خارجها ---------------- */
function closeAllPanels() {
  isSearchOpen.value = false;
  isQuickAddOpen.value = false;
}
function onDocumentClick(e) {
  if (!e.target.closest("[data-dropdown-scope]")) closeAllPanels();
}
onMounted(() => document.addEventListener("click", onDocumentClick));
onUnmounted(() => document.removeEventListener("click", onDocumentClick));

/* ---------------- اختصار لوحة مفاتيح Ctrl+K / Cmd+K لفتح البحث فورًا ---------------- */
function onGlobalKeydown(e) {
  const key = e.key.toLowerCase();
  const isPlainK = (e.ctrlKey || e.metaKey) && !e.shiftKey && key === "k";
  const isShiftK = (e.ctrlKey || e.metaKey) && e.shiftKey && key === "k";
  if (!isPlainK && !isShiftK) return;

  e.preventDefault();
  searchInputEl.value?.focus();
  if (searchQuery.value) isSearchOpen.value = true;
}
onMounted(() => document.addEventListener("keydown", onGlobalKeydown));
onUnmounted(() => document.removeEventListener("keydown", onGlobalKeydown));

function toggleQuickAdd() {
  isQuickAddOpen.value = !isQuickAddOpen.value;
  isSearchOpen.value = false;
}

</script>

<template>
  <header class="sticky top-0 z-50 bg-white/75 dark:bg-[#15171a]/85 backdrop-blur-xl border-b border-[#eee8da] dark:border-white/10">
    <div class="flex items-center justify-between gap-3 px-4 lg:px-6 h-[72px]">
      <div class="flex items-center gap-3 flex-1">
        <button type="button" class="md:hidden icon-btn !bg-[#EBF1E7] dark:!bg-white/5" :aria-label="t('common.open_menu')" @click="ui.openMobile()">
          <Menu />
        </button>

        <div
          v-if="!connectivityStore.isOnline"
          class="hidden sm:flex items-center gap-2 text-xs font-medium text-warning bg-warning-bg px-3 py-1.5 rounded-full"
          role="status"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
          <span>{{ t("common.offline_saving_locally") }}</span>
        </div>
        <div
          v-else-if="connectivityStore.pendingCount > 0"
          class="hidden sm:flex items-center gap-2 text-xs font-medium text-info bg-info-bg px-3 py-1.5 rounded-full"
        >
          <RefreshCw class="text-[11px] animate-spin" />
          <span>{{ t("common.syncing_operations", { count: connectivityStore.pendingCount }) }}</span>
        </div>

        <div class="relative hidden md:block max-w-sm w-full" data-dropdown-scope>
          <Search class="absolute top-1/2 -translate-y-1/2 start-3.5 text-[#9a9d97] text-xs" />
          <input
            ref="searchInputEl"
            v-model="searchQuery"
            type="text"
            :placeholder="t('common.search_placeholder')"
            autocomplete="off"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-9 pe-3 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors"
            @input="onSearchInput"
            @keydown="onSearchKeydown"
            @focus="searchQuery && (isSearchOpen = true)"
          />
          <button :aria-label="t('common.clear_search')" v-if="searchQuery" type="button" class="absolute top-1/2 -translate-y-1/2 end-3 text-[#9a9d97] hover:text-[#D9534F] text-xs" @click="clearSearch">
            <CircleX />
          </button>
          <kbd
            v-if="!searchQuery"
            class="absolute top-1/2 -translate-y-1/2 end-2.5 text-[9.5px] font-bold text-[#9a9d97] bg-[#e7e2d6]/70 dark:bg-white/10 border border-[#e0dccf] dark:border-white/10 rounded px-1.5 py-0.5 pointer-events-none"
          >Ctrl+Shift+K</kbd>

          <div v-if="isSearchOpen" class="absolute top-[calc(100%+8px)] start-0 w-full min-w-[22rem] max-w-[calc(100vw-2rem)] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl max-h-96 overflow-y-auto">
            <div v-if="isSearching" class="dropdown-empty">{{ t("common.searching") }}</div>
            <div v-else-if="searchResults.length === 0" class="dropdown-empty flex flex-col items-center gap-2">
              <Search class="text-lg text-[#c9c4b4]" />
              <span>{{ t("common.no_results_for", { query: searchQuery }) }}</span>
            </div>
            <button
              v-for="(item, i) in searchResults"
              :key="i"
              type="button"
              class="dropdown-row w-full text-start"
              :class="{ '!bg-[#f4efe5]/80 dark:!bg-white/10': i === activeResultIndex }"
              @mouseenter="activeResultIndex = i"
              @click="goToResult(item)"
            >
              <div class="w-7 h-7 rounded-lg bg-[#f4efe5] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F] text-[11px] shrink-0">
                <AppIcon :name="item.icon" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-[12px] font-bold truncate">{{ item.primary }}</p>
                <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate">
                  <span v-if="item.secondary">{{ item.secondary }} · </span>{{ item.groupLabel }}
                </p>
              </div>
            </button>

            <div v-if="viewAllLinks.length" class="border-t border-[#eee8da] dark:border-white/10 p-1.5 flex flex-wrap gap-1.5">
              <RouterLink
                v-for="link in viewAllLinks"
                :key="link.label"
                :to="link.to"
                class="text-[11px] font-bold text-[#8A6D1F] hover:text-[#3E582E] dark:hover:text-[#8cc35a] px-2 py-1 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 flex items-center gap-1"
                @click="isSearchOpen = false"
              >
                {{ t("common.view_all_in", { label: link.label }) }}
                <ArrowLeft aria-hidden="true" v-if="locale === 'ar'" style="font-size:9px" /><ArrowRight aria-hidden="true" v-else style="font-size:9px" />
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="hidden xl:flex items-center gap-2 text-[11.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full px-3.5 py-1.5">
          <Circle class="text-[6px] text-[#28A745]" fill="currentColor" aria-hidden="true" /> {{ t("common.system_running") }}
          <span class="w-px h-3 bg-[#e0dccf] dark:bg-white/10 mx-1"></span>
          <span>{{ liveClock }}</span>
        </span>

        <div v-if="visibleQuickActions.length" class="relative" data-dropdown-scope>
          <button type="button" class="icon-btn hidden md:flex" :title="t('common.quick_add')" :aria-label="t('common.quick_add')" @click.stop="toggleQuickAdd">
            <Plus aria-hidden="true" />
          </button>
          <div v-if="isQuickAddOpen" class="absolute start-0 top-[calc(100%+10px)] w-64 max-w-[calc(100vw-1.5rem)] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl overflow-hidden">
            <div class="px-3.5 py-2.5 border-b border-[#eee8da] dark:border-white/10">
              <span class="text-[12.5px] font-extrabold">{{ t("common.quick_add") }}</span>
            </div>
            <div class="p-1.5 grid grid-cols-1">
              <RouterLink
                v-for="q in visibleQuickActions"
                :key="q.route + (q.query?.tab ?? '')"
                :to="{ name: q.route, query: q.query }"
                class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[12px] font-semibold hover:bg-[#EBF1E7] dark:hover:bg-white/5 transition-colors"
                @click="isQuickAddOpen = false"
              >
                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[11px] shrink-0" :style="{ background: `linear-gradient(135deg, ${q.c1}, ${q.c2})` }">
                  <AppIcon :name="q.icon" />
                </span>
                <span>{{ quickActionLabel(q) }}</span>
              </RouterLink>
            </div>
          </div>
        </div>

        <button type="button" class="icon-btn hidden md:flex" :title="t('common.fullscreen')" @click="toggleFullscreen">
          <Shrink aria-hidden="true" v-if="isFullscreen" /><Expand aria-hidden="true" v-else />
        </button>

        <ConversationsBell />
        <NotificationBell />

        <div class="icon-btn-group">
          <button type="button" class="icon-btn" :title="t('common.toggle_theme')" @click="ui.toggleTheme()">
            <Sun aria-hidden="true" v-if="ui.isDark" /><Moon aria-hidden="true" v-else />
          </button>
          <div class="w-px h-4 bg-[#e0dccf] dark:bg-white/10"></div>
          <button type="button" class="icon-btn text-[10.5px] font-bold" :title="t('common.toggle_language')" @click="toggleLanguage">
            {{ langButtonLabel }}
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
