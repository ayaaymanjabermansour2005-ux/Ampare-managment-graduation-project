<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter, RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAdminUiStore } from "@/stores/adminUi";
import { useNotificationStore } from "@/stores/notification";
import { usePermissions } from "@/composables/usePermissions";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";
import invoiceService from "@/services/invoiceService";
import conversationService from "@/services/conversationService";
import quickActions from "@/config/adminQuickActions";
import { setLocale } from "@/i18n";
import { ArrowLeft, ArrowRight, Bell, Circle, CircleX, Expand, Menu, MessageCircleMore, Moon, Plus, Search, Shrink, Sun } from "@lucide/vue";

import AppIcon from "@/components/ui/AppIcon.vue";

const router = useRouter();
const ui = useAdminUiStore();
const notificationStore = useNotificationStore();
const { can } = usePermissions();
const { t, locale } = useI18n();

/* ---------------- اللغة ---------------- */
const langButtonLabel = computed(() => (locale.value === "ar" ? "EN" : "AR"));
function toggleLanguage() {
  setLocale(locale.value === "ar" ? "en" : "ar");
}

/* ---------------- الساعة الحية ---------------- */
const now = ref(new Date());
let clockInterval = null;
onMounted(() => {
  clockInterval = setInterval(() => (now.value = new Date()), 1000);
  notificationStore.fetchNotifications();
  if (can("conversations.view")) {
    fetchConversations();
    fetchUnreadConversationsCount();
  }
});
onUnmounted(() => clearInterval(clockInterval));

const liveClock = computed(() =>
  now.value.toLocaleTimeString(locale.value === "ar" ? "ar-EG" : "en-US", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  }),
);

/* ---------------- البحث الشامل: مولدات + مشتركين + فواتير + صفحات ثابتة (كلها حقيقية) ---------------- */
const searchQuery = ref("");
const searchResults = ref([]);
const isSearchOpen = ref(false);
const viewAllLinks = ref([]);
const isSearching = ref(false);
const activeResultIndex = ref(-1);
const searchInputEl = ref(null);
let searchDebounce = null;

const staticPages = computed(() => [
  { label: t("menu.generator_owners"), icon: "fa-user-tie", route: "admin.generator-owners" },
  { label: t("menu.subscribers"), icon: "fa-users", route: "admin.subscribers" },
  { label: t("menu.invoices"), icon: "fa-file-invoice-dollar", route: "admin.invoices" },
  { label: t("menu.technicians"), icon: "fa-screwdriver-wrench", route: "admin.technicians" },
]);

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
    const [generatorsRes, subscribersRes, invoicesRes] = await Promise.all([
      generatorService.list({ search: query, per_page: 4 }),
      userService.list({ role: "subscriber", search: query, per_page: 4 }),
      invoiceService.list({ search: query, per_page: 4 }),
    ]);

    const generatorsPayload = generatorsRes.data.data;
    const subscribersPayload = subscribersRes.data.data;
    const invoicesPayload = invoicesRes.data.data;

    const generators = (generatorsPayload.data ?? generatorsPayload).map((g) => ({
      primary: g.name,
      secondary: g.location?.city ?? "",
      icon: "fa-plug-circle-bolt",
      groupLabel: t("menu.generator_owners"),
      to: { name: "admin.generator-owners", query: { q: g.name } },
    }));

    const subscribers = (subscribersPayload.data ?? subscribersPayload).map((u) => ({
      primary: u.name,
      secondary: u.email ?? u.phone ?? "",
      icon: "fa-user",
      groupLabel: t("menu.subscribers"),
      to: { name: "admin.subscribers", query: { q: u.name } },
    }));

    const invoices = (invoicesPayload.data ?? invoicesPayload).map((inv) => ({
      primary: t("common.invoice_hash", { id: inv.id }),
      secondary: inv.subscriber?.name ?? inv.generator?.name ?? "",
      icon: "fa-file-invoice-dollar",
      groupLabel: t("menu.invoices"),
      to: { name: "admin.invoices", query: { q: String(inv.id) } },
    }));

    const pages = staticPages.value
      .filter((p) => p.label.includes(query))
      .map((p) => ({
        primary: p.label,
        secondary: "",
        icon: p.icon,
        groupLabel: t("common.pages_group_label"),
        to: { name: p.route },
      }));

    searchResults.value = [...generators, ...subscribers, ...invoices, ...pages];
    activeResultIndex.value = searchResults.value.length > 0 ? 0 : -1;

    // "عرض الكل" — تُعرض فقط للفئة التي تحتوي فعليًا على نتائج أكثر من
    // الـ 4 المعروضة هنا بالمعاينة السريعة، وباستخدام نص البحث الأصلي كما
    // كتبه الأدمن (بعكس روابط النتائج الفردية أعلاه التي تبحث عن عنصر بعينه).
    // ملاحظة إصلاح: العدد الإجمالي يأتي متداخلاً بـ meta.total (شكل استجابة
    // Resource::collection()->response()->getData(true))، وليس total مباشرة
    // على المستوى الأعلى — الشرط السابق كان يقارن دائمًا نفس الرقم بنفسه
    // ولا يظهر الرابط أبدًا مهما كان عدد النتائج الفعلي.
    const links = [];
    const generatorsTotal = generatorsPayload.meta?.total ?? generatorsPayload.total ?? generators.length;
    const subscribersTotal = subscribersPayload.meta?.total ?? subscribersPayload.total ?? subscribers.length;
    const invoicesTotal = invoicesPayload.meta?.total ?? invoicesPayload.total ?? invoices.length;

    if (generatorsTotal > generators.length) {
      links.push({ label: t("menu.generator_owners"), to: { name: "admin.generator-owners", query: { q: query } } });
    }
    if (subscribersTotal > subscribers.length) {
      links.push({ label: t("menu.subscribers"), to: { name: "admin.subscribers", query: { q: query } } });
    }
    if (invoicesTotal > invoices.length) {
      links.push({ label: t("menu.invoices"), to: { name: "admin.invoices", query: { q: query } } });
    }
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

