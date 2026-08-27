<script setup>
import { ref, reactive, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import articleCommentService from "@/services/articleCommentService";
import { LoaderCircle, Reply, Send } from "@lucide/vue";


const props = defineProps({
  slug: { type: String, required: true },
});

const { t } = useI18n();

const comments = ref([]);
const isLoading = ref(true);
const loadError = ref(null);

async function loadComments() {
  isLoading.value = true;
  loadError.value = null;
  try {
    const { data } = await articleCommentService.publicList(props.slug, { per_page: 20 });
    const payload = data.data;
    comments.value = payload.data ?? payload;
  } catch {
    loadError.value = t("landing.articles_page.comments_load_error");
  } finally {
    isLoading.value = false;
  }
}

/* ---------------- فورم إضافة تعليق ---------------- */
const form = reactive({ name: "", email: "", comment: "", website: "" }); // website = Honeypot
const isSubmitting = ref(false);
const submitError = ref(null);
const submitSuccess = ref(false);
const fieldErrors = ref({});

function fieldError(field) {
  return fieldErrors.value?.[field]?.[0] ?? null;
}

async function handleSubmit() {
  isSubmitting.value = true;
  submitError.value = null;
  submitSuccess.value = false;
  fieldErrors.value = {};

  try {
    await articleCommentService.publicStore(props.slug, { ...form });
    submitSuccess.value = true;
    Object.assign(form, { name: "", email: "", comment: "", website: "" });
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors ?? {};
    } else {
      submitError.value = err.response?.data?.message ?? t("landing.articles_page.comment_submit_error");
    }
  } finally {
    isSubmitting.value = false;
  }
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

function initialsOf(name) {
  const parts = (name ?? "").trim().split(/\s+/);
  return (parts[0]?.[0] ?? "") + (parts[1]?.[0] ?? "");
}

onMounted(loadComments);
</script>

<template>
  <div class="space-y-5">
    <h3 class="text-[15px] font-extrabold">{{ t("landing.articles_page.comments_title") }} <span v-if="!isLoading" class="text-[#9a9d97] font-normal">({{ comments.length }})</span></h3>

    <!-- قائمة التعليقات -->
    <div v-if="isLoading" class="space-y-2.5">
      <div v-for="i in 2" :key="i" class="h-16 rounded-xl glass-card animate-pulse"></div>
    </div>
    <div v-else-if="loadError" class="text-[12px] text-[#D9534F]">{{ loadError }}</div>
    <div v-else-if="comments.length === 0" class="text-[12.5px] text-[#9a9d97] py-2">{{ t("landing.articles_page.no_comments_yet") }}</div>
    <div v-else class="space-y-3">
      <div v-for="c in comments" :key="c.id" class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-[11px] font-bold shrink-0 bg-gradient-to-br from-[#52733D] to-[#3E582E]">
          {{ initialsOf(c.name) }}
        </div>
        <div class="flex-1 min-w-0 glass-card p-3.5">
          <div class="flex items-center justify-between gap-2">
            <span class="text-[12px] font-bold">{{ c.name }}</span>
            <span class="text-[10px] text-[#9a9d97]">{{ timeAgo(c.created_at) }}</span>
          </div>
          <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1 leading-relaxed">{{ c.comment }}</p>

          <div v-if="c.admin_reply" class="mt-2.5 ms-3 ps-3 border-s-2 border-[#8A6D1F]/40">
            <p class="text-[10.5px] font-bold text-[#8A6D1F] flex items-center gap-1.5">
              <Reply class="text-[9px]" aria-hidden="true" /> {{ t("landing.articles_page.team_reply") }}
            </p>
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.admin_reply }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- فورم إضافة تعليق -->
    <div class="glass-card p-5">
      <h4 class="text-[13px] font-bold mb-3">{{ t("landing.articles_page.add_comment_title") }}</h4>

      <div v-if="submitSuccess" class="text-[12px] font-bold text-[#28A745] bg-[#28A745]/10 rounded-xl p-3 mb-3">
        {{ t("landing.articles_page.comment_submitted") }}
      </div>
      <div v-if="submitError" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-3 mb-3">{{ submitError }}</div>

      <form @submit.prevent="handleSubmit" class="space-y-3">
        <!-- حقل Honeypot: مخفي تمامًا عن المستخدم الحقيقي، مفخّخ للبوتات فقط -->
        <div class="absolute -start-[9999px] opacity-0 pointer-events-none" aria-hidden="true">
          <label for="website">Website</label>
          <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <input v-model="form.name" required type="text" :placeholder="t('landing.articles_page.comment_name_placeholder')" class="form-field" />
            <p v-if="fieldError('name')" class="text-[10px] text-[#D9534F] mt-1">{{ fieldError("name") }}</p>
          </div>
          <div>
            <input v-model="form.email" type="email" dir="ltr" :placeholder="t('landing.articles_page.comment_email_placeholder')" class="form-field" />
          </div>
        </div>
        <div>
          <textarea v-model="form.comment" required rows="3" maxlength="1000" :placeholder="t('landing.articles_page.comment_placeholder')" class="form-field"></textarea>
          <p v-if="fieldError('comment')" class="text-[10px] text-[#D9534F] mt-1">{{ fieldError("comment") }}</p>
        </div>
        <button type="submit" :disabled="isSubmitting" class="btn-fill bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white font-bold text-[12.5px] px-5 py-2.5 rounded-full shadow-md flex items-center gap-2 disabled:opacity-60">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Send aria-hidden="true" v-else />
          {{ isSubmitting ? t("landing.articles_page.comment_sending") : t("landing.articles_page.comment_submit") }}
        </button>
      </form>
    </div>
  </div>
</template>
