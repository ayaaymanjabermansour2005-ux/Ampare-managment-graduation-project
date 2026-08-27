<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useArticles } from "@/composables/useArticles";
import { useAdminArticleComments } from "@/composables/useAdminArticleComments";
import { useConfirm } from "@/composables/useConfirm";
import { vReveal } from "@/directives/reveal";
import { Check, ChevronLeft, ChevronRight, CircleAlert, FileText, Languages, LoaderCircle, MessageSquareOff, MessagesSquare, Newspaper, Pencil, Plus, Reply, Save, Trash2, TriangleAlert, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t, locale } = useI18n();
const route = useRoute();
const { confirm } = useConfirm();

/* ==========================================================================
 * ========================  التابات (المقالات / التعليقات)  ================
 * ========================================================================== */
const TABS = computed(() => [
  { key: "articles", label: t("articles_page.title"), icon: "fa-newspaper" },
  { key: "comments", label: t("article_comments_page.title"), icon: "fa-comments" },
]);
const activeTab = ref("articles");

/* ==========================================================================
 * =============================  تاب: المقالات  =============================
 * ========================================================================== */
const {
  articles,
  pagination,
  isLoading,
  error,
  isSaving,
  fetchArticles,
  createArticle,
  updateArticle,
  deleteArticle,
} = useArticles();

const isFormOpen = ref(false);
const editingArticle = ref(null);

const form = ref({
  title: "",
  excerpt: "",
  content: "",
  title_en: "",
  excerpt_en: "",
  content_en: "",
  cover_image_url: "",
  is_published: false,
});

const KPI_CARDS = computed(() => [
  { icon: "fa-newspaper", label: t("articles_page.total_articles"), value: pagination.value.total, c1: "#3E582E", c2: "#52733D" },
  { icon: "fa-circle-check", label: t("articles_page.published_label"), value: articles.value.filter((a) => a.is_published).length, c1: "#28A745", c2: "#1f7a37" },
  { icon: "fa-pen", label: t("articles_page.draft_label"), value: articles.value.filter((a) => !a.is_published).length, c1: "#8A6D1F", c2: "#5c4a15" },
]);

function openCreate() {
  editingArticle.value = null;
  form.value = {
    title: "",
    excerpt: "",
    content: "",
    title_en: "",
    excerpt_en: "",
    content_en: "",
    cover_image_url: "",
    is_published: false,
  };
  isFormOpen.value = true;
}

function openEdit(article) {
  editingArticle.value = article;
  form.value = { ...article };
  isFormOpen.value = true;
}

async function handleSubmit() {
  const ok = editingArticle.value
    ? await updateArticle(editingArticle.value.id, form.value)
    : await createArticle(form.value);
  if (ok) isFormOpen.value = false;
}

