import { useAuthStore } from "@/stores/auth";

export function usePermissions() {
  const authStore = useAuthStore();

  function can(permission) {
    return authStore.can(permission);
  }

  function canAny(permissionsList = []) {
    return permissionsList.some((p) => authStore.can(p));
  }

  function canAll(permissionsList = []) {
    return permissionsList.every((p) => authStore.can(p));
  }

  function hasRole(role) {
    return authStore.hasRole(role);
  }

  return { can, canAny, canAll, hasRole };
}
