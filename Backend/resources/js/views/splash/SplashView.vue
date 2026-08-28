<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useThemeSync } from '@/composables/useThemeSync'

const { t } = useI18n()
const uiStore = useThemeSync()
const isDark = computed(() => uiStore.isDark)

const props = defineProps({
  /** يصبح true عند اكتمال التحميل الفعلي (auth check, إعدادات...) */
  ready: {
    type: Boolean,
    default: false
  },
  /** حد أدنى لعرض الـ Splash بالمللي ثانية، لتفادي الوميض السريع */
  minDuration: {
    type: Number,
    default: 500
  }
})

const emit = defineEmits(['done'])

const visible = ref(true)
const startedAt = ref(Date.now())
const currentYear = computed(() => new Date().getFullYear())
// نفس مسار الشعار المستخدَم بباقي الموقع (HeroSection.vue وغيره) — ملف
// public عادي، وليس Vite asset import (لا يوجد resources/js/assets/branding).
const logoSrc = '/images/logo.png'

function hideSplash() {
  const elapsed = Date.now() - startedAt.value
  const remaining = Math.max(props.minDuration - elapsed, 0)
  setTimeout(() => {
    visible.value = false
  }, remaining)
}

watch(
  () => props.ready,
  (isReady) => {
    if (isReady) hideSplash()
  },
  { immediate: true }
)

onMounted(() => {
  startedAt.value = Date.now()
})

function onAfterLeave() {
  emit('done')
}
</script>

<template>
  <Transition name="splash-fade" @after-leave="onAfterLeave">
    <div v-if="visible" class="splash-shell" :class="{ 'is-dark': isDark, dark: isDark }" role="status" aria-live="polite">
      <!-- زخارف الزوايا -->
      <svg class="splash-corner splash-corner-tl" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g stroke="#D4AF37" stroke-width="1" fill="none" opacity="0.8">
          <path d="M20 0 L20 60 L70 60 L70 110 L120 110" />
          <path d="M0 40 L40 40 L40 90 L90 90" />
          <path d="M60 0 L60 30 L140 30 L140 80" stroke="#52733D" />
          <circle cx="70" cy="60" r="3" fill="#D4AF37" />
          <circle cx="40" cy="90" r="3" fill="#52733D" />
          <circle cx="140" cy="80" r="3" fill="#52733D" />
        </g>
      </svg>

      <svg class="splash-corner splash-corner-br" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g stroke="#52733D" stroke-width="1" fill="none" opacity="0.8">
          <path d="M20 0 L20 60 L70 60 L70 110 L120 110" />
          <path d="M0 40 L40 40 L40 90 L90 90" stroke="#D4AF37" />
          <path d="M60 0 L60 30 L140 30 L140 80" />
          <circle cx="70" cy="60" r="3" fill="#52733D" />
          <circle cx="40" cy="90" r="3" fill="#D4AF37" />
          <circle cx="140" cy="80" r="3" fill="#52733D" />
        </g>
      </svg>

      <!-- المحتوى المركزي -->
      <div class="splash-content">
        <div class="splash-logo-wrap">
          <img
            :src="logoSrc"
            :alt="t('splash.logoAlt')"
            width="220"
            height="220"
          />
        </div>

        <div class="splash-divider" aria-hidden="true"></div>

        <p class="splash-loading-text">
          <span>{{ t('splash.loading') }}</span>
          <span class="splash-dots" aria-hidden="true">
            <span></span><span></span><span></span>
          </span>
        </p>
      </div>

      <p class="splash-copyright">
        {{ t('splash.copyright', { year: currentYear }) }}
      </p>
    </div>
  </Transition>
</template>