async function handleDelete(article) {
  const confirmed = await confirm({
    title: t("articles_page.delete_article_title", { title: article.title }),
    message: t("subscriptions_page.confirm_status_change_message"),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (confirmed) await deleteArticle(article.id);
}

/* ==========================================================================
 * ============================  تاب: التعليقات  =============================
 * ========================================================================== */
const {
  comments,
  pagination: commentsPagination,
  isLoading: isCommentsLoading,
  error: commentsError,
  statusFilter,
  fetchComments,
  onFilterChange,
  isActing,
  approve,
  reject,
  destroy,
  reply,
  deleteReply,
} = useAdminArticleComments();

const STATUS_PILLS = [
  { value: "pending", key: "owner_applications_page.status_pending" },
  { value: "approved", key: "meter_readings_page.status_approved" },
  { value: "rejected", key: "owner_applications_page.status_rejected" },
  { value: "all", key: "common.all" },
];

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

const replyDrafts = reactive({});
const openReplyIds = reactive(new Set());

function toggleReplyBox(c) {
  if (openReplyIds.has(c.id)) {
    openReplyIds.delete(c.id);
  } else {
    replyDrafts[c.id] = c.admin_reply ?? "";
    openReplyIds.add(c.id);
  }
}

async function submitReply(c) {
  const text = (replyDrafts[c.id] ?? "").trim();
  if (!text) return;
  const ok = await reply(c.id, text);
  if (ok) openReplyIds.delete(c.id);
}

async function removeReply(c) {
  await deleteReply(c.id);
  openReplyIds.delete(c.id);
}

onMounted(() => {
  // يسمح بفتح الصفحة مباشرة على تاب "التعليقات" (مثلاً من رابط بالداشبورد): ?tab=comments
  if (route.query.tab === "comments") activeTab.value = "comments";

  fetchArticles();
  fetchComments(1);
});
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("articles_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
              <Newspaper aria-hidden="true" v-if="activeTab === 'articles'" /><MessagesSquare aria-hidden="true" v-else />
            </span>
            {{ activeTab === "articles" ? $t("articles_page.title") : $t("article_comments_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ activeTab === "articles" ? $t("articles_page.subtitle") : $t("article_comments_page.subtitle") }}
          </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <!-- ===== تبديل التابات ===== -->
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 w-fit">
            <button
              v-for="tab in TABS" :key="tab.key" type="button"
              @click="activeTab = tab.key"
              class="relative flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
              :class="
                activeTab === tab.key
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                  : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
              "
            >
              <AppIcon :name="tab.icon" />
              {{ tab.label }}
            </button>
          </div>

          <button
            v-if="activeTab === 'articles'"
            type="button"
            @click="openCreate"
            class="btn-fill relative inline-flex items-center gap-2 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white px-4 py-2.5 rounded-full text-[12.5px] font-bold shadow-md shrink-0"
          >
            <Plus class="text-[11px]" aria-hidden="true" />
            {{ $t("articles_page.new_article") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ==========================================================
         ===================  تاب: المقالات  ==========================
         ========================================================== -->
    <template v-if="activeTab === 'articles'">
      <!-- ===== KPI CARDS ===== -->
      <section v-reveal>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <div
            v-for="c in KPI_CARDS"
            :key="c.label"
            class="kpi-card glass-card hoverable"
            :style="{ '--kpi-color': c.c1, '--kpi-color2': c.c2 }"
          >
            <div class="kpi-icon mb-2.5"><AppIcon :name="c.icon" /></div>
            <div class="text-lg font-extrabold">{{ c.value }}</div>
            <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.label }}</div>
          </div>
        </div>
      </section>

      <div v-if="error" v-reveal class="glass-card p-4 text-[12.5px] text-[#D9534F] border border-[#D9534F]/30">
        <CircleAlert class="me-1.5" aria-hidden="true" />{{ error }}
      </div>

      <!-- ===== LIST ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("articles_page.articles_list_title") }}</h3>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-16 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="articles.length === 0" class="text-center py-12">
          <Newspaper class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" aria-hidden="true" />
          <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("articles_page.no_articles_yet") }}</p>
        </div>

        <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
          <div
            v-for="article in articles"
            :key="article.id"
            class="flex items-center gap-3.5 py-3.5 px-1.5"
          >
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#3E582E] to-[#52733D] flex items-center justify-center shrink-0 text-white">
              <FileText class="text-[12px]" aria-hidden="true" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="text-[12.5px] font-bold truncate">{{ article.title }}</p>
                <span class="status-chip shrink-0" :class="article.is_published ? 'chip-success' : 'chip-info'">
                  {{ article.is_published ? $t("articles_page.published_label") : $t("articles_page.draft_label") }}
                </span>
                <span v-if="!article.title_en || !article.content_en" class="status-chip shrink-0 chip-warning" :title="$t('articles_page.missing_english_version')">
                  <TriangleAlert class="text-[9px]" aria-hidden="true" /> {{ $t("articles_page.english_badge_label") }}
                </span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 truncate">
                {{ locale === "ar" ? article.excerpt : article.excerpt_en }}
              </p>
            </div>

            <div class="flex items-center gap-1 shrink-0">
              <button
                type="button"
                @click="openEdit(article)"
                class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#3E582E] hover:bg-[#EBF1E7] dark:hover:bg-white/5 transition"
                :title="$t('common.edit')"
                :aria-label="$t('common.edit')"
              >
                <Pencil class="text-[12px]" aria-hidden="true" />
              </button>
              <button
                type="button"
                @click="handleDelete(article)"
                class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-danger hover:bg-danger/10 transition"
                :title="$t('common.delete')"
                :aria-label="$t('common.delete')"
              >
                <Trash2 class="text-[12px]" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-4 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          <span>{{ $t("articles_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchArticles(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
            <button :aria-label="$t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchArticles(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>

      <!-- ===== FORM MODAL ===== -->
      <Teleport to="body">
        <div
          v-if="isFormOpen"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
          @click.self="isFormOpen = false"
        >
          <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#eee8da] dark:border-white/10 shrink-0">
              <h3 class="text-[14.5px] font-extrabold flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-[12px]">
                  <Pencil aria-hidden="true" v-if="editingArticle" /><Plus aria-hidden="true" v-else />
                </span>
                {{ editingArticle ? $t("articles_page.edit_article") : $t("articles_page.new_article") }}
              </h3>
              <button :aria-label="$t('common.close')" type="button" @click="isFormOpen = false" class="w-8 h-8 rounded-full flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-black/5 dark:hover:bg-white/10 transition">
                <X aria-hidden="true" />
              </button>
            </div>

            <div class="p-5 space-y-5 overflow-y-auto">
              <!-- ===== النسخة العربية ===== -->
              <div class="space-y-3.5">
                <p class="text-[11px] font-extrabold text-[#8A6D1F] dark:text-[#F4E0A5] flex items-center gap-1.5">
                  <Languages aria-hidden="true" /> {{ $t("articles_page.arabic_version_label") }}
                </p>

                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("articles_page.title_field_ar") }}</label>
                  <input
                    v-model="form.title"
                    type="text"
                    dir="rtl"
                    :placeholder="$t('articles_page.title_ar_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]"
                  />
                </div>
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">
                    {{ $t("articles_page.excerpt_field_ar") }}
                    <span class="text-[#9a9d97] dark:text-[#8f938a] font-normal">{{ $t("articles_page.optional_suffix") }}</span>
                  </label>
                  <input
                    v-model="form.excerpt"
                    type="text"
                    dir="rtl"
                    :placeholder="$t('articles_page.excerpt_ar_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]"
                  />
                </div>
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("articles_page.content_field_ar") }}</label>
                  <textarea
                    v-model="form.content"
                    rows="6"
                    dir="rtl"
                    :placeholder="$t('articles_page.content_ar_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"
                  ></textarea>
                </div>
              </div>

              <div class="space-y-3.5 pt-4 border-t border-[#eee8da] dark:border-white/10">
                <p class="text-[11px] font-extrabold text-[#8A6D1F] dark:text-[#F4E0A5] flex items-center gap-1.5">
                  <Languages aria-hidden="true" /> {{ $t("articles_page.english_version_label") }}
                  <span class="text-[#D9534F] font-normal">{{ $t("articles_page.required_suffix") }}</span>
                </p>

                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("articles_page.title_field_en") }}</label>
                  <input
                    v-model="form.title_en"
                    type="text"
                    dir="ltr"
                    :placeholder="$t('articles_page.title_en_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]"
                  />
                </div>
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">
                    {{ $t("articles_page.excerpt_field_en") }}
                    <span class="text-[#9a9d97] dark:text-[#8f938a] font-normal">{{ $t("articles_page.optional_suffix") }}</span>
                  </label>
                  <input
                    v-model="form.excerpt_en"
                    type="text"
                    dir="ltr"
                    :placeholder="$t('articles_page.excerpt_en_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]"
                  />
                </div>
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("articles_page.content_field_en") }}</label>
                  <textarea
                    v-model="form.content_en"
                    rows="6"
                    dir="ltr"
                    :placeholder="$t('articles_page.content_en_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"
                  ></textarea>
                </div>
              </div>

              <div class="pt-4 border-t border-[#eee8da] dark:border-white/10 space-y-3.5">
                <div>
                  <label class="text-[11.5px] font-bold block mb-1.5">
                    {{ $t("articles_page.cover_image_label") }}
                    <span class="text-[#9a9d97] dark:text-[#8f938a] font-normal">{{ $t("articles_page.optional_suffix") }}</span>
                  </label>
                  <input
                    v-model="form.cover_image_url"
                    type="url"
                    dir="ltr"
                    :placeholder="$t('articles_page.cover_image_placeholder')"
                    class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F]"
                  />
                </div>

                <label class="flex items-center gap-2 text-[12.5px] cursor-pointer">
                  <input
                    v-model="form.is_published"
                    type="checkbox"
                    class="rounded border-[#e7e2d6] dark:border-white/10 text-[#3E582E] focus:ring-[#3E582E]/30"
                  />
                  {{ $t("articles_page.publish_immediately") }}
                </label>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0">
              <button type="button" @click="isFormOpen = false" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">
                {{ $t("dashboard.cancel") }}
              </button>
              <button
                type="button"
                :disabled="!form.title || !form.content || !form.title_en || !form.content_en || isSaving"
                @click="handleSubmit"
                class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2 disabled:opacity-60"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
                {{ isSaving ? $t("users_page.saving_ellipsis") : $t("users_page.save_action") }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </template>

    <!-- ==========================================================
         ==================  تاب: تعليقات المدونة  ====================
         ========================================================== -->
    <template v-else>
      <section v-reveal class="glass-card p-4">
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 w-fit">
          <button
            v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
            @click="statusFilter = pill.value; onFilterChange()"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >{{ t(pill.key) }}</button>
        </div>
      </section>

      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isCommentsLoading" class="space-y-2">
          <div v-for="i in 5" :key="i" class="h-20 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="commentsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ commentsError }}</div>
        <div v-else-if="comments.length === 0" class="text-center py-10">
          <MessageSquareOff class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("article_comments_page.no_comments") }}</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="c in comments" :key="c.id" class="p-3.5 rounded-xl border border-[#eee8da] dark:border-white/5">
            <div class="flex items-start justify-between gap-3 flex-wrap">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-[12.5px] font-bold">{{ c.name }}</span>
                  <span v-if="c.email" class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ c.email }}</span>
                  <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">· {{ timeAgo(c.created_at) }}</span>
                </div>
                <p v-if="c.article" class="text-[11px] text-[#8A6D1F] font-semibold mt-0.5">
                  <Newspaper class="text-[9px]" aria-hidden="true" /> {{ c.article.title }}
                </p>
                <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1.5 leading-relaxed">{{ c.comment }}</p>

                <!-- رد الأدمن الحالي (إن وُجد) -->
                <div v-if="c.admin_reply && !openReplyIds.has(c.id)" class="mt-2.5 ms-4 ps-3 border-s-2 border-[#8A6D1F]/40">
                  <p class="text-[10.5px] font-bold text-[#8A6D1F] flex items-center gap-1.5">
                    <Reply class="text-[9px]" aria-hidden="true" />
                    {{ $t("article_comments_page.team_reply_label") }}
                    <span v-if="c.replied_by" class="font-normal text-[#9a9d97] dark:text-[#8f938a]">— {{ c.replied_by }}</span>
                  </p>
                  <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.admin_reply }}</p>
                </div>

                <!-- صندوق كتابة/تعديل الرد -->
                <div v-if="openReplyIds.has(c.id)" class="mt-2.5 space-y-2">
                  <textarea
                    v-model="replyDrafts[c.id]"
                    rows="2" maxlength="1000"
                    :placeholder="$t('article_comments_page.reply_placeholder')"
                    class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12px] outline-none focus:border-[#8A6D1F] resize-none"
                  ></textarea>
                  <div class="flex items-center gap-2">
                    <button type="button" :disabled="isActing || !replyDrafts[c.id]?.trim()" @click="submitReply(c)" class="text-[11px] font-bold px-3 py-1.5 rounded-full bg-[#8A6D1F] text-white disabled:opacity-50">
                      {{ $t("article_comments_page.save_reply") }}
                    </button>
                    <button type="button" @click="toggleReplyBox(c)" class="text-[11px] font-bold px-3 py-1.5 rounded-full border border-[#e7e2d6] dark:border-white/10">
                      {{ $t("dashboard.cancel") }}
                    </button>
                    <button v-if="c.admin_reply" type="button" :disabled="isActing" @click="removeReply(c)" class="text-[11px] font-bold text-[#D9534F] ms-auto">
                      {{ $t("article_comments_page.delete_reply") }}
                    </button>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-1.5 shrink-0">
                <button
                  type="button" :disabled="isActing" @click="toggleReplyBox(c)"
                  class="w-8 h-8 rounded-lg flex items-center justify-center text-[#8A6D1F] hover:bg-[#8A6D1F]/10 disabled:opacity-40"
                  :title="c.admin_reply ? $t('article_comments_page.edit_reply_title') : $t('article_comments_page.reply_title')"
                  :aria-label="c.admin_reply ? $t('article_comments_page.edit_reply_title') : $t('article_comments_page.reply_title')"
                ><Pencil aria-hidden="true" v-if="c.admin_reply" style="font-size:11px" /><Reply aria-hidden="true" v-else style="font-size:11px" /></button>
                <button
                  v-if="statusFilter !== 'approved'"
                  type="button" :disabled="isActing" @click="approve(c.id)"
                  class="w-8 h-8 rounded-lg flex items-center justify-center text-[#28A745] hover:bg-[#28A745]/10 disabled:opacity-40"
                  :title="$t('generators_management_page.approve_action')"
                  :aria-label="$t('generators_management_page.approve_action')"
                ><Check class="text-[12px]" aria-hidden="true" /></button>
                <button
                  v-if="statusFilter !== 'rejected'"
                  type="button" :disabled="isActing" @click="reject(c.id)"
                  class="w-8 h-8 rounded-lg flex items-center justify-center text-[#D9534F] hover:bg-[#D9534F]/10 disabled:opacity-40"
                  :title="$t('owner_applications_page.reject_action')"
                  :aria-label="$t('owner_applications_page.reject_action')"
                ><X class="text-[12px]" aria-hidden="true" /></button>
                <button
                  type="button" :disabled="isActing" @click="destroy(c.id)"
                  class="w-8 h-8 rounded-lg flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:bg-[#D9534F]/10 hover:text-[#D9534F] disabled:opacity-40"
                  :title="$t('article_comments_page.delete_permanently_title')"
                  :aria-label="$t('article_comments_page.delete_permanently_title')"
                ><Trash2 class="text-[11px]" aria-hidden="true" /></button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="commentsPagination.last_page > 1" class="flex items-center justify-between mt-3 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          <span>{{ $t("article_comments_page.pagination_text", { current: commentsPagination.current_page, last: commentsPagination.last_page }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')" type="button" :disabled="commentsPagination.current_page <= 1" @click="fetchComments(commentsPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
            <button :aria-label="$t('common.next_page')" type="button" :disabled="commentsPagination.current_page >= commentsPagination.last_page" @click="fetchComments(commentsPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>