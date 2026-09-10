import { ref } from "vue";
import { useI18n } from "vue-i18n";
import invoiceService from "@/services/invoiceService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * يدير قائمة الفواتير بلوحة تحكم الأدمن: الجلب المُصفَّح (حالة/بحث)، والإلغاء
 * وإعادة الإصدار. التصحيح (correct) يبقى خارج هذا composable لأنه منطق
 * نافذة منفصلة (InvoiceCorrectionModal.vue) لها حالتها الخاصة.
 */
export function useAdminInvoices() {
  const { t } = useI18n();
  const invoices = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1 });
  const isLoading = ref(false);
  const error = ref(null);
  const statusFilter = ref("all");
  const search = ref("");

  /** يجلب صفحة من الفواتير مطبَّقًا عليها فلتر الحالة والبحث الحاليين. */
  async function fetchInvoices(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const params = { page };
      if (statusFilter.value !== "all") params.status = statusFilter.value;
      if (search.value) params.search = search.value;

      const { data } = await invoiceService.list(params);
      const payload = data.data;
      invoices.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("invoices_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounce = null;
  /** يعيد جلب الصفحة الأولى بعد توقّف الكتابة بـ350ms (بحث حي بدون إغراق الباك اند بالطلبات). */
  function onSearchInput() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchInvoices(1), 350);
  }

  /** يبدّل فلتر الحالة ويعيد جلب الصفحة الأولى بالفلتر الجديد. */
  function applyFilter(status) {
    statusFilter.value = status;
    fetchInvoices(1);
  }

  /** يستبدل فاتورة واحدة بنسختها المحدَّثة داخل القائمة المحمَّلة، دون إعادة جلب الصفحة كاملة. */
  function replaceInvoice(updated) {
    const index = invoices.value.findIndex((i) => i.id === updated.id);
    if (index !== -1) invoices.value[index] = updated;
  }

  const cancellingId = ref(null);
  const cancelError = ref(null);

  /** يلغي فاتورة، ويحدّث نسختها بالقائمة فور نجاح الإلغاء. */
  async function cancelInvoice(id) {
    cancellingId.value = id;
    cancelError.value = null;
    try {
      const { data } = await invoiceService.cancel(id);
      replaceInvoice(data.data);
      return true;
    } catch (err) {
      cancelError.value =
        normalizeApiError(err, t("invoices_page.cancel_error")).message;
      return false;
    } finally {
      cancellingId.value = null;
    }
  }

  const reissuingId = ref(null);
  const reissueError = ref(null);

  /** يعيد إصدار فاتورة ملغاة كفاتورة جديدة، ويضيفها بأعلى القائمة المحمَّلة. */
  async function reissueInvoice(id) {
    reissuingId.value = id;
    reissueError.value = null;
    try {
      const { data } = await invoiceService.reissue(id);
      invoices.value.unshift(data.data);
      return true;
    } catch (err) {
      reissueError.value =
        normalizeApiError(err, t("invoices_page.reissue_error")).message;
      return false;
    } finally {
      reissuingId.value = null;
    }
  }

  return {
    invoices,
    pagination,
    isLoading,
    error,
    statusFilter,
    search,
    fetchInvoices,
    onSearchInput,
    applyFilter,
    replaceInvoice,
    cancellingId,
    cancelError,
    cancelInvoice,
    reissuingId,
    reissueError,
    reissueInvoice,
  };
}
