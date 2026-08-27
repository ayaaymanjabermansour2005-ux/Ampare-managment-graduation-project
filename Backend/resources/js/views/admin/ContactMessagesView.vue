<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminContactMessages } from "@/composables/useAdminContactMessages";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Check, ChevronLeft, ChevronRight, Inbox, LoaderCircle, MailOpen, Search, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t, locale } = useI18n();

const {
  messages,
  pagination,
  isLoading,
  error,
  searchTerm,
  statusFilter,
  fetchMessages,
  onFilterChange,
  isUpdating,
  updateError,
  updateStatus,
} = useAdminContactMessages();

let searchDebounce = null;
function onSearchInput() {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => fetchMessages(1), 400);
}

const STATUS_META = {
  new: { chip: "chip-danger", key: "complaints_page.status_new" },
  in_progress: { chip: "chip-warning", key: "complaints_page.status_in_progress" },
  resolved: { chip: "chip-success", key: "contact_messages_page.status_resolved" },
};
function statusLabel(s) {
  const m = STATUS_META[s];
  return m ? t(m.key) : s;
}
const messageStatusOptions = computed(() => [
  { value: "new", label: statusLabel("new") },
  { value: "in_progress", label: statusLabel("in_progress") },
  { value: "resolved", label: statusLabel("resolved") },
]);
function statusChip(s) {
  return STATUS_META[s]?.chip ?? "chip-info";
}

const SUBJECT_META = {
  general: { key: "landing.contact.form.subject_general", icon: "fa-circle-question" },
  owner: { key: "contact_messages_page.subject_owner", icon: "fa-user-plus" },
  technical: { key: "landing.contact.form.subject_technical", icon: "fa-triangle-exclamation" },
  partnership: { key: "landing.contact.form.subject_partnership", icon: "fa-handshake" },
};
function subjectLabel(s) {
  const m = SUBJECT_META[s];
  return m ? t(m.key) : s;
}
function subjectIcon(s) {
  return SUBJECT_META[s]?.icon ?? "fa-envelope";
}

const STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  { value: "new", label: statusLabel("new") },
  { value: "in_progress", label: statusLabel("in_progress") },
  { value: "resolved", label: statusLabel("resolved") },
]);

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

/* ---------------- نافذة التفاصيل + تحديث الحالة ---------------- */
const viewingMessage = ref(null);
const statusForm = ref({ status: "new", admin_note: "" });

function openMessage(msg) {
  viewingMessage.value = msg;
  statusForm.value = { status: msg.status, admin_note: msg.admin_note ?? "" };
}

async function handleUpdateStatus() {
  const updated = await updateStatus(viewingMessage.value.id, statusForm.value.status, statusForm.value.admin_note);
  if (updated) viewingMessage.value = updated;
}

onMounted(() => fetchMessages(1));
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>

      <nav class="relative flex items-center gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
        <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("menu.contact_messages") }}</span>
      </nav>

      <h1 class="relative text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
          <MailOpen aria-hidden="true" />
        </span>
        {{ $t("menu.contact_messages") }}
      </h1>
      <p class="relative text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
        {{ $t("contact_messages_page.subtitle") }}
      </p>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative max-w-sm w-full sm:w-auto">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="searchTerm" type="text" @input="onSearchInput"
            :placeholder="$t('contact_messages_page.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
            @click="statusFilter = pill.value; onFilterChange()"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >{{ pill.label }}</button>
        </div>
      </div>
    </section>

    <!-- ===== LIST ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>
      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>
      <div v-else-if="messages.length === 0" class="text-center py-10">
        <Inbox class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("contact_messages_page.no_matching_messages") }}</p>
      </div>

      <div v-else class="space-y-2">
        <button
          v-for="msg in messages" :key="msg.id" type="button"
          @click="openMessage(msg)"
          class="w-full text-start flex items-start gap-3 p-3.5 rounded-xl border border-[#eee8da] dark:border-white/5 hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition-colors"
        >
          <div class="w-9 h-9 rounded-lg bg-[#f4efe5] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F] shrink-0">
            <AppIcon :name="subjectIcon(msg.subject)" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <span class="text-[12.5px] font-bold">{{ msg.name }}</span>
              <div class="flex items-center gap-2 shrink-0">
                <span class="status-chip" :class="statusChip(msg.status)">{{ statusLabel(msg.status) }}</span>
                <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(msg.created_at) }}</span>
              </div>
            </div>
            <p class="text-[11px] text-[#8A6D1F] font-semibold mt-0.5">{{ subjectLabel(msg.subject) }}</p>
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] truncate mt-1">{{ msg.message }}</p>
          </div>
        </button>
      </div>

      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
        <span>{{ $t("contact_messages_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <!-- زر الصفحة السابقة: يشير للخلف (يسار بالإنجليزي / يمين بالعربي) -->
          <button
            :aria-label="$t('common.previous_page')" type="button"
            :disabled="pagination.current_page <= 1"
            @click="fetchMessages(pagination.current_page - 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>

          <!-- زر الصفحة التالية: يشير للأمام (يمين بالإنجليزي / يسار بالعربي) -->
          <button
            :aria-label="$t('common.next_page')" type="button"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchMessages(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===================== نافذة التفاصيل ===================== -->
    <Teleport to="body">
      <div v-if="viewingMessage" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="viewingMessage = null">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[88vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><MailOpen aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("contact_messages_page.details_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ viewingMessage.name }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="viewingMessage = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 space-y-4 overflow-y-auto">
            <div class="glass-card p-4 space-y-2.5 text-[12px]">
              <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.name") }}</span><b>{{ viewingMessage.name }}</b></div>
              <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.email_label") }}</span><b class="truncate max-w-[12rem]">{{ viewingMessage.email }}</b></div>
              <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.phone_label") }}</span><b dir="ltr">{{ viewingMessage.phone }}</b></div>
              <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("complaints_page.subject_field_label") }}</span><b>{{ subjectLabel(viewingMessage.subject) }}</b></div>
              <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.date_col") }}</span><b>{{ viewingMessage.created_at?.slice(0, 16) }}</b></div>
            </div>

            <div class="glass-card p-4">
              <h5 class="text-[12px] font-bold mb-2">{{ $t("contact_messages_page.message_label") }}</h5>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed whitespace-pre-line">{{ viewingMessage.message }}</p>
            </div>

            <div v-if="viewingMessage.handled_by" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
              {{ $t("contact_messages_page.last_handled_by") }} <b>{{ viewingMessage.handled_by.name }}</b> — {{ timeAgo(viewingMessage.handled_at) }}
            </div>

            <div v-if="updateError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ updateError }}</div>

            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.status") }}</label>
              <AppDropdownSelect v-model="statusForm.status" :options="messageStatusOptions" variant="field" width-class="w-full" match-trigger-width />
            </div>

            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("contact_messages_page.internal_note_label") }}</label>
              <textarea v-model="statusForm.admin_note" rows="3" maxlength="2000" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"></textarea>
            </div>
          </div>

          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="viewingMessage = null" class="btn-outline-brand">{{ $t("common.close") }}</button>
            <button type="button" @click="handleUpdateStatus" :disabled="isUpdating" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isUpdating" /><Check aria-hidden="true" v-else />
              {{ isUpdating ? $t("contact_messages_page.saving") : $t("contact_messages_page.save_status") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>