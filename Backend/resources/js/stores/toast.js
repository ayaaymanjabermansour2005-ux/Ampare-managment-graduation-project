import { defineStore } from "pinia";
import { ref } from "vue";

let nextId = 1;

export const useToastStore = defineStore("toast", () => {
  const toasts = ref([]);

  function show({ title, message, type = "info", duration = 6000 }) {
    const id = nextId++;
    toasts.value.push({ id, title, message, type });

    if (duration > 0) {
      setTimeout(() => dismiss(id), duration);
    }
    return id;
  }

  function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }

  return { toasts, show, dismiss };
});