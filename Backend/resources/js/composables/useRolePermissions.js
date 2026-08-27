import { ref } from "vue";
import { useI18n } from "vue-i18n";
import rolePermissionService from "@/services/rolePermissionService";

export function useRolePermissions() {
  const { t } = useI18n();
  const roles = ref([]);
  const allPermissions = ref([]);
  const isLoading = ref(true);
  const error = ref(null);
  const isSaving = ref(false);

  async function fetchData() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await rolePermissionService.index();
      roles.value = data.data.roles;
      allPermissions.value = data.data.all_permissions;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("roles_permissions_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  async function saveRole(role) {
    isSaving.value = true;
    error.value = null;
    try {
      await rolePermissionService.sync(role.id, role.permissions);
      return true;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("roles_permissions_page.save_error");
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  return {
    roles,
    allPermissions,
    isLoading,
    error,
    isSaving,
    fetchData,
    saveRole,
  };
}
