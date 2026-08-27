import { onMounted, onUnmounted } from "vue";

/**
 * @param {import('vue').Ref<HTMLCanvasElement|null>} canvasRef
 * @param {{ density?: number, isDark: () => boolean }} options
 */
export function useLightningCanvas(canvasRef, options = {}) {
  const density = options.density ?? 1;
  const isDark = options.isDark ?? (() => false);

  let ctx = null;
  let rafId = null;
  let lightnings = [];
  let timer = 0;
  let resizeHandler = null;

  class Lightning {
    constructor(canvas) {
      this.canvas = canvas;
      this.reset();
    }
    reset() {
      const canvas = this.canvas;
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
        x += (Math.random() - 0.5) * (70 * density);
        y += (canvas.height / steps) * (0.8 + Math.random() * 0.4);
        this.path.push({ x, y });
      }
    }
    draw() {
      this.life++;
      let alpha = Math.max(0, 1 - this.life / this.maxLife);
      if (Math.random() < 0.25) alpha *= 0.3;
      const dark = isDark();
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

  function resize(canvas) {
    canvas.width = canvas.parentElement.offsetWidth;
    canvas.height = canvas.parentElement.offsetHeight;
  }

  function animate(canvas) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    timer++;
    if (timer % 90 === 0 || Math.random() < 0.01) lightnings.push(new Lightning(canvas));
    for (let i = lightnings.length - 1; i >= 0; i--) {
      lightnings[i].draw();
      if (lightnings[i].life >= lightnings[i].maxLife) lightnings.splice(i, 1);
    }
    rafId = requestAnimationFrame(() => animate(canvas));
  }

  onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    ctx = canvas.getContext("2d");
    resizeHandler = () => resize(canvas);
    window.addEventListener("resize", resizeHandler);
    resize(canvas);
    lightnings = [new Lightning(canvas)];
    animate(canvas);
  });

  onUnmounted(() => {
    if (rafId) cancelAnimationFrame(rafId);
    if (resizeHandler) window.removeEventListener("resize", resizeHandler);
    lightnings = [];
  });
}

export default useLightningCanvas;