import { defineStore } from "pinia";
import { ref } from "vue";

const THEME_KEY = "ampere-admin-theme";
const COLLAPSE_KEY = "ampere-admin-sidebar-collapsed";

export const useAdminUiStore = defineStore("adminUi", () => {
  const isDark = ref(readStoredTheme() === "dark");
  const isCollapsed = ref(readStoredCollapse());
  const isMobileOpen = ref(false);

  function readStoredTheme() {
    try {
      return localStorage.getItem(THEME_KEY) || "light";
    } catch {
      return "light";
    }
  }

  function readStoredCollapse() {
    try {
      return localStorage.getItem(COLLAPSE_KEY) === "1";
    } catch {
      return false;
    }
  }

  function toggleTheme() {
    isDark.value = !isDark.value;
    try {
      localStorage.setItem(THEME_KEY, isDark.value ? "dark" : "light");
    } catch {
    }
  }

  function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
    try {
      localStorage.setItem(COLLAPSE_KEY, isCollapsed.value ? "1" : "0");
    } catch {
    }
  }

  function openMobile() {
    isMobileOpen.value = true;
  }

  function closeMobile() {
    isMobileOpen.value = false;
  }

  return {
    isDark,
    isCollapsed,
    isMobileOpen,
    toggleTheme,
    toggleCollapse,
    openMobile,
    closeMobile,
  };
});