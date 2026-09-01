<script setup>
import { ref, computed, nextTick, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { Filter, X } from "@lucide/vue";

const props = defineProps({
  modelValue: {
    // { min: '', max: '' } for type=number/date, or { from: '', to: '' } for type=date
    type: Object,
    default: () => ({ min: "", max: "" }),
  },
  type: { type: String, default: "number" }, // 'number' | 'date'
});

const emit = defineEmits(["update:modelValue"]);

const { t } = useI18n();

const isOpen = ref(false);
const triggerEl = ref(null);
const panelEl = ref(null);
const panelStyle = ref({});

const draft = ref({ min: props.modelValue?.min ?? "", max: props.modelValue?.max ?? "" });

const isActive = computed(() => Boolean(props.modelValue?.min || props.modelValue?.max));

function computePosition() {
  if (!triggerEl.value) return;
  const rect = triggerEl.value.getBoundingClientRect();
  const margin = 8;
  const width = 220;
  let left = rect.right - width;
  if (left < margin) left = margin;
  if (left + width > window.innerWidth - margin) left = window.innerWidth - width - margin;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUpward = spaceBelow < 180 && rect.top > 180;
  panelStyle.value = {
    position: "fixed",
    left: `${left}px`,
    width: `${width}px`,
    ...(openUpward ? { bottom: `${window.innerHeight - rect.top + 6}px` } : { top: `${rect.bottom + 6}px` }),
  };
}

async function toggle() {
  if (isOpen.value) {
    close();
    return;
  }
  draft.value = { min: props.modelValue?.min ?? "", max: props.modelValue?.max ?? "" };
  isOpen.value = true;
  await nextTick();
  computePosition();
  window.addEventListener("scroll", onScrollOrResize, true);
  window.addEventListener("resize", onScrollOrResize);
  document.addEventListener("mousedown", onClickOutside);
  document.addEventListener("keydown", onKeydown);
}

function close() {
  if (!isOpen.value) return;
  isOpen.value = false;
  window.removeEventListener("scroll", onScrollOrResize, true);
  window.removeEventListener("resize", onScrollOrResize);
  document.removeEventListener("mousedown", onClickOutside);
  document.removeEventListener("keydown", onKeydown);
}

function onScrollOrResize() {
  computePosition();
}
function onClickOutside(e) {
  if (
    (triggerEl.value && triggerEl.value.contains(e.target)) ||
    (panelEl.value && panelEl.value.contains(e.target))
  ) {
    return;
  }
  close();
}
function onKeydown(e) {
  if (e.key === "Escape") close();
}

function apply() {
  emit("update:modelValue", { min: draft.value.min, max: draft.value.max });
  close();
}

function clear() {
  draft.value = { min: "", max: "" };
  emit("update:modelValue", { min: "", max: "" });
  close();
}

onBeforeUnmount(close);
</script>

<template>
  <div class="relative inline-flex shrink-0">
    <button
      ref="triggerEl"
      type="button"
      @click="toggle"
      class="w-5 h-5 flex items-center justify-center rounded transition-colors shrink-0"
      :class="isActive ? 'text-[#8A6D1F] dark:text-[#D4AF37]' : 'text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F] dark:hover:text-[#D4AF37]'"
      :aria-label="t('common.filter')"
    >
      <Filter class="text-[11px]" :fill="isActive ? 'currentColor' : 'none'" aria-hidden="true" />
    </button>

    <Teleport to="body">
      <Transition name="dropdown-fade">
        <div
          v-if="isOpen"
          ref="panelEl"
          :style="panelStyle"
          class="z-[999] bg-white dark:bg-[#1c1e20] border border-[#e7e2d6] dark:border-white/10 rounded-xl shadow-xl p-3 space-y-2.5"
        >
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[10px] font-bold text-[#9a9d97] dark:text-[#8f938a] block mb-1">{{ t("common.min") }}</label>
              <input
                v-model="draft.min"
                :type="type"
                class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-2 py-1.5 text-[11.5px] outline-none focus:border-[#8A6D1F]"
              />
            </div>
            <div>
              <label class="text-[10px] font-bold text-[#9a9d97] dark:text-[#8f938a] block mb-1">{{ t("common.max") }}</label>
              <input
                v-model="draft.max"
                :type="type"
                class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-2 py-1.5 text-[11.5px] outline-none focus:border-[#8A6D1F]"
              />
            </div>
          </div>
          <div class="flex items-center justify-between gap-2 pt-1">
            <button type="button" @click="clear" class="text-[10.5px] font-bold text-[#9a9d97] hover:text-[#D9534F] flex items-center gap-1">
              <X class="text-[9px]" aria-hidden="true" />{{ t("common.clear") }}
            </button>
            <button type="button" @click="apply" class="btn-fill relative text-[10.5px] font-bold px-3 py-1.5 rounded-full">
              {{ t("common.apply") }}
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.12s ease, transform 0.12s ease;
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
