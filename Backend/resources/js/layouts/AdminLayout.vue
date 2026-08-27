<script setup>
import { computed, watch, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import AdminSidebar from "@/components/admin/AdminSidebar.vue";
import AdminTopbar from "@/components/admin/AdminTopbar.vue";
import { useAdminUiStore } from "@/stores/adminUi";
import { useRealtimeNotifications } from "@/composables/useRealtimeNotifications";

const ui = useAdminUiStore();
const { locale } = useI18n();
useRealtimeNotifications();

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
  "flex-1 transition-all duration-300",
  ui.isCollapsed ? "lg:ms-[84px]" : "lg:ms-[264px]",
  { "mobile-pushed": ui.isMobileOpen },
]);

const dir = computed(() => (locale.value === "ar" ? "rtl" : "ltr"));
</script>

<template>
  <div class="admin-shell" :dir="dir" :class="{ dark: ui.isDark }">
    <div class="flex min-h-screen bg-[#f8faf7] dark:bg-[#0f1112] text-[#333] dark:text-[#eee] transition-colors duration-500">
      <AdminSidebar class="print-hidden" />

      <div id="adminMainArea" :class="mainAreaClasses">
        <AdminTopbar class="print-hidden" />

        <main class="p-4 lg:p-6 space-y-6 max-w-[1600px] mx-auto overflow-x-hidden">
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