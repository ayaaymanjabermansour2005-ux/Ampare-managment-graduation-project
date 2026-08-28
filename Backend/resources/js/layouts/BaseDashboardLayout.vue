<script setup>
import { computed, watch, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import AppNavbar from "@/components/navbar/AppNavbar.vue";
import AppSidebar from "@/components/sidebar/AppSidebar.vue";
import { useAdminUiStore } from "@/stores/adminUi";
import { Menu } from "@lucide/vue";

const ui = useAdminUiStore();
const { t, locale } = useI18n();

document.documentElement.classList.add("admin-shell");
watch(
  () => ui.isDark,
  (isDark) => {
    document.documentElement.classList.toggle("dark", isDark);
  },
  { immediate: true },
);
onUnmounted(() => {
  document.documentElement.classList.remove("admin-shell", "dark");
});

const mainAreaClasses = computed(() => [
  "flex-1 flex flex-col min-w-0 transition-all duration-300",
  ui.isCollapsed ? "lg:ms-[84px]" : "lg:ms-[264px]",
  { "mobile-pushed": ui.isMobileOpen },
]);

const dir = computed(() => (locale.value === "ar" ? "rtl" : "ltr"));
</script>

<template>
  <div class="admin-shell" :dir="dir" :class="{ dark: ui.isDark }">
    <div class="min-h-screen flex bg-[#f8faf7] dark:bg-[#0f1112] text-[#333] dark:text-[#eee] transition-colors duration-500">
      <!-- FIX: (item 18) كانت هذه الطبقة الوحيدة من الثلاث (Base/Dashboard/Admin)
           بدون print-hidden — الفواتير المطبوعة من صفحات owner/subscriber/
           technician (اللي بتستخدم هاي الطبقة) كانت تُطبَع مع السايدبار
           والنافبار ظاهرين، خلافًا للطبقتين الأخريين. -->
      <AppSidebar class="print-hidden" />

      <div :class="mainAreaClasses">
        <AppNavbar class="print-hidden">
          <template #mobile-toggle>
            <button
              type="button"
              class="lg:hidden icon-btn !bg-[#EBF1E7] dark:!bg-white/5"
              :aria-label="t('common.open_menu')"
              @click="ui.openMobile()"
            >
              <Menu aria-hidden="true" />
            </button>
          </template>
        </AppNavbar>

        <main class="flex-1 p-4 lg:p-6 space-y-6 max-w-[1600px] mx-auto w-full overflow-x-auto">
          <RouterView v-slot="{ Component }">
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              mode="out-in"
            >
              <component :is="Component" />
            </Transition>
          </RouterView>
        </main>
      </div>
    </div>
  </div>
</template>
