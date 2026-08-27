import { watch, onUnmounted } from "vue";
import { useAdminUiStore } from "@/stores/adminUi";

export function useThemeSync(shellClass) {
  const ui = useAdminUiStore();
  const root = document.documentElement;

  if (shellClass) root.classList.add(shellClass);

  watch(
    () => ui.isDark,
    (isDark) => root.classList.toggle("dark", isDark),
    { immediate: true },
  );

  onUnmounted(() => {
    root.classList.remove("dark");
    if (shellClass) root.classList.remove(shellClass);
  });

  return ui;
}
