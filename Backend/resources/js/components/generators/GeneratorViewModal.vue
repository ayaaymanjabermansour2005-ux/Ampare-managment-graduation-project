<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import { useGeneratorAttachments } from "@/composables/useGeneratorAttachments";
import { useToastStore } from "@/stores/toast";
import { useConfirm } from "@/composables/useConfirm";
import { usePermissions } from "@/composables/usePermissions";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { File, Info, Link, Link2Off, LoaderCircle, Paperclip, Pencil, Trash2, Upload, UserCog, X } from "@lucide/vue";


const props = defineProps({
  open: { type: Boolean, default: false },
  generator: { type: Object, default: null },
});

const emit = defineEmits(["close", "edit"]);

const { t, locale } = useI18n();
const toast = useToastStore();
const { confirm: confirmDialog } = useConfirm();
const { hasRole } = usePermissions();

const STATUS_META = {
  active: { chip: "chip-success", color: "#28A745" },
  maintenance: { chip: "chip-warning", color: "#FFC107" },
  inactive: { chip: "chip-danger", color: "#D9534F" },
  pending_verification: { chip: "chip-info", color: "#17A2B8" },
  rejected: { chip: "chip-danger", color: "#8A6D1F" },
};
function statusLabel(status) {
  return t(`status.${status}`, status);
}
function fmtMoney(n) {
  return "₪ " + Number(n ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}
function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}
function timeAgo(str) {
  if (!str) return "-";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}
function fmtFileSize(bytes) {
  if (!bytes) return "-";
  const kb = bytes / 1024;
  if (kb < 1024) return `${Math.round(kb)} KB`;
  return `${(kb / 1024).toFixed(1)} MB`;
}

const FUEL_RING_CIRCUMFERENCE = 2 * Math.PI * 42;
function fuelRingOffset(pct) {
  return FUEL_RING_CIRCUMFERENCE - FUEL_RING_CIRCUMFERENCE * ((pct ?? 0) / 100);
}

/* ---------------- سجل الصيانة والأعطال ---------------- */
const generatorTimeline = ref([]);
const isLoadingGeneratorTimeline = ref(false);
async function fetchGeneratorTimeline(id) {
  isLoadingGeneratorTimeline.value = true;
  generatorTimeline.value = [];
  try {
    const { data } = await generatorService.timeline(id);
    generatorTimeline.value = data.data ?? data;
  } catch {
    generatorTimeline.value = [];
  } finally {
    isLoadingGeneratorTimeline.value = false;
  }
}
const TIMELINE_DOT_COLORS = {
  maintenance_completed: "#52733D",
  maintenance_closed: "#52733D",
  fault: "#D9534F",
  fault_reported: "#D9534F",
  started: "#17A2B8",
  activated: "#17A2B8",
};
function timelineDotColor(item) {
  return item.color ?? TIMELINE_DOT_COLORS[item.type] ?? "#52733D";
}

/* ---------------- المرفقات ---------------- */
const {
  attachments,
  isLoading: isLoadingAttachments,
  fetchAttachments,
  isUploading,
  uploadError,
  uploadAttachment,
  deletingAttachmentId,
  deleteAttachment,
} = useGeneratorAttachments();

const DOCUMENT_TYPE_OPTIONS = computed(() => [
  { value: "generator_photo", label: t("generators_management_page.doc_type_photo"), icon: "fa-image" },
  { value: "generator_license", label: t("generators_management_page.doc_type_license"), icon: "fa-certificate" },
  { value: "generator_purchase_invoice", label: t("generators_management_page.doc_type_invoice"), icon: "fa-file-invoice" },
]);
function documentTypeLabel(type) {
  const m = DOCUMENT_TYPE_OPTIONS.value.find((o) => o.value === type);
  return m ? m.label : type;
}
const newAttachmentType = ref("generator_photo");
const newAttachmentFile = ref(null);
const newAttachmentDescription = ref("");
const attachmentFileInput = ref(null);

