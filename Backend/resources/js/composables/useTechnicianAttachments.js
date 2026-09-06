import { ref } from "vue";
import { useI18n } from "vue-i18n";
import technicianService from "@/services/technicianService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * FIX (تدقيق شامل للوحة الأدمن — بند 5): GET/POST /technicians/{id}/attachments
 * جاهزان بالكامل بالباك اند (شهادة/هوية الفني) لكن بدون أي واجهة تستخدمهما
 * إطلاقًا. نفس بنية useGeneratorAttachments.js، بدون حذف (لا يوجد endpoint
 * حذف مخصَّص لمرفقات الفنيين مربوط بالفرونت حاليًا — بعكس مرفقات المولدات).
 */
export function useTechnicianAttachments() {
  const { t } = useI18n();
  const attachments = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchAttachments(technicianId) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await technicianService.attachments(technicianId);
      attachments.value = data.data;
    } catch (err) {
      error.value = normalizeApiError(err, t("admin_technicians_page.attachments_load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const isUploading = ref(false);
  const uploadError = ref(null);

  async function uploadAttachment(technicianId, { documentType, file, description }) {
    isUploading.value = true;
    uploadError.value = null;
    try {
      const formData = new FormData();
      formData.append("document_type", documentType);
      formData.append("file", file);
      if (description) formData.append("description", description);

      const { data } = await technicianService.storeAttachment(technicianId, formData);
      attachments.value.unshift(data.data);
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("admin_technicians_page.attachment_upload_error"));
      uploadError.value = Object.keys(normalized.fieldErrors).length
        ? normalized.fieldErrors
        : { file: [normalized.message] };
      return false;
    } finally {
      isUploading.value = false;
    }
  }

  return {
    attachments,
    isLoading,
    error,
    fetchAttachments,
    isUploading,
    uploadError,
    uploadAttachment,
  };
}
