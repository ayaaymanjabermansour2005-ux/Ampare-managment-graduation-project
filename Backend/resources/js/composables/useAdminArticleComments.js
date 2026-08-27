import { ref } from "vue";
import { useI18n } from "vue-i18n";
import articleCommentService from "@/services/articleCommentService";

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
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
        total: payload.total ?? comments.value.length,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("article_comments_page.load_error");
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
