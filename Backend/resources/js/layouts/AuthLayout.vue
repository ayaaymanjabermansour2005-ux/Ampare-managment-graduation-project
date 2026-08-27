<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { setLocale } from "@/i18n";
import { useThemeSync } from "@/composables/useThemeSync";
import { House, Moon, Sun } from "@lucide/vue";


const route = useRoute();
const { t, locale } = useI18n();

const uiStore = useThemeSync();
const isDark = computed(() => uiStore.isDark);
function toggleTheme() {
  uiStore.toggleTheme();
}

function toggleLanguage() {
  setLocale(locale.value === "ar" ? "en" : "ar");
}

const dir = computed(() => (locale.value === "ar" ? "rtl" : "ltr"));

const pageSubtitle = computed(() =>
  route.name === "register" ? t("auth.subtitle_register") : t("auth.subtitle_login")
);

const widthLevel = computed(() => {
  if (route.name === "register.owner" || route.name === "register.subscriber") return "wide";
  return "normal";
});

const cardEl = ref(null);
let isTouchDevice = false;
const MAX_TILT_DEG = 7;

function onTilt(e) {
  if (isTouchDevice || !cardEl.value || widthLevel.value !== "normal") return;
  const rect = cardEl.value.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  const centerX = rect.width / 2;
  const centerY = rect.height / 2;
  const rotateX = ((y - centerY) / centerY) * -MAX_TILT_DEG;
  const rotateY = ((x - centerX) / centerX) * MAX_TILT_DEG;
  cardEl.value.style.transform = `rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-4px) scale(1.015)`;
}
function resetTilt() {
  if (!cardEl.value) return;
  cardEl.value.style.transform = "rotateX(0deg) rotateY(0deg) translateY(0) scale(1)";
}

const canvasEl = ref(null);
let ctx, animFrame, lightnings = [], timer = 0;

class Lightning {
  constructor(canvas) {
    this.canvas = canvas;
    this.reset();
  }
  reset() {
    const canvas = this.canvas;
    this.startX = Math.random() * canvas.width;
    this.startY = Math.random() < 0.7 ? 0 : Math.random() * (canvas.height * 0.3);
    this.path = [];
    this.branches = [];
    this.life = 0;
    this.maxLife = 18 + Math.random() * 22;
    this.generatePath();
  }
  generatePath() {
    let currX = this.startX;
    let currY = this.startY;
    this.path.push({ x: currX, y: currY });
    const steps = 14 + Math.floor(Math.random() * 10);
    for (let i = 0; i < steps; i++) {
      currX += (Math.random() - 0.5) * 90;
      currY += (this.canvas.height / steps) * (0.8 + Math.random() * 0.4);
      this.path.push({ x: currX, y: currY });
      if (Math.random() < 0.45) {
        this.branches.push(this.generateBranch(currX, currY));
      }
    }
  }
  generateBranch(startX, startY) {
    const branch = [{ x: startX, y: startY }];
    let currX = startX;
    let currY = startY;
    const steps = 4 + Math.floor(Math.random() * 5);
    for (let i = 0; i < steps; i++) {
      currX += (Math.random() - 0.5) * 60;
      currY += 20 + Math.random() * 30;
      branch.push({ x: currX, y: currY });
    }
    return branch;
  }
  draw() {
    this.life++;
    let alpha = Math.max(0, 1 - this.life / this.maxLife);
    if (Math.random() < 0.25) alpha *= 0.3;
    ctx.save();
    ctx.shadowBlur = isDark.value ? 20 : 12;
    ctx.shadowColor = isDark.value ? "#D4AF37" : "#3E582E";
    ctx.strokeStyle = isDark.value
      ? `rgba(255, 248, 220, ${alpha})`
      : `rgba(62, 88, 46, ${alpha * 0.9})`;
    ctx.lineWidth = isDark.value ? 2.5 : 2.2;
    ctx.beginPath();
    this.path.forEach((p, i) => (i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y)));
    ctx.stroke();
    ctx.lineWidth = 1.2;
    ctx.strokeStyle = isDark.value
      ? `rgba(212, 175, 55, ${alpha * 0.75})`
      : `rgba(212, 175, 55, ${alpha * 0.85})`;
    this.branches.forEach((b) => {
      ctx.beginPath();
      b.forEach((p, i) => (i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y)));
      ctx.stroke();
    });
    ctx.restore();
  }
}

