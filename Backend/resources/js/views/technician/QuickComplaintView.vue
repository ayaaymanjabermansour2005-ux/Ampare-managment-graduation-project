<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import complaintService from "@/services/complaintService";
import { vReveal } from "@/directives/reveal";
import { CircleCheck, FileVideoCamera, LoaderCircle, MessageSquareWarning, Send, X } from "@lucide/vue";

const router = useRouter();
const { t } = useI18n();

const form = reactive({
  subject: "",
  description: "",
});

const isSubmitting = ref(false);
const submitError = ref(null);
const submitted = ref(false);

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
        attachmentWarning.value = t("technician_complaint.attachment_upload_error");
      }
    }

    submitted.value = true;
    setTimeout(() => router.back(), attachmentWarning.value ? 2200 : 1200);
  } catch (err) {
    submitError.value = normalizeApiError(err, t("technician_complaint.default_error")).message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="space-y-5">
    <!-- ===== رأس الصفحة ===== -->
    <section v-reveal class="glass-card p-5 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-56 h-56 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[90px] pointer-events-none"></div>
      <div class="relative flex items-center gap-3">
        <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#52733D] text-white flex items-center justify-center text-base shrink-0">
          <MessageSquareWarning aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-0.5">{{ t("technician_complaint.eyebrow") }}</p>
          <h1 class="text-lg font-extrabold truncate">{{ t("technician_complaint.title") }}</h1>
        </div>
      </div>
    </section>

    <section v-reveal v-if="submitted" class="glass-card p-8 text-center text-[13px] font-bold text-[#1f7a37] dark:text-[#7fe19c]">
      <CircleCheck class="text-3xl mb-2 block mx-auto" aria-hidden="true" />
      {{ t("owner_complaints.submitted_toast_message") }}
      <p v-if="attachmentWarning" class="text-[11.5px] font-normal text-[#8A6D1F] dark:text-[#D4AF37] mt-2">{{ attachmentWarning }}</p>
    </section>

    <form v-else @submit.prevent="handleSubmit" v-reveal class="glass-card p-5 space-y-3.5">
      <div v-if="submitError" class="alert-box"><X class="shrink-0" aria-hidden="true" /> {{ submitError }}</div>

      <div>
        <label class="field-label">{{ t("owner_complaints.subject_label") }}</label>
        <input v-model="form.subject" type="text" required maxlength="191" class="field-input" />
      </div>

      <div>
        <label class="field-label">{{ t("owner_complaints.description_label") }}</label>
        <textarea v-model="form.description" rows="5" required maxlength="2000" class="field-input resize-none"></textarea>
      </div>

      <div>
        <label class="field-label">{{ t("technician_complaint.attachment_label") }}</label>
        <div v-if="!attachmentPreview && !attachmentFile">
          <input
            ref="attachmentInput"
            type="file"
            accept="image/*,video/mp4,video/quicktime"
            capture="environment"
            @change="onAttachmentChange"
            class="field-input !py-1.5 text-[11px] file:me-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-[11px] file:font-bold file:bg-[#EBF1E7] file:text-[#3E582E] dark:file:bg-white/10 dark:file:text-[#a8d19a]"
          />
        </div>
        <div v-else class="flex items-center gap-2.5">
          <img v-if="attachmentPreview" :src="attachmentPreview" class="w-14 h-14 rounded-xl object-cover border border-[#e7e2d6] dark:border-white/10 shrink-0" />
          <span v-else class="w-14 h-14 rounded-xl border border-[#e7e2d6] dark:border-white/10 flex items-center justify-center text-[#c9c3b2] dark:text-white/20 shrink-0">
            <FileVideoCamera aria-hidden="true" />
          </span>
          <span class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate min-w-0 flex-1">{{ attachmentFile?.name }}</span>
          <button type="button" @click="clearAttachment" class="text-[11px] font-bold text-[#D9534F] hover:underline shrink-0">
            {{ t("technician_complaint.remove_attachment") }}
          </button>
        </div>
        <p class="text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("technician_complaint.attachment_hint") }}</p>
      </div>

      <button type="submit" :disabled="isSubmitting" class="btn-fill-brand w-full justify-center">
        <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Send aria-hidden="true" v-else />
        {{ isSubmitting ? t("owner_complaints.submitting_ellipsis") : t("owner_complaints.submit_button") }}
      </button>
    </form>
  </div>
</template>