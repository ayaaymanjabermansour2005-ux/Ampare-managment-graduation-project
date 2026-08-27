import { ref, computed } from "vue";

export function usePagination(fetchFn) {
  const currentPage = ref(1);
  const perPage = ref(15);

  async function goToPage(page) {
    currentPage.value = page;
    await fetchFn({ page: currentPage.value, per_page: perPage.value });
  }

  return { currentPage, perPage, goToPage };
}
