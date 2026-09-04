import { ref } from "vue";
import { useI18n } from "vue-i18n";
import technicianService from "@/services/technicianService";
import userService from "@/services/userService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useAdminTechnicians() {
  const { t } = useI18n();
  const technicians = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);
  const searchTerm = ref("");

  async function fetchTechnicians(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await technicianService.list({
        page,
        search: searchTerm.value || undefined,
      });
      const payload = data.data;
      technicians.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? technicians.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("admin_technicians_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const isCreating = ref(false);
  const createError = ref(null);

  /**
   * إنشاء فني جديد نيابةً عن مالك مولد — owner_id إلزامي ويُتحقّق منه
   * أيضًا من الخادم (AdminCreateTechnicianForOwnerAction) قبل أي إنشاء.
   */
  async function createTechnician(payload) {
    isCreating.value = true;
    createError.value = null;
    try {
      await userService.createTechnician(payload);
      await fetchTechnicians(1);
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("admin_technicians_page.create_error"));
      createError.value = { message: normalized.message, errors: normalized.fieldErrors };
      return false;
    } finally {
      isCreating.value = false;
    }
  }

  const isSaving = ref(false);
  const saveError = ref(null);

  async function updateTechnician(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      const supportedPayload = {};
      if (payload.status !== undefined) supportedPayload.status = payload.status;
      if (payload.notes !== undefined) supportedPayload.notes = payload.notes;
      if (payload.name !== undefined) supportedPayload.name = payload.name;
      if (payload.email !== undefined) supportedPayload.email = payload.email;
      if (payload.phone !== undefined) supportedPayload.phone = payload.phone;

      const { data } = await technicianService.update(id, supportedPayload);
      const index = technicians.value.findIndex((t) => t.id === id);
      if (index !== -1) technicians.value[index] = data.data;
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("admin_technicians_page.update_error"));
      saveError.value = { message: normalized.message, errors: normalized.fieldErrors };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  const deletingId = ref(null);
  const deleteError = ref(null);

  async function deleteTechnician(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await technicianService.destroy(id);
      technicians.value = technicians.value.filter((t) => t.id !== id);
      return true;
    } catch (err) {
      deleteError.value = normalizeApiError(err, t("admin_technicians_page.delete_error")).message;
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  async function unlockTechnician(technicianId) {
    const technician = technicians.value.find((t) => t.id === technicianId);
    if (!technician?.user_id) return false;

    try {
      // FIX: (item 15) userService مستورَد static أصلًا بأعلى الملف —
      // كان في import() ديناميكي زائد لنفس الموديول هون بدون داعٍ.
      await userService.unlock(technician.user_id);
      const index = technicians.value.findIndex((t) => t.id === technicianId);
      if (index !== -1) technicians.value[index].is_locked = false;
      return true;
    } catch (err) {
      deleteError.value = normalizeApiError(err, t("admin_technicians_page.unlock_error")).message;
      return false;
    }
  }

  return {
    technicians,
    pagination,
    isLoading,
    error,
    searchTerm,
    fetchTechnicians,
    isCreating,
    createError,
    createTechnician,
    isSaving,
    saveError,
    updateTechnician,
    deletingId,
    deleteError,
    deleteTechnician,
    unlockTechnician,
  };
}