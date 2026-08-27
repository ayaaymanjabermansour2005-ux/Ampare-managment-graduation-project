<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { ChevronDown } from "@lucide/vue";

const props = defineProps({
  modelValue: { type: [String, Number], default: "" },
  options: { type: Array, required: true }, // [{ value, label }]
  placeholder: { type: String, default: "" },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const rootEl = ref(null);

const selectedLabel = computed(() => {
  const match = props.options.find((o) => String(o.value) === String(props.modelValue));
  return match?.label ?? props.placeholder;
});

function toggle() {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
}
function select(option) {
  emit("update:modelValue", option.value);
  isOpen.value = false;
}
function onClickOutside(e) {
  if (rootEl.value && !rootEl.value.contains(e.target)) isOpen.value = false;
}
onMounted(() => document.addEventListener("click", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", onClickOutside));
</script>

<template>
  <div ref="rootEl" class="relative">
    <button
      type="button"
      class="auth-select-trigger"
      :class="{ 'is-disabled': disabled, 'is-invalid': invalid, 'is-open': isOpen }"
      :disabled="disabled"
      @click="toggle"
    >
      <span class="auth-select-label" :class="{ 'is-placeholder': modelValue === '' || modelValue === null }">{{ selectedLabel }}</span>
      <ChevronDown class="auth-select-chevron" :class="{ 'is-open': isOpen }" aria-hidden="true" />
    </button>

    <div v-if="isOpen" class="auth-select-panel">
      <button
        v-for="option in options"
        :key="option.value"
        type="button"
        class="auth-select-option"
        :class="{ 'is-selected': String(option.value) === String(modelValue) }"
        @click="select(option)"
      >
        {{ option.label }}
      </button>
    </div>
  </div>
</template>

<style>
.auth-select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  width: 100%;
  box-sizing: border-box;
  padding: 0.55rem 0.75rem;
  min-height: 2.2rem;
  border-radius: 0.55rem;
  font-size: 12.5px;
  font-family: inherit;
  color: #333333;
  text-align: start;
  cursor: pointer;
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(6px);
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}
.auth-select-trigger:hover:not(.is-disabled) {
  border-color: rgba(138, 109, 31, 0.35);
}
.auth-select-trigger.is-open {
  border-color: var(--color-secondary-500);
  box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.16);
  background: rgba(255, 255, 255, 0.72);
}
.auth-select-trigger.is-invalid {
  border-color: #e73f3f;
}
.auth-select-trigger.is-disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
.auth-shell.is-dark .auth-select-trigger {
  background: rgba(37, 40, 38, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #f0f0ec;
}
.auth-shell.is-dark .auth-select-trigger:hover:not(.is-disabled) {
  border-color: rgba(244, 224, 165, 0.3);
}
.auth-shell.is-dark .auth-select-trigger.is-open {
  border-color: var(--color-secondary-300);
  box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
  background: rgba(37, 40, 38, 0.68);
}
.auth-shell.is-dark .auth-select-trigger.is-invalid {
  border-color: #f0726d;
}

.auth-select-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.auth-select-label.is-placeholder {
  color: #8e8e8e;
}

.auth-select-chevron {
  flex-shrink: 0;
  font-size: 10px;
  color: #8a6d1f;
  transition: transform 0.2s ease;
}
.auth-select-chevron.is-open {
  transform: rotate(180deg);
}

.auth-select-panel {
  position: absolute;
  z-index: 30;
  margin-top: 0.375rem;
  width: 100%;
  max-height: 14rem;
  overflow-y: auto;
  border-radius: 0.75rem;
  padding: 0.375rem 0;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid #e7e2d6;
  backdrop-filter: blur(20px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
}
.auth-shell.is-dark .auth-select-panel {
  background: rgba(28, 30, 32, 0.98);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.auth-select-option {
  display: block;
  width: 100%;
  text-align: start;
  padding: 0.5rem 0.9rem;
  font-size: 12px;
  color: inherit;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.auth-select-option:hover {
  background: rgba(138, 109, 31, 0.12);
  color: #8a6d1f;
}
.auth-shell.is-dark .auth-select-option:hover {
  background: rgba(212, 175, 55, 0.15);
  color: #f4e0a5;
}
.auth-select-option.is-selected {
  background: rgba(138, 109, 31, 0.1);
  color: #8a6d1f;
  font-weight: 700;
}
.auth-shell.is-dark .auth-select-option.is-selected {
  background: rgba(212, 175, 55, 0.12);
  color: #f4e0a5;
}
</style>