<script setup>
import { RouterLink } from "vue-router";
import StatusPulse from "@/components/common/StatusPulse.vue";
import { Zap } from "@lucide/vue";

defineProps({
  generator: { type: Object, required: true },
});
</script>

<template>
  <RouterLink
    :to="{ name: 'owner.generators', query: { highlight: generator.id } }"
    class="flex items-center justify-between gap-3 p-3 rounded-xl border border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/40 dark:bg-white/[0.03] hover:!border-[#D4AF37]/50 hover:bg-[#f4efe5]/70 dark:hover:bg-white/5 transition-colors duration-200"
  >
    <div class="flex items-center gap-3 min-w-0">
      <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center shrink-0">
        <Zap class="text-white text-sm" aria-hidden="true" />
      </div>
      <div class="min-w-0">
        <p class="text-[12.5px] font-bold truncate">{{ generator.name }}</p>
        <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ generator.capacity_kw }} kW</p>
      </div>
    </div>

    <StatusPulse :status="generator.status" size="sm" />
  </RouterLink>
</template>
