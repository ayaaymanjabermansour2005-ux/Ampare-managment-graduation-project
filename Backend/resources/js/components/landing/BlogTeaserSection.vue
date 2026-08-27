<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import articleService from "@/services/articleService";
import { Newspaper, TriangleAlert } from "@lucide/vue";

const { t, locale } = useI18n();

const articles = ref([]);
const status = ref("loading"); // loading | ready | empty | error

function localizedTitle(article) {
  return locale.value === "en" && article.title_en ? article.title_en : article.title;
}
function localizedExcerpt(article) {
  return locale.value === "en" && article.excerpt_en ? article.excerpt_en : article.excerpt;
}

async function loadArticles() {
  status.value = "loading";
  try {
    const { data } = await articleService.listPublic();
    const payload = data.data;
    const list = payload.data ?? payload;
    articles.value = list.slice(0, 3);
    status.value = articles.value.length ? "ready" : "empty";
  } catch {
    status.value = "error";
  }
}

onMounted(loadArticles);
</script>

<template>
  <section id="blog" class="py-14 sm:py-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.blog.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.blog.title") }}</h2>
      </div>

      <div v-if="status === 'loading'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <div v-for="i in 3" :key="i" class="glass-card h-40 sm:h-48 animate-pulse"></div>
      </div>

      <div v-else-if="status === 'ready'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <RouterLink
          v-for="article in articles"
          :key="article.id"
          :to="{ name: 'landing.articles.show', params: { slug: article.slug } }"
          v-reveal
          class="glass-card block p-4 sm:p-6 hover:-translate-y-1 transition-transform duration-300"
        >
          <div v-if="article.cover_image_url" class="w-full h-28 sm:h-32 rounded-lg mb-2.5 sm:mb-3 overflow-hidden">
            <img :src="article.cover_image_url" :alt="localizedTitle(article)" class="w-full h-full object-cover" loading="lazy" />
          </div>
          <div v-else class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F] mb-2.5 sm:mb-3">
            <Newspaper aria-hidden="true" />
          </div>
          <h3 class="font-bold text-[13.5px] sm:text-[15px] mb-1.5 sm:mb-2 leading-snug">{{ localizedTitle(article) }}</h3>
          <p v-if="article.excerpt" class="text-[11.5px] sm:text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed line-clamp-2 mb-1.5 sm:mb-2">{{ localizedExcerpt(article) }}</p>
          <p class="text-[10px] sm:text-[10.5px] text-[#9a9d97]">{{ article.author_name }} — {{ article.published_at }}</p>
        </RouterLink>
      </div>

      <div v-else-if="status === 'empty'" class="text-center py-8 sm:py-10">
        <Newspaper class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97]">{{ t("landing.blog.empty") }}</p>
      </div>

      <div v-else-if="status === 'error'" class="text-center py-8 sm:py-10">
        <TriangleAlert class="text-2xl text-[#D9534F]/60 mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97]">{{ t("landing.blog.error") }}</p>
      </div>

    </div>
  </section>
</template>