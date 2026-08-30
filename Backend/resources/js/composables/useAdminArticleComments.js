import { ref } from "vue";
import { useI18n } from "vue-i18n";
import articleCommentService from "@/services/articleCommentService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useAdminArticleComments() {
  const { t } = useI18n();
  const comments = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
  const isLoading = ref(false);
  const error = ref(null);
  const statusFilter = ref("pending");

  async function fetchComments(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await articleCommentService.adminList({ page, status: statusFilter.value });
      const payload = data.data;
      comments.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? comments.value.length,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("article_comments_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  function onFilterChange() {
    fetchComments(1);
  }

  const isActing = ref(false);

  async function approve(id) {
    isActing.value = true;
    try {
      await articleCommentService.approve(id);
      comments.value = comments.value.filter((c) => c.id !== id);
    } finally {
      isActing.value = false;
    }
  }

  async function reject(id) {
    isActing.value = true;
    try {
      await articleCommentService.reject(id);
      comments.value = comments.value.filter((c) => c.id !== id);
    } finally {
      isActing.value = false;
    }
  }

  async function destroy(id) {
    isActing.value = true;
    try {
      await articleCommentService.destroy(id);
      comments.value = comments.value.filter((c) => c.id !== id);
    } finally {
      isActing.value = false;
    }
  }

  async function reply(id, adminReply) {
    isActing.value = true;
    try {
      const { data } = await articleCommentService.reply(id, adminReply);
      const index = comments.value.findIndex((c) => c.id === id);
      if (index !== -1) comments.value[index] = data.data;
      return true;
    } catch {
      return false;
    } finally {
      isActing.value = false;
    }
  }

  async function deleteReply(id) {
    isActing.value = true;
    try {
      const { data } = await articleCommentService.deleteReply(id);
      const index = comments.value.findIndex((c) => c.id === id);
      if (index !== -1) comments.value[index] = data.data;
    } finally {
      isActing.value = false;
    }
  }

  return {
    comments,
    pagination,
    isLoading,
    error,
    statusFilter,
    fetchComments,
    onFilterChange,
    isActing,
    approve,
    reject,
    destroy,
    reply,
    deleteReply,
  };
}
