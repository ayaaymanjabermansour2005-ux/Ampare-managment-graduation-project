import { computed } from "vue";
import menuConfig from "@/config/menu.js";
import { usePermissions } from "./usePermissions";

export function useMenu() {
  const { hasRole, can } = usePermissions();

  const visibleMenu = computed(() =>
    menuConfig.filter((item) => {
      const roleOk = !item.roles || item.roles.some((r) => hasRole(r));
      const permOk = !item.permission || can(item.permission);
      return roleOk && permOk;
    }),
  );

  return { visibleMenu };
}