function onAttachmentFileChange(e) {
  newAttachmentFile.value = e.target.files?.[0] ?? null;
}

async function handleUploadAttachment() {
  if (!newAttachmentFile.value || !props.generator) return;
  const ok = await uploadAttachment(props.generator.id, {
    documentType: newAttachmentType.value,
    file: newAttachmentFile.value,
    description: newAttachmentDescription.value || undefined,
  });
  if (ok) {
    newAttachmentFile.value = null;
    newAttachmentDescription.value = "";
    if (attachmentFileInput.value) attachmentFileInput.value.value = "";
    toast.show({
      type: "success",
      title: t("generators_management_page.uploaded_toast_title"),
      message: t("generators_management_page.attachment_uploaded_message"),
    });
  }
}

async function handleDeleteAttachment(attachmentId) {
  const confirmed = await confirmDialog({
    title: t("generators_management_page.delete_attachment_title"),
    message: t("generators_management_page.delete_attachment_message"),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;
  const ok = await deleteAttachment(attachmentId);
  if (ok) {
    toast.show({
      type: "success",
      title: t("users_page.deleted_toast_title"),
      message: t("generators_management_page.attachment_deleted_message"),
    });
  }
}

/* ---------------- الفنيون المرتبطون دائمًا بالمولد (Attach/Detach) ---------------- */
const linkedTechnicians = ref([]);
const isLoadingLinkedTechnicians = ref(false);
const availableToLinkTechnicians = ref([]);
const isLoadingAvailableTechnicians = ref(false);
const selectedTechnicianToLink = ref("");
const isLinkingTechnician = ref(false);
const isUnlinkingTechnicianId = ref(null);

const linkableTechnicians = computed(() => {
  const linkedIds = new Set(linkedTechnicians.value.map((tc) => tc.id));
  return availableToLinkTechnicians.value.filter((tc) => !linkedIds.has(tc.id));
});
const linkableTechnicianOptions = computed(() =>
  linkableTechnicians.value.map((tc) => ({ value: tc.id, label: tc.name })),
);

async function fetchLinkedTechnicians(generatorId) {
  isLoadingLinkedTechnicians.value = true;
  try {
    const { data } = await generatorService.linkedTechnicians(generatorId);
    linkedTechnicians.value = data.data;
  } catch {
    linkedTechnicians.value = [];
  } finally {
    isLoadingLinkedTechnicians.value = false;
  }
}

async function fetchAvailableToLinkTechnicians(generatorId) {
  isLoadingAvailableTechnicians.value = true;
  try {
    const { data } = await generatorService.availableTechnicians(generatorId);
    availableToLinkTechnicians.value = data.data;
  } catch {
    availableToLinkTechnicians.value = [];
  } finally {
    isLoadingAvailableTechnicians.value = false;
  }
}

async function handleLinkTechnician() {
  if (!selectedTechnicianToLink.value || !props.generator) return;
  isLinkingTechnician.value = true;
  try {
    await generatorService.linkTechnician(selectedTechnicianToLink.value, props.generator.id);
    selectedTechnicianToLink.value = "";
    await Promise.all([
      fetchLinkedTechnicians(props.generator.id),
      fetchAvailableToLinkTechnicians(props.generator.id),
    ]);
    toast.show({
      type: "success",
      title: t("generators_management_page.linked_toast_title"),
      message: t("generators_management_page.technician_linked_message"),
    });
  } catch (err) {
    toast.show({
      type: "danger",
      title: t("generators_management_page.linking_failed_title"),
      message: err.response?.data?.message ?? t("generators_management_page.try_again"),
    });
  } finally {
    isLinkingTechnician.value = false;
  }
}

async function handleUnlinkTechnician(technicianId) {
  const confirmed = await confirmDialog({
    title: t("generators_management_page.unlink_technician_title"),
    message: t("generators_management_page.unlink_technician_message"),
    confirmLabel: t("generators_management_page.unlink_action"),
    variant: "danger",
  });
  if (!confirmed) return;

  isUnlinkingTechnicianId.value = technicianId;
  try {
    await generatorService.unlinkTechnician(technicianId, props.generator.id);
    await Promise.all([
      fetchLinkedTechnicians(props.generator.id),
      fetchAvailableToLinkTechnicians(props.generator.id),
    ]);
    toast.show({
      type: "success",
      title: t("generators_management_page.unlinked_toast_title"),
      message: t("generators_management_page.technician_unlinked_message"),
    });
  } finally {
    isUnlinkingTechnicianId.value = null;
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && props.generator) {
      fetchGeneratorTimeline(props.generator.id);
      fetchAttachments(props.generator.id);
      fetchLinkedTechnicians(props.generator.id);
      fetchAvailableToLinkTechnicians(props.generator.id);
    }
  },
);

function close() {
  emit("close");
}
</script>

<template>
  <Teleport to="body">
    <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="open && generator" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="close">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <!-- Header -->
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Info aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("generators_management_page.generator_details_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ generator.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="close" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <!-- Body -->
          <div class="p-5 overflow-y-auto space-y-3.5">
            <!-- 3 مؤشرات سريعة -->
            <div class="grid grid-cols-3 gap-2.5">
              <div class="simple-box">
                <div class="text-[13px] font-extrabold">{{ fmtMoney(generator.monthly_revenue_ils) }}</div>
                <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ $t("dashboard.revenue_label") }}</div>
              </div>
              <div class="simple-box">
                <div class="text-[13px] font-extrabold">{{ generator.active_subscriptions_count ?? 0 }}</div>
                <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ $t("dashboard.subscriber_label") }}</div>
              </div>
              <div class="simple-box">
                <div class="text-[13px] font-extrabold">{{ generator.capacity_kw ?? "-" }}</div>
                <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ $t("dashboard.kw_label") }}</div>
              </div>
            </div>

            <!-- حلقة الوقود + الحالة -->
            <div class="simple-box py-5">
              <div class="relative w-[110px] h-[110px] mx-auto">
                <svg width="110" height="110" viewBox="0 0 110 110" class="fuel-ring">
                  <circle cx="55" cy="55" r="42" fill="none" stroke="rgba(82,115,61,0.12)" stroke-width="10" />
                  <circle
                    cx="55" cy="55" r="42" fill="none"
                    :stroke="fuelColor(generator.fuel_percentage)"
                    stroke-width="10" stroke-linecap="round"
                    :stroke-dasharray="FUEL_RING_CIRCUMFERENCE"
                    :stroke-dashoffset="fuelRingOffset(generator.fuel_percentage)"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <div class="text-xl font-extrabold leading-tight" :style="{ color: fuelColor(generator.fuel_percentage) }">
                    {{ generator.fuel_percentage !== null ? generator.fuel_percentage + '%' : '-' }}
                  </div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] leading-tight">{{ $t("dashboard.fuel_label") }}</div>
                </div>
              </div>
              <div class="mt-3">
                <span class="status-chip" :class="STATUS_META[generator.status]?.chip">{{ statusLabel(generator.status) }}</span>
              </div>
              <p class="fuel-source-note">
                <Info aria-hidden="true" />
                {{ t("dashboard.fuel_updated_by_hint", { lastUpdate: generator.fuel_updated_at ? t("dashboard.fuel_updated_by_hint_last_update", { time: timeAgo(generator.fuel_updated_at) }) : "" }) }}
              </p>
            </div>

            <!-- سجل الصيانة والأعطال -->
            <div class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-2.5">{{ $t("dashboard.maintenance_fault_log") }}</h5>
              <div v-if="isLoadingGeneratorTimeline" class="space-y-2">
                <div v-for="i in 3" :key="i" class="h-9 rounded-lg thumb-loading"></div>
              </div>
              <div v-else-if="generatorTimeline.length === 0" class="dropdown-empty py-6 text-[11px]">
                {{ $t("dashboard.no_history_yet") }}
              </div>
              <div v-else class="space-y-2.5">
                <div v-for="item in generatorTimeline" :key="item.id" class="timeline-item" :style="{ '--dot-color': timelineDotColor(item) }">
                  <p class="text-[11.5px] font-semibold">{{ item.title ?? item.description }}</p>
                  <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ item.date_label ?? timeAgo(item.created_at) }}</span>
                </div>
              </div>
            </div>

            <!-- معلومات إضافية -->
            <div class="simple-box text-[12px] space-y-2.5 text-start">
              <div class="info-row"><span>{{ $t("dashboard.code") }}</span><b>{{ generator.code }}</b></div>
              <div class="info-row"><span>{{ $t("dashboard.area") }}</span><b>{{ generator.location?.city ?? "-" }}</b></div>

              <!-- عند الأدمن: اسم المالك. عند مالك المولد: نفس اسمه، فمعلومة مكررة —
                   بنستبدلها بتاريخ التركيب اللي أفيد إله. -->
              <div class="info-row">
                <span>{{ hasRole('generator_owner') ? $t("dashboard.commissioned_label") : $t("dashboard.owner") }}</span>
                <b>{{ hasRole('generator_owner') ? (generator.created_at?.slice(0, 10) ?? "-") : (generator.owner?.name ?? "-") }}</b>
              </div>

              <div class="info-row"><span>{{ $t("dashboard.fuel_type_value_label") }}</span><b>{{ generator.fuel_type_label ?? "-" }}</b></div>

              <!-- هاد الصف بيبين تاريخ التركيب أصلاً — للأدمن بس، لأنه عند مالك
                   المولد صار نفس المعلومة معروضة فوق بصف "المالك" المُستبدَل. -->
              <div v-if="!hasRole('generator_owner')" class="info-row">
                <span>{{ $t("dashboard.commissioned_label") }}</span>
                <b>{{ generator.created_at?.slice(0, 10) ?? "-" }}</b>
              </div>

              <div class="info-row"><span>{{ $t("dashboard.last_maintenance_label") }}</span><b>{{ generator.last_maintenance_at?.slice(0, 10) ?? "-" }}</b></div>
            </div>

            <div v-if="generator.notes" class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-1.5">{{ $t("dashboard.notes_label") }}</h5>
              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ generator.notes }}</p>
            </div>

            <!-- المرفقات (صور، رخصة تشغيل، فاتورة شراء) -->
            <div class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-1.5"><Paperclip class="text-[10px]" aria-hidden="true" /> {{ $t("generators_management_page.attachments_title") }}</h5>

              <div v-if="isLoadingAttachments" class="space-y-2">
                <div v-for="i in 2" :key="i" class="h-10 rounded-lg thumb-loading"></div>
              </div>
              <div v-else-if="attachments.length === 0" class="dropdown-empty py-4 text-[11px]">
                {{ $t("generators_management_page.no_attachments_yet") }}
              </div>
              <div v-else class="space-y-2 mb-3">
                <div v-for="a in attachments" :key="a.id" class="flex items-center gap-2.5 p-2 rounded-lg bg-[#f4efe5]/50 dark:bg-white/5">
                  <File class="text-[#8A6D1F] text-[13px] shrink-0" aria-hidden="true" />
                  <div class="min-w-0 flex-1">
                    <a :href="a.download_url" target="_blank" rel="noopener" class="text-[11.5px] font-semibold hover:underline truncate block">{{ a.original_name }}</a>
                    <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ documentTypeLabel(a.document_type) }} · {{ fmtFileSize(a.file_size) }}</p>
                  </div>
                  <button :aria-label="$t('common.delete_attachment')" type="button" @click="handleDeleteAttachment(a.id)" :disabled="deletingAttachmentId === a.id" class="action-btn action-btn--delete shrink-0">
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingAttachmentId === a.id" /><Trash2 aria-hidden="true" v-else />
                  </button>
                </div>
              </div>

              <!-- فورم رفع مرفق جديد -->
              <div class="pt-3 border-t border-[#eee8da] dark:border-white/10 space-y-2">
                <div class="grid grid-cols-2 gap-2">
                  <AppDropdownSelect v-model="newAttachmentType" :options="DOCUMENT_TYPE_OPTIONS" variant="field" width-class="w-full" match-trigger-width />
                  <input ref="attachmentFileInput" type="file" @change="onAttachmentFileChange" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" class="field-input text-[11px]" />
                </div>
                <input v-model="newAttachmentDescription" type="text" :placeholder="$t('generators_management_page.short_description_optional')" class="field-input text-[11.5px]" />
                <p v-if="uploadError?.file" class="text-[10.5px] text-[#D9534F]">{{ uploadError.file[0] }}</p>
                <button
                  type="button" @click="handleUploadAttachment"
                  :disabled="!newAttachmentFile || isUploading"
                  class="w-full text-[11.5px] font-bold py-2 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isUploading" /><Upload aria-hidden="true" v-else />
                  {{ isUploading ? $t("generators_management_page.uploading_ellipsis") : $t("generators_management_page.upload_attachment_button") }}
                </button>
              </div>
            </div>

            <!-- الفنيون المرتبطون دائمًا بالمولد -->
            <div class="simple-box text-start">
              <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-1.5">
                <UserCog class="text-[10px]" aria-hidden="true" /> {{ $t("generators_management_page.responsible_technicians_title") }}
              </h5>
              <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-2.5">
                {{ $t("generators_management_page.persistent_link_desc") }}
              </p>

              <div v-if="isLoadingLinkedTechnicians" class="space-y-2 mb-3">
                <div v-for="i in 2" :key="i" class="h-9 rounded-lg thumb-loading"></div>
              </div>
              <div v-else-if="linkedTechnicians.length === 0" class="dropdown-empty py-3 text-[11px] mb-3">
                {{ $t("generators_management_page.no_technician_linked") }}
              </div>
              <div v-else class="space-y-2 mb-3">
                <div v-for="tech in linkedTechnicians" :key="tech.id" class="flex items-center gap-2.5 p-2 rounded-lg bg-[#f4efe5]/50 dark:bg-white/5">
                  <span class="w-7 h-7 rounded-full bg-gradient-to-br from-[#17A2B8] to-[#0f6c7d] text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                    {{ tech.name?.charAt(0) ?? "?" }}
                  </span>
                  <span class="text-[11.5px] font-semibold flex-1 truncate">{{ tech.name }}</span>
                  <button :aria-label="$t('common.unlink_technician')" type="button" @click="handleUnlinkTechnician(tech.id)" :disabled="isUnlinkingTechnicianId === tech.id" class="action-btn action-btn--delete shrink-0">
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isUnlinkingTechnicianId === tech.id" /><Link2Off aria-hidden="true" v-else />
                  </button>
                </div>
              </div>

              <div class="pt-3 border-t border-[#eee8da] dark:border-white/10 flex gap-2">
                <AppDropdownSelect
                  v-model="selectedTechnicianToLink"
                  :options="linkableTechnicianOptions"
                  :disabled="isLoadingAvailableTechnicians"
                  :placeholder="isLoadingAvailableTechnicians ? $t('common.loading') : $t('generators_management_page.select_technician_placeholder')"
                  variant="field"
                  width-class="w-full flex-1"
                  match-trigger-width
                />
                <button
                  type="button" @click="handleLinkTechnician"
                  :disabled="!selectedTechnicianToLink || isLinkingTechnician"
                  class="shrink-0 px-4 rounded-full text-[11.5px] font-bold text-white bg-gradient-to-l from-[#17A2B8] to-[#0f6c7d] disabled:opacity-50 flex items-center gap-1.5"
                >
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isLinkingTechnician" /><Link aria-hidden="true" v-else />
                  {{ $t("generators_management_page.link_action") }}
                </button>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="close" class="btn-outline-brand">{{ $t("common.close") }}</button>
            <button type="button" @click="emit('edit', generator)" class="btn-fill-brand">
              <Pencil aria-hidden="true" /> {{ $t("common.edit") }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>