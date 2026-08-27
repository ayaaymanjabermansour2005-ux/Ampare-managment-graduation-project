<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
  status: { type: String, required: true },
});

const { t } = useI18n();

const CHIP_CLASS = {
  active: "chip-success",
  maintenance: "chip-warning",
  inactive: "chip-danger",
};

const chipClass = computed(() => CHIP_CLASS[props.status] ?? "chip-danger");
const label = computed(() => t(`owner_generators.status.${props.status}`, props.status));
</script>

<template>
  <span class="status-chip" :class="chipClass">{{ label }}</span>
</template>
