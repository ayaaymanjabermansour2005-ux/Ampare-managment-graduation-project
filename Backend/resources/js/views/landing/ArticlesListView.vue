<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import articleService from "@/services/articleService";
import { vReveal } from "@/directives/reveal";
import { ArrowLeft, ArrowRight, ChevronLeft, ChevronRight, Newspaper } from "@lucide/vue";

const { t, locale } = useI18n();

const articles = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
const isLoading = ref(true);
const error = ref(null);

function localizedTitle(article) {
  return locale.value === "en" && article.title_en ? article.title_en : article.title;
}
function localizedExcerpt(article) {
  return locale.value === "en" && article.excerpt_en ? article.excerpt_en : article.excerpt;
}

async function fetchArticles(page = 1) {
  isLoading.value = true;
  error.value = null;
  try {
    const { data } = await articleService.listPublic({ page });
    const payload = data.data;
    articles.value = payload.data ?? payload;
    pagination.value = {
      current_page: payload.current_page ?? 1,
      last_page: payload.last_page ?? 1,
      total: payload.total ?? articles.value.length,
      per_page: payload.per_page ?? 12,
    };
    window.scrollTo({ top: 0, behavior: "smooth" });
  } catch (err) {
    error.value = normalizeApiError(err, t("landing.articles_page.error_generic")).message;
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => fetchArticles());
</script>

<template>
  <div class="max-w-4xl mx-auto px-5 sm:px-8 py-16 space-y-10">
    <RouterLink
      :to="{ name: 'landing.home' }"
      class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5] hover:opacity-80"
    >
      <ArrowRight class="rtl:inline-block ltr:hidden" aria-hidden="true" />
      <ArrowLeft class="ltr:inline-block rtl:hidden" aria-hidden="true" />
      {{ t("landing.articles_page.back_to_home") }}
    </RouterLink>

    <div class="text-center" v-reveal>
      <span class="text-[12px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5]">{{ t("landing.articles_page.eyebrow") }}</span>
      <h1 class="text-3xl font-extrabold mt-2">{{ t("landing.articles_page.title") }}</h1>
    </div>

    <div v-if="error" class="bg-danger-bg text-danger text-[12.5px] rounded-xl p-4 text-center">
      {{ error }}
    </div>

    <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div v-for="i in 4" :key="i" class="glass-card h-48 animate-pulse"></div>
    </div>

    <div v-else-if="articles.length === 0" class="glass-card text-center py-16 text-[12.5px] text-[#9a9d97]">
      <Newspaper class="text-2xl mb-2 block" aria-hidden="true" />
      {{ t("landing.articles_page.empty") }}
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <RouterLink
        v-for="article in articles"
        :key="article.id"
        :to="{ name: 'landing.articles.show', params: { slug: article.slug } }"
        v-reveal
        class="glass-card block p-5 hover:-translate-y-1 transition-transform duration-300"
      >
        <img
          v-if="article.cover_image_url"
          :src="article.cover_image_url"
          :alt="localizedTitle(article)"
          class="w-full h-36 object-cover rounded-xl mb-3"
        />
        <h2 class="font-bold text-[14px] mb-1.5">{{ localizedTitle(article) }}</h2>
        <p v-if="article.excerpt" class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] line-clamp-2 leading-relaxed">
          {{ localizedExcerpt(article) }}
        </p>
        <p class="text-[10.5px] text-[#9a9d97] mt-2">
          {{ article.author_name }} — {{ article.published_at }}
        </p>
      </RouterLink>
    </div>

    <div v-if="pagination.last_page > 1" class="flex items-center justify-between text-[11.5px] text-[#9a9d97] dark:text-[#8f938a]">
      <span>{{ t("landing.articles_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
      <div class="flex items-center gap-1">
        <button :aria-label="t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchArticles(pagination.current_page - 1)" class="w-8 h-8 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40 flex items-center justify-center">
          <ChevronRight class="rtl:block ltr:hidden text-[12px]" aria-hidden="true" />
          <ChevronLeft class="ltr:block rtl:hidden text-[12px]" aria-hidden="true" />
        </button>
        <button :aria-label="t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchArticles(pagination.current_page + 1)" class="w-8 h-8 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40 flex items-center justify-center">
          <ChevronLeft class="rtl:block ltr:hidden text-[12px]" aria-hidden="true" />
          <ChevronRight class="ltr:block rtl:hidden text-[12px]" aria-hidden="true" />
        </button>
      </div>
    </div>
  </div>
</template>