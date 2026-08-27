<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { useThemeSync } from "@/composables/useThemeSync";
import { useConnectivityStore } from "@/stores/connectivity";
import { setLocale } from "@/i18n";
import TechnicianBottomNav from "@/components/technician/TechnicianBottomNav.vue";
import TechnicianMoreSheet from "@/components/technician/TechnicianMoreSheet.vue";
import { Moon, RefreshCw, Sun } from "@lucide/vue";


const ui = useThemeSync("admin-shell");
const { t, locale } = useI18n();
const router = useRouter();

const connectivityStore = useConnectivityStore();
const { isOnline, pendingCount } = storeToRefs(connectivityStore);

const langButtonLabel = computed(() => (locale.value === "ar" ? "EN" : "AR"));
function toggleLanguage() {
  setLocale(locale.value === "ar" ? "en" : "ar");
}

const dir = computed(() => (locale.value === "ar" ? "rtl" : "ltr"));
const moreOpen = ref(false);

function goHome() {
  router.push({ name: "technician.dashboard" });
}
</script>

<template>
  <div class="admin-shell" :dir="dir" :class="{ dark: ui.isDark }">
    <div class="min-h-screen flex flex-col bg-[#f8faf7] dark:bg-[#0f1112] text-[#333] dark:text-[#eee] transition-colors duration-500">
      <header class="print-hidden sticky top-0 z-30 bg-surface/95 dark:bg-[#1c1e20]/95 backdrop-blur border-b border-border">
        <div class="max-w-lg mx-auto flex items-center gap-2.5 px-4 py-3">
          <button type="button" @click="goHome" class="flex items-center gap-2.5 flex-1 min-w-0">
            <div class="logo-box w-9 h-9 rounded-xl flex items-center justify-center shrink-0 overflow-hidden p-1">
              <img
                src="/images/logo.png"
                :alt="t('auth.logo_alt')"
                class="w-full h-full object-contain"
                onerror="this.replaceWith(Object.assign(document.createElement('i'), { className: 'fa-solid fa-bolt text-lg text-[#8A6D1F] dark:text-[#F4E0A5]' }))"
              />
            </div>
            <span class="text-base font-extrabold brand-gradient-text truncate">{{ t("common.brand") }}</span>
          </button>

          <button
            type="button"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5 shrink-0 text-[10.5px] font-bold"
            :title="t('common.toggle_language')"
            @click="toggleLanguage"
          >
            {{ langButtonLabel }}
          </button>

          <button
            type="button"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5 shrink-0"
            :title="t('common.toggle_theme')"
            @click="ui.toggleTheme()"
          >
            <Sun aria-hidden="true" v-if="ui.isDark" /><Moon aria-hidden="true" v-else />
          </button>
        </div>

        <div
          v-if="!isOnline"
          class="flex items-center justify-center gap-1.5 text-[10px] font-medium text-warning bg-warning-bg px-2.5 py-1"
          role="status"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
          <span>{{ t("common.offline_saving_locally") }}</span>
        </div>
        <div
          v-else-if="pendingCount > 0"
          class="flex items-center justify-center gap-1.5 text-[10px] font-medium text-info bg-info-bg px-2.5 py-1"
        >
          <RefreshCw class="text-[10px] animate-spin" aria-hidden="true" />
          <span>{{ t("common.syncing_operations", { count: pendingCount }) }}</span>
        </div>
      </header>

      <main class="flex-1 max-w-lg mx-auto w-full p-4 pb-24">
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

      <TechnicianBottomNav class="print-hidden" @open-more="moreOpen = true" />
      <TechnicianMoreSheet :open="moreOpen" @close="moreOpen = false" />
    </div>
  </div>
</template>