/* ---------------- التنقل بالكيبورد داخل نتائج البحث ---------------- */
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
    activeResultIndex.value =
      (activeResultIndex.value - 1 + searchResults.value.length) % searchResults.value.length;
  } else if (e.key === "Enter") {
    e.preventDefault();
    const selected = searchResults.value[activeResultIndex.value];
    if (selected) goToResult(selected);
  } else if (e.key === "Escape") {
    clearSearch();
    searchInputEl.value?.blur();
  }
}

/* ---------------- إضافة سريعة (روابط حقيقية) ---------------- */
const visibleQuickActions = computed(() => quickActions.filter((a) => !a.permission || can(a.permission)));
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

/* ---------------- المحادثات (بيانات حقيقية) ---------------- */
const isMessagesOpen = ref(false);
const conversations = ref([]);
const isLoadingConversations = ref(true);
const unreadConversationsCount = ref(0);

async function fetchUnreadConversationsCount() {
  try {
    const { data } = await conversationService.unreadCount();
    unreadConversationsCount.value = data.data.unread_count ?? 0;
  } catch {
    unreadConversationsCount.value = 0;
  }
}

async function fetchConversations() {
  isLoadingConversations.value = true;
  try {
    const { data } = await conversationService.list({ per_page: 6 });
    const payload = data.data;
    conversations.value = payload.data ?? payload;
  } finally {
    isLoadingConversations.value = false;
  }
}

function goToConversation(conv) {
  isMessagesOpen.value = false;
  router.push({ name: "admin.messages", query: { open: conv.id } });
}

/* ---------------- الإشعارات ---------------- */
const isNotifOpen = ref(false);
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
function notifMeta(type) {
  return NOTIF_ICONS[type] || { icon: "fa-bell", color: "#52733D" };
}
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

/* ---------------- إغلاق القوائم عند الضغط خارجها ---------------- */
function closeAllPanels() {
  isSearchOpen.value = false;
  isQuickAddOpen.value = false;
  isNotifOpen.value = false;
  isMessagesOpen.value = false;
}
function onDocumentClick(e) {
  if (!e.target.closest("[data-dropdown-scope]")) closeAllPanels();
}
onMounted(() => document.addEventListener("click", onDocumentClick));
onUnmounted(() => document.removeEventListener("click", onDocumentClick));

/* ---------------- اختصار لوحة مفاتيح Ctrl+K / Cmd+K لفتح البحث فورًا ---------------- */
function onGlobalKeydown(e) {
  // ملاحظة: Ctrl+K وحده محجوز من متصفحات Chrome/Edge نفسها (بيفتح خانة البحث
  // بشريط العنوان) ولا يصل أبدًا لصفحة الويب — لذلك نقبل أيضًا Ctrl+Shift+K
  // كبديل مضمون لا يتعارض مع أي اختصار متصفح معروف.
  // FIX: e.key مش مضمون دايمًا (بعض الأحداث الاصطناعية — إضافات المتصفح،
  // أدوات إدخال خاصة — بترسل keydown بدون key محدَّد) فكانت ترمي استثناء
  // "Cannot read properties of undefined (reading 'toLowerCase')" بالكونسول.
  const key = (e.key ?? "").toLowerCase();
  const isPlainK = (e.ctrlKey || e.metaKey) && !e.shiftKey && key === "k";
  const isShiftK = (e.ctrlKey || e.metaKey) && e.shiftKey && key === "k";
  if (!isPlainK && !isShiftK) return;

  e.preventDefault();
  searchInputEl.value?.focus();
  if (searchQuery.value) isSearchOpen.value = true;
}
onMounted(() => document.addEventListener("keydown", onGlobalKeydown));
onUnmounted(() => document.removeEventListener("keydown", onGlobalKeydown));

