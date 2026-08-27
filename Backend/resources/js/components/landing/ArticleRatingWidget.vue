<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import articleCommentService from "@/services/articleCommentService";
import { CircleCheck, Star } from "@lucide/vue";

const props = defineProps({
  slug: { type: String, required: true },
});

const { t, locale } = useI18n();

const average = ref(0);
const count = ref(0);
const myRating = ref(null);
const hoverStar = ref(0);
const isLoading = ref(true);
const isSubmitting = ref(false);

async function load() {
  isLoading.value = true;
  try {
    const { data } = await articleCommentService.ratingShow(props.slug);
    average.value = data.data.average;
    count.value = data.data.count;
    myRating.value = data.data.my_rating;
  } catch {
    // فشل تحميل التقييم لا يجب أن يكسر الصفحة — تبقى الحالة الافتراضية بصمت.
  } finally {
    isLoading.value = false;
  }
}

async function submitRating(star) {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  try {
    const { data } = await articleCommentService.ratingStore(props.slug, star);
    average.value = data.data.average;
    count.value = data.data.count;
    myRating.value = data.data.my_rating;
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="flex items-center gap-3 flex-wrap">
    <div class="flex items-center gap-1" @mouseleave="hoverStar = 0">
      <button
        v-for="star in 5" :key="star" type="button"
        :disabled="isSubmitting"
        class="text-lg leading-none transition-transform hover:scale-110 disabled:opacity-60"
        @mouseenter="hoverStar = star"
        @click="submitRating(star)"
        :title="t('landing.articles_page.rate_star', { n: star })"
      >
        <Star
          :class="(hoverStar || myRating || Math.round(average)) >= star ? 'text-[#D4AF37]' : 'text-[#c9c4b4]'"
          :fill="(hoverStar || myRating || Math.round(average)) >= star ? 'currentColor' : 'none'"
          aria-hidden="true"
        />
      </button>
    </div>
    <span v-if="!isLoading" class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
      <template v-if="count > 0">{{ average }} · {{ count }} {{ t("landing.articles_page.ratings_count") }}</template>
      <template v-else>{{ t("landing.articles_page.no_ratings_yet") }}</template>
    </span>
    <span v-if="myRating" class="text-[10.5px] text-[#28A745] font-semibold">
      <CircleCheck aria-hidden="true" /> {{ t("landing.articles_page.you_rated", { n: myRating }) }}
    </span>
  </div>
</template>
