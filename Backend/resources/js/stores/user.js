import { defineStore } from "pinia";
import { ref } from "vue";
import userService from "@/services/userService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export const useUserStore = defineStore("user", () => {
  const users = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const errors = ref(null);
  // FIX: "errors" (أخطاء تحقّق حقول 422) ما كانت تُعرَض بأي مكان بالواجهة،
  // وما في أصلًا رسالة عامة تُلتقط لفشل التحميل نفسه (شبكة/صلاحيات/500) —
  // فالقائمة كانت تظهر "لا يوجد مستخدمون" بصمت بدل رسالة خطأ حقيقية.
  const error = ref(null);

  async function fetchUsers(params = {}) {
    isLoading.value = true;
    errors.value = null;
    error.value = null;
    try {
      const { data } = await userService.list(params);
      const payload = data.data;
      users.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? users.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      const normalized = normalizeApiError(err, null);
      errors.value = Object.keys(normalized.fieldErrors).length ? normalized.fieldErrors : null;
      error.value = normalized.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  async function updateUser(id, payload) {
    const { data } = await userService.update(id, payload);
    const index = users.value.findIndex((u) => u.id === id);
    if (index !== -1) users.value[index] = data.data;
    return data.data;
  }

  async function fetchUser(id) {
    const { data } = await userService.show(id);
    return data.data;
  }

  async function deleteUser(id) {
    await userService.destroy(id);
    users.value = users.value.filter((u) => u.id !== id);
  }

  async function unlockUser(id) {
    await userService.unlock(id);
    const index = users.value.findIndex((u) => u.id === id);
    if (index !== -1) users.value[index].is_locked = false;
  }

  return {
    users,
    pagination,
    isLoading,
    errors,
    error,
    fetchUsers,
    updateUser,
    fetchUser,
    deleteUser,
    unlockUser,
  };
});