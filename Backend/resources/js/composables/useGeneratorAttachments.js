import { ref } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useGeneratorAttachments() {
  const { t } = useI18n();
  const attachments = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchAttachments(generatorId) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await generatorService.attachments(generatorId);
      attachments.value = data.data;
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_generators.attachments_load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const isUploading = ref(false);
  const uploadError = ref(null);

  async function uploadAttachment(generatorId, { documentType, file, description }) {
    isUploading.value = true;
    uploadError.value = null;
    try {
      const formData = new FormData();
      formData.append("document_type", documentType);
      formData.append("file", file);
      if (description) formData.append("description", description);

      const { data } = await generatorService.storeAttachment(generatorId, formData);
      attachments.value.unshift(data.data);
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("owner_generators.attachment_upload_error"));
      uploadError.value = Object.keys(normalized.fieldErrors).length
        ? normalized.fieldErrors
        : { file: [normalized.message] };
      return false;
    } finally {
      isUploading.value = false;
    }
  }

  const deletingAttachmentId = ref(null);

  async function deleteAttachment(attachmentId) {
    deletingAttachmentId.value = attachmentId;
    try {
      await generatorService.destroyAttachment(attachmentId);
      attachments.value = attachments.value.filter((a) => a.id !== attachmentId);
      return true;
    } catch {
      return false;
    } finally {
      deletingAttachmentId.value = null;
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
    deletingAttachmentId,
    deleteAttachment,
  };
}