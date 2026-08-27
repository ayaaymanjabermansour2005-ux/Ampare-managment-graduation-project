<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (v) => ["active", "inactive", "maintenance"].includes(v),
  },
  label: { type: String, default: null },
  size: {
    type: String,
    default: "md",
    validator: (v) => ["sm", "md", "lg"].includes(v),
  },
});

const { t } = useI18n();

const STATUS_MAP = {
  active: {
    color: "text-success",
    bg: "bg-success",
    textKey: "common.status_pulse.active",
    pulse: true,
  },
  inactive: {
    color: "text-[#9a9d97] dark:text-[#8f938a]",
    bg: "bg-[#9a9d97] dark:bg-[#8f938a]",
    textKey: "common.status_pulse.inactive",
    pulse: false,
  },
  maintenance: {
    color: "text-warning",
    bg: "bg-warning",
    textKey: "common.status_pulse.maintenance",
    pulse: true,
  },
};

const config = computed(() => STATUS_MAP[props.status]);
const dotSize = computed(
  () => ({ sm: "w-1.5 h-1.5", md: "w-2 h-2", lg: "w-2.5 h-2.5" })[props.size],
);
</script>

<template>
  <span class="inline-flex items-center gap-1.5" :class="config.color">
    <span class="relative flex" :class="dotSize">
      <span
        v-if="config.pulse"
        class="absolute inset-0 rounded-full status-dot-live"
        :class="config.bg"
      />
      <span
        class="relative inline-flex rounded-full w-full h-full"
        :class="config.bg"
      />
    </span>
    <span v-if="label !== ''" class="text-xs font-medium">{{
      label ?? t(config.textKey)
    }}</span>
  </span>
</template>
