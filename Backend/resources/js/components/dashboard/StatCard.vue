<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { vCountUp } from "@/directives/countUp";
import AppIcon from "@/components/ui/AppIcon.vue";

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: String, required: true },
  tone: {
    type: String,
    default: "primary",
    validator: (v) =>
      ["primary", "secondary", "success", "warning", "danger"].includes(v),
  },
  suffix: { type: String, default: "" },
});

const { locale } = useI18n();

const TONE_COLORS = {
  primary: ["#52733D", "#3E582E"],
  secondary: ["#8A6D1F", "#D4AF37"],
  success: ["#28A745", "#1f7a37"],
  warning: ["#FFC107", "#a3760a"],
  danger: ["#D9534F", "#b8352f"],
};

const cssVars = computed(() => {
  const [c1, c2] = TONE_COLORS[props.tone];
  return { "--kpi-color": c1, "--kpi-color2": c2 };
});

/* الـ v-count-up يتعامل فقط مع أرقام؛ لو القيمة نص جاهز (مثل "3 / 5") تُعرض كما هي بدون عدّاد */
const isNumeric = computed(() => typeof props.value === "number" || /^-?\d+(\.\d+)?$/.test(String(props.value)));
</script>

<template>
  <div class="kpi-card glass-card hoverable" :style="cssVars">
    <div class="flex items-start justify-between mb-2.5">
      <div class="kpi-icon"><AppIcon :name="icon" /></div>
    </div>
    <div class="text-xl font-extrabold" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
      <span v-if="isNumeric" v-count-up="Number(value)">0</span>
      <span v-else>{{ value }}</span>
      <span v-if="suffix" class="text-[13px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] ms-1">{{ suffix }}</span>
    </div>
    <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1 truncate">{{ label }}</div>
    <div class="bar-track mt-2.5"><div class="bar-fill" style="width:100%; opacity:.35" :style="{ background: cssVars['--kpi-color'] }"></div></div>
  </div>
</template>
