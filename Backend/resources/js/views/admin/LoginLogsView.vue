<script setup>
import { onMounted, computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useLoginLogs } from "@/composables/useLoginLogs";
import loginLogService from "@/services/loginLogService";
import { vReveal } from "@/directives/reveal";
import { ChevronLeft, ChevronRight, FileSpreadsheet, Printer, Search, ShieldUser, TriangleAlert } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t } = useI18n();

const { logs, pagination, isLoading, error, fetchLogs } = useLoginLogs();

const EVENT_META = {
  login_succeeded: { chip: "chip-success", key: "login_logs_page.event_succeeded" },
  login_failed: { chip: "chip-danger", key: "login_logs_page.event_failed" },
};
function eventLabel(e) {
  const m = EVENT_META[e];
  return m ? t(m.key) : e;
}

/* ---------------- Filters (نفس منهجية صفحة الأعطال والمستخدمين) ----------------
 * الفلاتر بترسل search/event لـ fetchLogs، والكومبوزابل useLoginLogs يمررها كـ
 * query params لـ GET /admin/login-logs، والباك-إند (LoginLogController::index)
 * بيدعم الفلترة بنوع الحدث والبحث باسم المستخدم.
 */
const searchTerm = ref("");
const eventFilter = ref("");
let searchDebounce = null;

const EVENT_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  { value: "login_succeeded", label: eventLabel("login_succeeded") },
  { value: "login_failed", label: eventLabel("login_failed") },
]);

function reload(page = 1) {
  fetchLogs(page, {
    search: searchTerm.value || undefined,
    event: eventFilter.value || undefined,
  });
}

function onSearchInput() {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => reload(1), 400);
}

function onFilterChange() {
  reload(1);
}

/* ---------------- Repeated failed-IP detection (على الصفحة المحمّلة حالياً) ----------------
 * تنبيه Brute-force بسيط: أي IP سجّل عليه 3 محاولات فاشلة أو أكثر ضمن السجلات
 * المعروضة حالياً يتحدد كـ "مشبوه" ويتمّ إبرازه. هاد تحليل على الصفحة الحالية
 * فقط وليس بديلاً عن Rate Limiting فعلي على الباك-إند، ولا عن تحليل تراكمي
 * حقيقي عبر كل السجلات (اللي لازم يصير كـ aggregate query بالسيرفر).
 */
const FAILED_THRESHOLD = 3;
const suspiciousIps = computed(() => {
  const counts = {};
  logs.value.forEach((log) => {
    if (log.event === "login_failed" && log.ip) {
      counts[log.ip] = (counts[log.ip] || 0) + 1;
    }
  });
  return new Set(Object.keys(counts).filter((ip) => counts[ip] >= FAILED_THRESHOLD));
});
function isSuspicious(log) {
  return log.event === "login_failed" && log.ip && suspiciousIps.value.has(log.ip);
}

/* ---------------- KPI Cards ---------------- */
const countOnPage = (event) => logs.value.filter((l) => l.event === event).length;
const KPI_CARDS = computed(() => [
  { icon: "fa-list-check", label: t("login_logs_page.total_logs"), value: pagination.value.total, tone: "primary" },
  { icon: "fa-circle-check", label: eventLabel("login_succeeded") + t("common.this_page_suffix"), value: countOnPage("login_succeeded"), tone: "success" },
  { icon: "fa-triangle-exclamation", label: eventLabel("login_failed") + t("common.this_page_suffix"), value: countOnPage("login_failed"), tone: "danger" },
  { icon: "fa-shield-halved", label: t("login_logs_page.suspicious_ips"), value: suspiciousIps.value.size, tone: "secondary" },
]);

/* ---------------- Pagination بأزرار محدودة ---------------- */
const paginationRange = computed(() => {
  const total = pagination.value.last_page;
  const current = pagination.value.current_page;
  const delta = 1;
  const range = [];
  const withDots = [];
  let last = null;

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }
  for (const i of range) {
    if (last !== null) {
      if (i - last === 2) withDots.push(last + 1);
      else if (i - last > 2) withDots.push("...");
    }
    withDots.push(i);
    last = i;
  }
  return withDots;
});

const exportUrl = computed(() =>
  loginLogService.exportUrl({
    search: searchTerm.value || undefined,
    event: eventFilter.value || undefined,
  })
);

/* ---------------- طباعة الصفحة المحمّلة حاليًا ---------------- */
function handlePrint() {
  window.print();
}

onMounted(() => reload());
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
<ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
<ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("menu.login_logs") }}</span>
      </nav>
      <div class="relative">
        <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
            <ShieldUser aria-hidden="true" />
          </span>
          {{ $t("menu.login_logs") }}
        </h1>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
          {{ $t("login_logs_page.subtitle") }}
        </p>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
        />
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="searchTerm"
            type="text"
            @input="onSearchInput"
            :placeholder="$t('login_logs_page.search_placeholder')"
            class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
          />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in EVENT_PILLS"
              :key="pill.value"
              type="button"
              @click="eventFilter = pill.value; onFilterChange()"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="eventFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <a
            :href="exportUrl"
            target="_blank"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="$t('login_logs_page.export_excel')"
          >
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </a>
          <button
            type="button"
            @click="handlePrint"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="$t('login_logs_page.print')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== LIST ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ $t("login_logs_page.events_log_title") }}</h3>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="logs.length === 0" class="text-center py-12">
        <ShieldUser class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("login_logs_page.no_matching_logs") }}</p>
      </div>

      <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
        <div
          v-for="log in logs"
          :key="log.id"
          class="flex items-center justify-between gap-3 py-3 px-1.5 -mx-1.5 rounded-lg"
          :class="isSuspicious(log) ? 'bg-[#D9534F]/[0.06] dark:bg-[#D9534F]/10' : ''"
        >
          <div class="flex items-center gap-3 min-w-0">
            <span class="status-chip shrink-0" :class="EVENT_META[log.event]?.chip">{{ eventLabel(log.event) }}</span>
            <p class="text-[12.5px] font-semibold truncate">{{ log.user_name }}</p>
            <span
              v-if="isSuspicious(log)"
              class="status-chip shrink-0 chip-danger"
              :title="$t('login_logs_page.suspicious_title')"
            >
              <TriangleAlert aria-hidden="true" />
              {{ $t("login_logs_page.suspicious_badge") }}
            </span>
          </div>
          <div class="text-end shrink-0">
            <p class="text-[11px] font-mono" :class="isSuspicious(log) ? 'text-[#D9534F] font-bold' : 'text-[#9a9d97] dark:text-[#8f938a]'">{{ log.ip ?? "—" }}</p>
            <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mt-0.5">{{ log.created_at }}</p>
          </div>
        </div>
      </div>

      <!-- Pagination بأزرار محدودة -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("login_logs_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="pagination.current_page <= 1"
            @click="reload(pagination.current_page - 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>

          <template v-for="(page, i) in paginationRange" :key="i">
            <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
            <button
              v-else
              type="button"
              @click="reload(page)"
              class="w-7 h-7 rounded-lg text-[11px] font-mono font-bold transition-colors"
              :class="
                page === pagination.current_page
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                  : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
              "
            >
              {{ page }}
            </button>
          </template>

          <button
            :aria-label="$t('common.next_page')"
            type="button"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="reload(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>
  </div>
</template>