import { onMounted, onUnmounted } from "vue";

export function useParallax(layers) {
  let ticking = false;
  let sectionEl = null;

  function update() {
    ticking = false;
    if (!sectionEl) return;

    const rect = sectionEl.getBoundingClientRect();
    if (rect.bottom < 0 || rect.top > window.innerHeight) return;

    const scrollProgress = -rect.top; 

    layers.forEach(({ el, speed }) => {
      if (el.value) {
        el.value.style.transform = `translate3d(0, ${scrollProgress * speed}px, 0)`;
      }
    });
  }

  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(update);
      ticking = true;
    }
  }

  onMounted(() => {
    sectionEl = layers[0]?.el.value?.closest("section") ?? layers[0]?.el.value?.parentElement;
    window.addEventListener("scroll", onScroll, { passive: true });
    update();
  });

  onUnmounted(() => {
    window.removeEventListener("scroll", onScroll);
  });
}