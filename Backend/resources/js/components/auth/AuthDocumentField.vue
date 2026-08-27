<script setup>
import { computed } from "vue";
import { X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const props = defineProps({
  label: { type: String, required: true },
  hint: { type: String, default: "" },
  icon: { type: String, default: "fa-file" }, // font-awesome class, no "fa-solid" prefix needed
  required: { type: Boolean, default: false },
  accept: { type: String, default: "" },
  file: { type: Object, default: null }, // File | null
  error: { type: String, default: null },
  removeLabel: { type: String, default: "" },
});

const emit = defineEmits(["select", "remove"]);

const displayIcon = computed(() => {
  if (!props.file) return props.icon;
  const ext = props.file.name.split(".").pop()?.toLowerCase();
  return ext === "pdf" ? "fa-file-pdf" : "fa-file-image";
});

function onInputChange(event) {
  const file = event.target.files?.[0] ?? null;
  event.target.value = "";
  if (file) emit("select", file);
}
</script>

<template>
  <div>
    <label class="auth-field-label block text-[10.5px] font-semibold mb-[3px]">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>

    <div class="auth-doc-card-wrap">
      <label class="auth-doc-card" :class="{ 'is-filled': !!file, 'is-error': !!error }">
        <input type="file" :accept="accept" class="hidden" @change="onInputChange" />

        <template v-if="file">
          <span class="auth-doc-card-icon"><AppIcon :name="displayIcon" /></span>
          <span class="auth-doc-card-filename">{{ file.name }}</span>
        </template>
        <template v-else>
          <span class="auth-doc-card-icon"><AppIcon :name="icon" /></span>
          <span class="auth-doc-card-label">{{ label }}</span>
          <span v-if="hint" class="auth-doc-card-hint">{{ hint }}</span>
        </template>
      </label>

      <button
        v-if="file"
        type="button"
        class="auth-doc-card-remove"
        :title="removeLabel"
        :aria-label="removeLabel"
        @click.stop="emit('remove')"
      >
        <X aria-hidden="true" />
      </button>
    </div>

    <p v-if="error" class="auth-field-error">{{ error }}</p>
  </div>
</template>
