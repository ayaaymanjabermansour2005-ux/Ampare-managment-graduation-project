<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useConfirmDialogState } from "@/composables/useConfirm";
import { CircleCheck, TriangleAlert, X } from "@lucide/vue";


const { t } = useI18n();
const { state, handleConfirm, handleCancel } = useConfirmDialogState();

const isDanger = computed(() => state.variant === "danger");
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-250 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="state.isOpen"
        class="fixed inset-0 z-[150] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
        @keydown.esc="handleCancel"
        @click.self="handleCancel"
      >
        <div
          class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl"
          role="alertdialog"
          aria-modal="true"
        >
          <div class="modal-head-brand" :class="isDanger ? 'modal-head-brand--danger' : 'modal-head-brand--gold'">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><TriangleAlert aria-hidden="true" v-if="isDanger" /><CircleCheck aria-hidden="true" v-else /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ state.title }}</h3>
              </div>
            </div>
            <button v-if="!state.hideCancel" type="button" @click="handleCancel" class="modal-head-brand__close" :aria-label="t('common.close')">
              <X aria-hidden="true" />
            </button>
          </div>

          <div v-if="state.message" class="p-5 text-center">
            <p class="text-[12.5px] leading-relaxed text-[#4b4b4b] dark:text-[#d7d9d3]">{{ state.message }}</p>
          </div>

          <div class="modal-footer-brand">
            <button v-if="!state.hideCancel" type="button" @click="handleCancel" class="btn-outline-brand">
              {{ state.cancelLabel }}
            </button>
            <button
              type="button"
              @click="handleConfirm"
              autofocus
              class="btn-fill-brand"
              :class="isDanger ? 'btn-fill-brand--danger' : ''"
            >
              {{ state.confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
