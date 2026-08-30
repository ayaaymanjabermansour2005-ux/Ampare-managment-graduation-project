import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import articleService from "@/services/articleService";

export function useArticles() {
  const { t } = useI18n();
  const articles = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);
  const isSaving = ref(false);

  async function fetchArticles(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await articleService.listForAdmin({ page });
      const payload = data.data;
      articles.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? articles.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("articles_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  async function createArticle(payload) {
    isSaving.value = true;
    try {
      await articleService.create(payload);
      await fetchArticles();
      return true;
    } catch (err) {
      error.value = normalizeApiError(err, t("articles_page.create_error")).message;
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function updateArticle(id, payload) {
    isSaving.value = true;
    try {
      await articleService.update(id, payload);
      await fetchArticles(pagination.value.current_page);
      return true;
    } catch (err) {
      error.value = normalizeApiError(err, t("articles_page.update_error")).message;
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function deleteArticle(id) {
    try {
      await articleService.destroy(id);
      await fetchArticles(pagination.value.current_page);
    } catch (err) {
      error.value = normalizeApiError(err, t("articles_page.delete_error")).message;
    }
  }

  return {
    articles,
    pagination,
    isLoading,
    error,
    isSaving,
    fetchArticles,
    createArticle,
    updateArticle,
    deleteArticle,
  };
}
