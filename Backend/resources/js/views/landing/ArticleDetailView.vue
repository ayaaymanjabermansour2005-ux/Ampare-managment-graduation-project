<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed, onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import articleService from "@/services/articleService";
import ArticleRatingWidget from "@/components/landing/ArticleRatingWidget.vue";
import ArticleComments from "@/components/landing/ArticleComments.vue";
import { FileQuestionMark, House } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const route = useRoute();
const { t, locale } = useI18n();

const article = ref(null);
const isLoading = ref(true);
const isNotFound = ref(false);
const error = ref(null);

const backArrowIcon = computed(() => (locale.value === "ar" ? "fa-arrow-right" : "fa-arrow-left"));

const localizedTitle = computed(() => {
  if (!article.value) return "";
  return locale.value === "en" && article.value.title_en ? article.value.title_en : article.value.title;
});
const localizedContent = computed(() => {
  if (!article.value) return "";
  return locale.value === "en" && article.value.content_en ? article.value.content_en : article.value.content;
});

async function fetchArticle() {
  isLoading.value = true;
  error.value = null;
  isNotFound.value = false;
  try {
    const { data } = await articleService.showPublic(route.params.slug);
    article.value = data.data;
  } catch (err) {
    if (err.response?.status === 404) {
      isNotFound.value = true;
    } else {
      error.value = normalizeApiError(err, t("landing.articles_page.error_detail_generic")).message;
    }
  } finally {
    isLoading.value = false;
  }
}

watch(() => route.params.slug, () => fetchArticle());
onMounted(() => fetchArticle());
</script>

<template>
  <div class="max-w-2xl mx-auto px-5 sm:px-8 py-16">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
      <RouterLink
        :to="{ name: 'landing.articles' }"
        class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5] hover:opacity-80"
      >
        <AppIcon :name="backArrowIcon" />
        {{ t("landing.articles_page.back_to_list") }}
      </RouterLink>

      <RouterLink
        :to="{ name: 'landing.home' }"
        class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5] hover:text-[#8A6D1F] dark:hover:text-[#F4E0A5]"
      >
        <House aria-hidden="true" />
        {{ t("landing.articles_page.back_to_home") }}
      </RouterLink>
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div class="h-8 w-2/3 rounded-xl glass-card animate-pulse"></div>
      <div class="h-40 rounded-2xl glass-card animate-pulse"></div>
    </div>

    <div v-else-if="isNotFound" class="glass-card text-center py-16">
      <FileQuestionMark class="text-2xl text-[#9a9d97] mb-2 block" aria-hidden="true" />
      <p class="text-[14px] font-bold mb-1">{{ t("landing.articles_page.not_found_title") }}</p>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("landing.articles_page.not_found_desc") }}</p>
    </div>

    <div v-else-if="error" class="bg-danger-bg text-danger text-[12.5px] rounded-xl p-4">
      {{ error }}
    </div>

    <article v-else-if="article" class="space-y-4">
      <img
        v-if="article.cover_image_url"
        :src="article.cover_image_url"
        :alt="localizedTitle"
        class="w-full rounded-2xl"
      />
      <h1 class="text-2xl font-extrabold">{{ localizedTitle }}</h1>
      <p class="text-[11px] text-[#9a9d97]">
        {{ article.author_name }} — {{ article.published_at }}
      </p>
      <div class="glass-card p-5 sm:p-7">
        <div class="prose prose-sm max-w-none whitespace-pre-line leading-relaxed text-[13.5px]">
          {{ localizedContent }}
        </div>
      </div>

      <div class="glass-card p-5 sm:p-7">
        <ArticleRatingWidget :slug="route.params.slug" />
      </div>

      <div class="glass-card p-5 sm:p-7">
        <ArticleComments :slug="route.params.slug" />
      </div>
    </article>
  </div>
</template>