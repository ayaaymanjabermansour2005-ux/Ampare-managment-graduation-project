<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import complaintService from "@/services/complaintService";
import { CircleCheck, FileVideoCamera } from "@lucide/vue";

const router = useRouter();
const { t } = useI18n();

const form = reactive({
  subject: "",
  description: "",
});

const isSubmitting = ref(false);
const submitError = ref(null);
const submitted = ref(false);

// ===================== مرفق اختياري (صورة/فيديو) — يُرفع عبر نفس
// AttachmentService::upload() المستخدم لباقي المرفقات بالنظام (الشكاوى
// تدعمه فعليًا عبر HasAttachments لكنه لم يكن مربوطًا بأي واجهة من قبل) =====================
const attachmentFile = ref(null);
const attachmentPreview = ref(null);
const attachmentInput = ref(null);
const attachmentWarning = ref(null);

function onAttachmentChange(e) {
  const file = e.target.files?.[0];
  if (!file) {
    attachmentFile.value = null;
    attachmentPreview.value = null;
    return;
  }
  attachmentFile.value = file;
  attachmentPreview.value = file.type.startsWith("image/") ? URL.createObjectURL(file) : null;
}

function clearAttachment() {
  attachmentFile.value = null;
  attachmentPreview.value = null;
  if (attachmentInput.value) attachmentInput.value.value = "";
}

async function handleSubmit() {
  isSubmitting.value = true;
  submitError.value = null;
  attachmentWarning.value = null;
  try {
    const { data } = await complaintService.create({ ...form });
    const complaintId = data.data?.id;

    if (attachmentFile.value && complaintId) {
      try {
        const body = new FormData();
        body.append("file", attachmentFile.value);
        body.append(
          "document_type",
          attachmentFile.value.type.startsWith("video/") ? "complaint_video" : "complaint_image",
        );
        await complaintService.uploadAttachment(complaintId, body);
      } catch {
        // الشكوى اتسجلت بنجاح أصلاً؛ فشل رفع المرفق وحده لا يمنع إتمام العملية.
        attachmentWarning.value = t("technician_complaint.attachment_upload_error");
      }
    }

    submitted.value = true;
    setTimeout(() => router.back(), attachmentWarning.value ? 2200 : 1200);
  } catch (err) {
    submitError.value = err.response?.data?.message ?? t("technician_complaint.default_error");
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <p class="text-xs font-medium text-secondary-600 tracking-wide mb-1">
        {{ t("technician_complaint.eyebrow") }}
      </p>
      <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ t("technician_complaint.title") }}</h1>
    </div>

    <div
      v-if="submitted"
      class="bg-success-bg text-success text-sm rounded-lg p-6 text-center"
    >
      <CircleCheck class="text-2xl mb-2 block" aria-hidden="true" />
      {{ t("owner_complaints.submitted_toast_message") }}
      <p v-if="attachmentWarning" class="text-warning text-xs mt-2">{{ attachmentWarning }}</p>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-4">
      <div v-if="submitError" class="bg-danger-bg text-danger text-sm rounded-lg p-3">
        {{ submitError }}
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("owner_complaints.subject_label") }}</label>
        <input
          v-model="form.subject"
          type="text"
          required
          maxlength="191"
          class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("owner_complaints.description_label") }}</label>
        <textarea
          v-model="form.description"
          rows="5"
          required
          maxlength="2000"
          class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">{{ t("technician_complaint.attachment_label") }}</label>
        <div v-if="!attachmentPreview && !attachmentFile" class="flex items-center gap-2">
          <input
            ref="attachmentInput"
            type="file"
            accept="image/*,video/mp4,video/quicktime"
            capture="environment"
            @change="onAttachmentChange"
            class="w-full min-w-0 rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2 text-xs text-gray-700 dark:text-gray-200 file:me-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-500/10 dark:file:text-primary-300"
          />
        </div>
        <div v-else class="flex items-center gap-2.5">
          <img v-if="attachmentPreview" :src="attachmentPreview" class="w-14 h-14 rounded-lg object-cover border border-border dark:border-white/10 shrink-0" />
          <span v-else class="w-14 h-14 rounded-lg border border-border dark:border-white/10 flex items-center justify-center text-gray-400 shrink-0">
            <FileVideoCamera aria-hidden="true" />
          </span>
          <span class="text-xs text-gray-500 dark:text-gray-400 truncate min-w-0 flex-1">{{ attachmentFile?.name }}</span>
          <button type="button" @click="clearAttachment" class="text-xs font-semibold text-danger hover:underline shrink-0">
            {{ t("technician_complaint.remove_attachment") }}
          </button>
        </div>
        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ t("technician_complaint.attachment_hint") }}</p>
      </div>

      <button
        type="submit"
        :disabled="isSubmitting"
        class="w-full py-2.5 rounded-lg text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 disabled:opacity-50 transition"
      >
        {{ isSubmitting ? t("owner_complaints.submitting_ellipsis") : t("owner_complaints.submit_button") }}
      </button>
    </form>
  </div>
</template>
