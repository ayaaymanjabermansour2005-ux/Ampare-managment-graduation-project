<script setup>
import { Check } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


import { ref, computed, nextTick, onBeforeUnmount } from "vue";

const props = defineProps({
  modelValue: { type: [String, Number, null], default: "" },
  options: {
    type: Array, // [{ value: string|number, label: string }]
    required: true,
  },
  placeholder: { type: String, default: "" },
  widthClass: { type: String, default: "w-40" },
  panelWidth: { type: Number, default: 224 }, // px — يُتجاهَل لو matchTriggerWidth مفعّل
  matchTriggerWidth: { type: Boolean, default: false }, // لما تكون true: عرض القائمة المنسدلة = عرض الزر بالضبط
  disabled: { type: Boolean, default: false },
  variant: { type: String, default: "pill" }, // 'pill' (فلاتر مضغوطة) | 'field' (حقل فورم عادي بنفس مقاس/شكل .field-input)
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const triggerEl = ref(null);
const panelEl = ref(null);
const panelStyle = ref({});

const selectedOption = computed(() =>
  props.options.find((o) => o.value === props.modelValue) ?? null,
);
const selectedLabel = computed(() => selectedOption.value?.label ?? props.placeholder);

function computePosition() {
  if (!triggerEl.value) return;
  const rect = triggerEl.value.getBoundingClientRect();
  const margin = 8;
  const effectiveWidth = props.matchTriggerWidth ? rect.width : props.panelWidth;

  // محاذاة حافة القائمة اليمنى مع حافة الزر اليمنى (نفس محاذاة النص العربي)
  // — لو matchTriggerWidth مفعّل، العرضين متساويين فالنتيجة تلقائيًا محاذاة تحت الزر بالضبط
  let left = rect.right - effectiveWidth;
  if (left < margin) left = margin;
  if (left + effectiveWidth > window.innerWidth - margin) {
    left = window.innerWidth - effectiveWidth - margin;
  }

  // لو ما في مساحة كافية تحت الزر، افتح القائمة لفوق بدل تحت
  const estimatedHeight = Math.min(256, props.options.length * 36 + 12);
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUpward = spaceBelow < estimatedHeight + margin && rect.top > estimatedHeight;

  panelStyle.value = {
    position: "fixed",
    left: `${left}px`,
    width: `${effectiveWidth}px`,
    ...(openUpward
      ? { bottom: `${window.innerHeight - rect.top + 6}px` }
      : { top: `${rect.bottom + 6}px` }),
  };
}

async function toggle() {
  if (props.disabled) return;
  if (isOpen.value) {
    close();
    return;
  }
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
function selectOption(option) {
  emit("update:modelValue", option.value);
  close();
}

onBeforeUnmount(close);
</script>

<template>
  <div class="relative shrink-0" :class="widthClass">
    <button
      v-if="variant === 'field'"
      ref="triggerEl"
      type="button"
      :disabled="disabled"
      @click="toggle"
      class="field-input w-full flex items-center justify-between gap-2 cursor-pointer !bg-[#f4efe5]/70 dark:!bg-white/5 !border-[#e7e2d6] dark:!border-white/10 !leading-normal disabled:opacity-50 disabled:cursor-not-allowed box-border"
      :class="isOpen ? '!border-[#8A6D1F]' : 'hover:!border-[#c9c0a6] dark:hover:!border-white/20'"
    >
      <span class="truncate" :class="selectedOption ? '' : 'text-[#9a9d97] dark:text-[#8f938a]'">{{ selectedLabel }}</span>
      <AppIcon
        name="chevron-down"
        class="text-[9px] text-[#8A6D1F] dark:text-[#D4AF37] transition-transform duration-150 shrink-0"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>
    <button
      v-else
      ref="triggerEl"
      type="button"
      :disabled="disabled"
      @click="toggle"
      class="w-full flex items-center justify-between gap-2 bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full ps-3.5 pe-3 py-2 text-[11.5px] font-bold outline-none transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      :class="isOpen ? '!border-[#8A6D1F]' : 'hover:border-[#c9c0a6] dark:hover:border-white/20'"
    >
      <span class="truncate" :class="selectedOption ? '' : 'text-[#9a9d97] dark:text-[#8f938a] font-normal'">{{ selectedLabel }}</span>
      <AppIcon
        name="chevron-down"
        class="text-[9px] text-[#8A6D1F] dark:text-[#D4AF37] transition-transform duration-150 shrink-0"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <Teleport to="body">
      <Transition name="dropdown-fade">
        <div
          v-if="isOpen"
          ref="panelEl"
          :style="panelStyle"
          class="z-[999] bg-white dark:bg-[#1c1e20] border border-[#e7e2d6] dark:border-white/10 rounded-xl shadow-xl py-1.5 max-h-64 overflow-y-auto"
          role="listbox"
        >
          <button
            v-for="opt in options" :key="opt.value" type="button"
            role="option" :aria-selected="opt.value === modelValue"
            @click="selectOption(opt)"
            class="w-full text-start px-3.5 py-2 text-[11.5px] font-semibold transition-colors flex items-center justify-between gap-2"
            :class="opt.value === modelValue
              ? 'bg-[#EBF1E7] dark:bg-white/10 text-[#3E582E] dark:text-[#8cc35a]'
              : 'text-[#3a3a38] dark:text-[#d8d9d4] hover:bg-[#f4efe5] dark:hover:bg-white/5'"
          >
            <span class="truncate">{{ opt.label }}</span>
            <Check class="text-[10px] text-[#8A6D1F] shrink-0" aria-hidden="true" v-if="opt.value === modelValue" />
          </button>
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