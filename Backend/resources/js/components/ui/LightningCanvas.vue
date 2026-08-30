<script setup>
/**
 * تأثير الصواعق الخلفي الزخرفي في أقسام الـ Hero (هوية أمبير البصرية — منقول
 * من subscribers.html/generators-owners.html). مكوّن عرض بحت بدون منطق عمل —
 * منقول من SubscribersView.vue (FRONT-004a slice 5، God-component breakdown)؛
 * كان نفس الكود مكرّرًا حرفيًا بـ GeneratorOwnersView.vue أيضًا (FRONT-004b) —
 * هذا المكوّن يوحّدهما.
 */
import { ref, onMounted, onUnmounted } from "vue";

const canvasEl = ref(null);
let stopLightning = null;

function startLightningEffect(canvas) {
  const ctx = canvas.getContext("2d");
  function resize() {
    canvas.width = canvas.parentElement.offsetWidth;
    canvas.height = canvas.parentElement.offsetHeight;
  }
  window.addEventListener("resize", resize);
  resize();

  class Lightning {
    constructor() {
      this.reset();
    }
    reset() {
      this.startX = Math.random() * canvas.width;
      this.startY = Math.random() * (canvas.height * 0.3);
      this.path = [];
      this.life = 0;
      this.maxLife = 20 + Math.random() * 20;
      let x = this.startX;
      let y = this.startY;
      this.path.push({ x, y });
      const steps = 8 + Math.floor(Math.random() * 6);
      for (let i = 0; i < steps; i++) {
        x += (Math.random() - 0.5) * 70;
        y += (canvas.height / steps) * (0.8 + Math.random() * 0.4);
        this.path.push({ x, y });
      }
    }
    draw() {
      this.life++;
      let alpha = Math.max(0, 1 - this.life / this.maxLife);
      if (Math.random() < 0.25) alpha *= 0.3;
      const dark = document.documentElement.classList.contains("dark");
      ctx.save();
      ctx.shadowBlur = dark ? 16 : 9;
      ctx.shadowColor = dark ? "#D4AF37" : "#3E582E";
      ctx.strokeStyle = dark ? `rgba(255,248,220,${alpha * 0.5})` : `rgba(62,88,46,${alpha * 0.35})`;
      ctx.lineWidth = 1.4;
      ctx.beginPath();
      this.path.forEach((p, i) => (i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y)));
      ctx.stroke();
      ctx.restore();
    }
  }

  let bolts = [new Lightning()];
  let timer = 0;
  let frame = null;
  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    timer++;
    if (timer % 90 === 0 || Math.random() < 0.01) bolts.push(new Lightning());
    for (let i = bolts.length - 1; i >= 0; i--) {
      bolts[i].draw();
      if (bolts[i].life >= bolts[i].maxLife) bolts.splice(i, 1);
    }
    frame = requestAnimationFrame(animate);
  }
  animate();

  return () => {
    window.removeEventListener("resize", resize);
    if (frame) cancelAnimationFrame(frame);
  };
}

onMounted(() => {
  if (canvasEl.value) stopLightning = startLightningEffect(canvasEl.value);
});
onUnmounted(() => {
  if (stopLightning) stopLightning();
});
</script>

<template>
  <canvas ref="canvasEl" class="absolute inset-0 w-full h-full pointer-events-none opacity-70"></canvas>
</template>
