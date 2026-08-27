<script setup>
import { useToastStore } from "@/stores/toast";
import { X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const toastStore = useToastStore();

const TYPE_META = {
  info: { icon: "fa-bell", accent: "#8A6D1F", gradient: "linear-gradient(135deg, #8A6D1F, #D4AF37)" },
  success: { icon: "fa-circle-check", accent: "#52733D", gradient: "linear-gradient(135deg, #3E582E, #52733D)" },
  warning: { icon: "fa-triangle-exclamation", accent: "#D4AF37", gradient: "linear-gradient(135deg, #a3760a, #D4AF37)" },
  danger: { icon: "fa-circle-exclamation", accent: "#D9534F", gradient: "linear-gradient(135deg, #8A2F2A, #D9534F)" },
};
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 start-4 z-[200] flex flex-col gap-2.5 w-full max-w-xs">
      <TransitionGroup
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="opacity-0 -translate-x-4"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-200 ease-in absolute"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-for="t in toastStore.toasts"
          :key="t.id"
          class="toast-card flex items-start gap-2.5 p-3.5 rounded-xl shadow-2xl cursor-pointer"
          :style="{ '--toast-accent': TYPE_META[t.type]?.accent ?? '#8A6D1F' }"
          @click="toastStore.dismiss(t.id)"
        >
          <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0 shadow-md" :style="{ background: TYPE_META[t.type]?.gradient ?? TYPE_META.info.gradient }">
            <AppIcon :name="TYPE_META[t.type]?.icon ?? 'fa-bell'" class="text-[11px]" />
          </span>
          <div class="flex-1 min-w-0">
            <p v-if="t.title" class="text-[12.5px] font-bold">{{ t.title }}</p>
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ t.message }}</p>
          </div>
          <button :aria-label="$t('common.close')" type="button" class="text-[#9a9d97] hover:text-[#D9534F] shrink-0" @click.stop="toastStore.dismiss(t.id)">
            <X class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.88) 100%);
  backdrop-filter: blur(18px) saturate(1.3);
  -webkit-backdrop-filter: blur(18px) saturate(1.3);
  border: 1px solid rgba(255, 255, 255, 0.6);
  border-left: 3px solid var(--toast-accent, #8A6D1F);
}
:global(.dark) .toast-card {
  background: linear-gradient(135deg, rgba(37, 40, 38, 0.92) 0%, rgba(31, 33, 34, 0.88) 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-left: 3px solid var(--toast-accent, #8A6D1F);
}
</style>