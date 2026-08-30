import { normalizeApiError } from "@/utils/normalizeApiError";
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
      error.value = normalizeApiError(err, t("roles_permissions_page.load_error")).message;
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
      error.value = normalizeApiError(err, t("roles_permissions_page.save_error")).message;
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