function resizeCanvas() {
  const canvas = canvasEl.value;
  if (!canvas) return;
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}

function animate() {
  const canvas = canvasEl.value;
  if (!canvas) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  timer++;
  if (timer % 50 === 0 || Math.random() < 0.03) {
    lightnings.push(new Lightning(canvas));
  }
  for (let i = lightnings.length - 1; i >= 0; i--) {
    lightnings[i].draw();
    if (lightnings[i].life >= lightnings[i].maxLife) {
      lightnings.splice(i, 1);
    }
  }
  animFrame = requestAnimationFrame(animate);
}

onMounted(() => {
  isTouchDevice = window.matchMedia("(pointer: coarse)").matches;

  const canvas = canvasEl.value;
  ctx = canvas.getContext("2d");
  resizeCanvas();
  window.addEventListener("resize", resizeCanvas);
  lightnings = [new Lightning(canvas), new Lightning(canvas)];
  animate();
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeCanvas);
  cancelAnimationFrame(animFrame);
});
</script>

<template>
  <div class="auth-shell" :class="{ 'is-dark': isDark, dark: isDark }" :dir="dir" :lang="locale">
    <div class="auth-bg-scene">
      <div class="auth-light-flash"></div>
      <div class="auth-glow auth-glow-top"></div>
      <div class="auth-glow auth-glow-bottom"></div>
      <canvas ref="canvasEl" class="auth-lightning-canvas"></canvas>
    </div>

    <div
      class="auth-tilt-wrapper"
      :class="{ 'auth-tilt-wrapper--wide': widthLevel === 'wide' }"
      @mousemove="onTilt"
      @mouseleave="resetTilt"
    >
      <div ref="cardEl" class="auth-tilt-card">
        <div class="flex items-center justify-center mb-3">
          <div class="auth-icon-btn-group">
            <RouterLink :to="{ name: 'landing.home' }" class="auth-icon-btn" :title="t('auth.home')">
              <House aria-hidden="true" />
            </RouterLink>
            <span class="auth-divider-pill"></span>
            <button
              type="button"
              class="auth-icon-btn text-[10.5px] font-bold"
              :title="t('auth.toggle_language')"
              @click="toggleLanguage"
            >
              {{ locale === "ar" ? "EN" : "AR" }}
            </button>
            <span class="auth-divider-pill"></span>
            <button type="button" class="auth-icon-btn" :title="t('auth.toggle_theme')" @click="toggleTheme">
              <Sun aria-hidden="true" v-if="isDark" /><Moon aria-hidden="true" v-else />
            </button>
          </div>
        </div>

        <!-- الشعار والاسم -->
        <div class="text-center mb-4">
          <div class="w-16 h-16 flex items-center justify-center mx-auto mb-1">
            <img
              src="/images/logo.png"
              :alt="t('auth.logo_alt')"
              class="w-full h-full object-contain drop-shadow"
              onerror="this.style.display='none'"
            />
          </div>
          <div class="auth-brand-name text-xl font-bold bg-gradient-to-r from-secondary-700 to-primary-600 bg-clip-text text-transparent mb-0.5">
            {{ t("auth.brand_name") }}
          </div>
          <div class="auth-brand-desc text-[10.5px] leading-relaxed">
            {{ t("auth.brand_desc") }}
          </div>
          <p class="text-[11px] text-gray-400 mt-1">{{ pageSubtitle }}</p>
        </div>

        <RouterView :is-dark="isDark" />
      </div>
    </div>
  </div>
</template>