function toggleMessages() {
  isMessagesOpen.value = !isMessagesOpen.value;
  isNotifOpen.value = false;
  isQuickAddOpen.value = false;
}
function toggleNotif() {
  isNotifOpen.value = !isNotifOpen.value;
  isMessagesOpen.value = false;
  isQuickAddOpen.value = false;
}
function toggleQuickAdd() {
  isQuickAddOpen.value = !isQuickAddOpen.value;
  isNotifOpen.value = false;
  isMessagesOpen.value = false;
}
</script>

<template>
  <header class="sticky top-0 z-50 bg-white/75 dark:bg-[#15171a]/85 backdrop-blur-xl border-b border-[#eee8da] dark:border-white/10">
    <div class="flex items-center justify-between gap-3 px-4 lg:px-6 py-2.5">
      <div class="flex items-center gap-3 flex-1">
        <button type="button" class="md:hidden icon-btn !bg-[#EBF1E7] dark:!bg-white/5" :aria-label="t('common.open_menu')" @click="ui.openMobile()">
          <Menu aria-hidden="true" />
        </button>

        <div class="relative hidden md:block max-w-sm w-full" data-dropdown-scope>
          <Search class="absolute top-1/2 -translate-y-1/2 start-3.5 text-[#9a9d97] dark:text-[#8f938a] text-xs" aria-hidden="true" />
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
          <button :aria-label="$t('common.clear_search')" v-if="searchQuery" type="button" class="absolute top-1/2 -translate-y-1/2 end-3 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#D9534F] text-xs" @click="clearSearch">
            <CircleX aria-hidden="true" />
          </button>
          <kbd
            v-if="!searchQuery"
            class="absolute top-1/2 -translate-y-1/2 end-2.5 text-[9.5px] font-bold text-[#9a9d97] dark:text-[#8f938a] bg-[#e7e2d6]/70 dark:bg-white/10 border border-[#e0dccf] dark:border-white/10 rounded px-1.5 py-0.5 pointer-events-none"
          >Ctrl+Shift+K</kbd>

          <div v-if="isSearchOpen" class="absolute top-[calc(100%+8px)] start-0 w-full min-w-[22rem] max-w-[calc(100vw-2rem)] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl max-h-96 overflow-y-auto">
            <div v-if="isSearching" class="dropdown-empty">{{ t("common.searching") }}</div>
            <div v-else-if="searchResults.length === 0" class="dropdown-empty flex flex-col items-center gap-2">
              <Search class="text-lg text-[#c9c4b4]" aria-hidden="true" />
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
                {{ $t("common.view_all_in", { label: link.label }) }}
                <ArrowRight class="ltr:inline-block rtl:hidden" aria-hidden="true" style="font-size:9px" />
                <ArrowLeft class="rtl:inline-block ltr:hidden" aria-hidden="true" style="font-size:9px" />
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="hidden xl:flex items-center gap-2 text-[11.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full px-3.5 py-1.5">
          <Circle class="text-[6px] text-[#28A745]" aria-hidden="true" /> {{ t("common.system_running") }}
          <span class="w-px h-3 bg-[#e0dccf] dark:bg-white/10 mx-1"></span>
          <span>{{ liveClock }}</span>
        </span>

        <div class="relative" data-dropdown-scope>
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
                :key="q.route"
                :to="{ name: q.route }"
                class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[12px] font-semibold hover:bg-[#EBF1E7] dark:hover:bg-white/5 transition-colors"
                @click="isQuickAddOpen = false"
              >
                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[11px] shrink-0" :style="{ background: `linear-gradient(135deg, ${q.c1}, ${q.c2})` }">
                  <AppIcon :name="q.icon" />
                </span>
                <span>{{ q.label }}</span>
              </RouterLink>
            </div>
          </div>
        </div>

        <button type="button" class="icon-btn hidden md:flex" :title="t('common.fullscreen')" :aria-label="t('common.fullscreen')" @click="toggleFullscreen">
          <Shrink aria-hidden="true" v-if="isFullscreen" /><Expand aria-hidden="true" v-else />
        </button>

        <div v-if="can('conversations.view')" class="relative" data-dropdown-scope>
          <button type="button" class="icon-btn relative" :title="t('common.conversations')" :aria-label="t('common.conversations')" @click.stop="toggleMessages">
            <MessageCircleMore aria-hidden="true" />
            <span v-if="unreadConversationsCount > 0" class="notif-dot"></span>
          </button>
          <div v-if="isMessagesOpen" class="max-md:fixed max-md:inset-x-3 max-md:top-16 md:absolute md:start-0 md:top-[calc(100%+10px)] w-auto md:w-[19rem] max-h-[26rem] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-[#eee8da] dark:border-white/10">
              <span class="text-[12.5px] font-extrabold">{{ t("common.conversations") }}</span>
              <RouterLink :to="{ name: 'admin.messages' }" class="text-[10.5px] font-bold text-[#8A6D1F] hover:underline" @click="isMessagesOpen = false">
                {{ t("common.view_all") }}
              </RouterLink>
            </div>
            <div class="overflow-y-auto flex-1 divide-y divide-[#eee8da] dark:divide-white/5">
              <div v-if="isLoadingConversations" class="dropdown-empty">{{ t("common.loading") }}</div>
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
{{ c.other_participant?.name?.charAt(0) ?? "?" }}                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-[12px] font-bold truncate">{{ c.other_participant?.name ?? "—" }}</p>
                  <p v-if="c.latest_message" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate">{{ c.latest_message.text }}</p>
                  <span v-if="c.latest_message" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(c.latest_message.created_at) }}</span>
                </div>
              </button>
            </div>
          </div>
        </div>

        <div class="relative" data-dropdown-scope>
          <button type="button" class="icon-btn relative" :title="t('common.notifications')" :aria-label="t('common.notifications')" @click.stop="toggleNotif">
            <Bell aria-hidden="true" />
            <span v-if="notificationStore.unreadCount > 0" class="notif-dot"></span>
          </button>
          <div v-if="isNotifOpen" class="max-md:fixed max-md:inset-x-3 max-md:top-16 md:absolute md:start-0 md:top-[calc(100%+10px)] w-auto md:w-[19rem] max-h-[26rem] glass-card !bg-white/95 dark:!bg-[#1c1e20]/95 z-50 shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-[#eee8da] dark:border-white/10">
              <span class="text-[12.5px] font-extrabold">{{ t("common.notifications") }}</span>
              <button
                v-if="notificationStore.unreadCount > 0"
                type="button"
                class="text-[10.5px] font-bold text-[#8A6D1F] hover:underline"
                @click="notificationStore.markAllAsRead()"
              >
                {{ t("common.mark_all_read") }}
              </button>
            </div>
            <div class="overflow-y-auto flex-1 divide-y divide-[#eee8da] dark:divide-white/5">
              <div v-if="notificationStore.loading" class="dropdown-empty">{{ t("common.loading") }}</div>
              <div v-else-if="notificationStore.notifications.length === 0" class="dropdown-empty">{{ t("common.no_notifications") }}</div>
              <button
                v-for="n in notificationStore.notifications"
                :key="n.id"
                type="button"
                class="dropdown-row w-full text-start"
                :class="{ unread: !n.is_read }"
                @click="!n.is_read && notificationStore.markAsRead(n.id)"
              >
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] shrink-0" :style="{ background: notifMeta(n.type).color }">
                  <AppIcon :name="notifMeta(n.type).icon" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-[12px] font-bold truncate">{{ n.title ?? "—" }}</p>
                  <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate">{{ n.message }}</p>
                  <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(n.created_at) }}</span>
                </div>
              </button>
            </div>
          </div>
        </div>

        <div class="icon-btn-group">
          <button type="button" class="icon-btn" :title="t('common.dark_mode')" :aria-label="t('common.toggle_theme')" @click="ui.toggleTheme()">
            <Sun aria-hidden="true" v-if="ui.isDark" /><Moon aria-hidden="true" v-else />
          </button>
          <div class="w-px h-4 bg-[#e0dccf] dark:bg-white/10"></div>
          <button type="button" class="icon-btn text-[10.5px] font-bold" :title="t('common.toggle_language')" :aria-label="t('common.toggle_language')" @click="toggleLanguage">
            {{ langButtonLabel }}
          </button>
        </div>
      </div>
    </div>
  </header>
</template>