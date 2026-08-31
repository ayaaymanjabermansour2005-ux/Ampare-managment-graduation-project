<script setup>
import { computed, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { Download, ExternalLink, File, X } from "@lucide/vue";

/**
 * POINT 9 (frontend deep-audit report): attachment listings across the app
 * (GeneratorViewModal.vue, PaymentReviewPanel.vue) rendered every non-image
 * attachment as a plain `<a target="_blank">` link — functionally correct
 * but never an in-app preview. This is the shared preview surface for both:
 * images render inline, PDFs render in an <iframe>, anything else falls
 * back to an explicit "open in a new tab" / "download" pair instead of a
 * broken/blank preview attempt.
 */
const props = defineProps({
  attachment: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const { t } = useI18n();

const kind = computed(() => {
  const mime = props.attachment?.mime_type ?? "";
  if (mime.startsWith("image/")) return "image";
  if (mime === "application/pdf") return "pdf";
  return "other";
});

function onKeydown(e) {
  if (e.key === "Escape" && props.attachment) emit("close");
}

onMounted(() => window.addEventListener("keydown", onKeydown));
onUnmounted(() => window.removeEventListener("keydown", onKeydown));
</script>

<template>
  <Transition
    enter-active-class="transition-opacity duration-150"
    leave-active-class="transition-opacity duration-100"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
  >
    <div
      v-if="attachment"
      class="fixed inset-0 z-[80] bg-black/85 flex items-center justify-center p-4"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-3xl max-h-[85vh] flex flex-col bg-white dark:bg-[#1c1b17] rounded-xl overflow-hidden">
        <header class="flex items-center justify-between gap-3 px-4 py-3 border-b border-[#e7e2d6] dark:border-white/10 shrink-0">
          <span class="text-[12.5px] font-bold truncate">{{ attachment.original_name }}</span>
          <div class="flex items-center gap-1.5 shrink-0">
            <a
              :href="attachment.download_url"
              :aria-label="t('common.download')"
              :title="t('common.download')"
              class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#f4efe5] dark:hover:bg-white/10 transition text-[#5b5d58] dark:text-[#c8cac5]"
            >
              <Download class="text-[14px]" aria-hidden="true" />
            </a>
            <a
              :href="attachment.preview_url"
              target="_blank"
              rel="noopener"
              :aria-label="t('common.open_in_new_tab')"
              :title="t('common.open_in_new_tab')"
              class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#f4efe5] dark:hover:bg-white/10 transition text-[#5b5d58] dark:text-[#c8cac5]"
            >
              <ExternalLink class="text-[14px]" aria-hidden="true" />
            </a>
            <button
              type="button"
              :aria-label="t('common.close')"
              :title="t('common.close')"
              @click="emit('close')"
              class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#f4efe5] dark:hover:bg-white/10 transition text-[#5b5d58] dark:text-[#c8cac5]"
            >
              <X class="text-[14px]" aria-hidden="true" />
            </button>
          </div>
        </header>

        <div class="flex-1 min-h-0 overflow-auto bg-[#f4efe5]/40 dark:bg-black/20 flex items-center justify-center">
          <img
            v-if="kind === 'image'"
            :src="attachment.preview_url"
            :alt="attachment.original_name"
            class="max-w-full max-h-[75vh] object-contain"
          />
          <iframe
            v-else-if="kind === 'pdf'"
            :src="attachment.preview_url"
            :title="attachment.original_name"
            class="w-full h-[75vh] border-0"
          ></iframe>
          <div v-else class="flex flex-col items-center gap-3 py-14 px-6 text-center">
            <File class="text-[32px] text-[#9a9d97]" aria-hidden="true" />
            <p class="text-[12px] text-[#5b5d58] dark:text-[#c8cac5]">{{ t("common.preview_unavailable") }}</p>
            <a
              :href="attachment.download_url"
              class="text-[11.5px] font-bold px-4 py-2 rounded-full bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-md"
            >
              {{ t("common.download") }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
