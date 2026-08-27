import { ref, onMounted, onBeforeUnmount } from "vue";

export function useScrollGlow() {
  const stageEl = ref(null);
  let rafId = null;
  let observer = null;
  let listening = false;

  const prefersReducedMotion =
    typeof window !== "undefined" && window.matchMedia
      ? window.matchMedia("(prefers-reduced-motion: reduce)").matches
      : false;

  function update() {
    rafId = null;
    const el = stageEl.value;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const vh = window.innerHeight || document.documentElement.clientHeight;
    const total = rect.height + vh;
    let progress = total > 0 ? (vh - rect.top) / total : 0;
    progress = Math.max(0, Math.min(1, progress));
    el.style.setProperty("--scroll-glow", progress.toFixed(4));
  }

  function onScrollTick() {
    if (rafId != null) return;
    rafId = requestAnimationFrame(update);
  }

  function start() {
    if (listening || prefersReducedMotion) return;
    listening = true;
    window.addEventListener("scroll", onScrollTick, { passive: true });
    window.addEventListener("resize", onScrollTick, { passive: true });
    update();
  }

  function stop() {
    if (!listening) return;
    listening = false;
    window.removeEventListener("scroll", onScrollTick);
    window.removeEventListener("resize", onScrollTick);
    if (rafId != null) cancelAnimationFrame(rafId);
    rafId = null;
  }

  onMounted(() => {
    if (stageEl.value && "IntersectionObserver" in window && !prefersReducedMotion) {
      observer = new IntersectionObserver(
        (entries) => entries.forEach((e) => (e.isIntersecting ? start() : stop())),
        { threshold: 0 }
      );
      observer.observe(stageEl.value);
    }
  });

  onBeforeUnmount(() => {
    stop();
    if (observer) observer.disconnect();
  });

  return stageEl;
}