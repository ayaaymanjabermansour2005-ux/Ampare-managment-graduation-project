import { ref, computed, onMounted, onBeforeUnmount } from "vue";

/**
 * منطق Stacked Card Carousel قابل لإعادة الاستخدام بأي قسم بالموقع.
 * @param {number} count - عدد الكروت
 * @param {object} options - { rtl: Ref<boolean>, autoplayMs?: number, maxDepth?: number }
 */
export function useCardStack(count, { rtl, autoplayMs = 4200, maxDepth = 2 } = {}) {
  const activeIndex = ref(0);
  const isDragging = ref(false);
  const dragDeltaX = ref(0);
  const isPaused = ref(false);
  const isInView = ref(true);

  const prefersReducedMotion =
    typeof window !== "undefined" && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  let startX = 0;
  let moved = false;
  let autoplayTimer = null;
  let sectionEl = null;
  let observer = null;

  function wrappedOffset(i) {
    let offset = i - activeIndex.value;
    if (offset > count / 2) offset -= count;
    if (offset < -count / 2) offset += count;
    return offset;
  }

  function cardStyle(i) {
    const dir = rtl?.value ? -1 : 1;
    let offset = wrappedOffset(i);

    // الكارد الفعّال أثناء السحب يتحرك مع الإصبع مباشرة
    const dragOffset = offset === 0 && isDragging.value ? dragDeltaX.value : 0;

    const absOffset = Math.abs(offset);
    const visible = absOffset <= maxDepth;

    const translateX = offset * dir * 34 + dragOffset; // % من عرض الكارد
    const scale = Math.max(1 - absOffset * 0.1, 0.78);
    const opacity = visible ? Math.max(1 - absOffset * 0.32, 0) : 0;
    const zIndex = count - absOffset;
    const rotate = offset * dir * -3;

    return {
      transform: `translateX(${translateX}%) scale(${scale}) rotate(${rotate}deg)`,
      opacity,
      zIndex,
      pointerEvents: visible ? "auto" : "none",
      transition: isDragging.value
        ? "none"
        : "transform .55s cubic-bezier(.22,.85,.3,1), opacity .45s ease",
    };
  }

  function next() {
    activeIndex.value = (activeIndex.value + 1) % count;
  }
  function prev() {
    activeIndex.value = (activeIndex.value - 1 + count) % count;
  }
  function goTo(i) {
    activeIndex.value = ((i % count) + count) % count;
  }

  function onPointerDown(e) {
    isDragging.value = true;
    moved = false;
    startX = e.clientX;
    stopAutoplay();
  }
  function onPointerMove(e) {
    if (!isDragging.value) return;
    const delta = e.clientX - startX;
    if (Math.abs(delta) > 4) moved = true;
    dragDeltaX.value = (delta / (e.target.closest(".stack-track")?.offsetWidth || 300)) * 100;
  }
  function onPointerUp() {
    if (!isDragging.value) return;
    isDragging.value = false;
    const threshold = 12;
    const dir = rtl?.value ? -1 : 1;
    if (dragDeltaX.value * dir < -threshold) next();
    else if (dragDeltaX.value * dir > threshold) prev();
    dragDeltaX.value = 0;
    startAutoplay();
  }

  function startAutoplay() {
    if (prefersReducedMotion) return;
    stopAutoplay();
    autoplayTimer = setInterval(() => {
      if (!isPaused.value && isInView.value) next();
    }, autoplayMs);
  }
  function stopAutoplay() {
    if (autoplayTimer) clearInterval(autoplayTimer);
  }

  function observeVisibility(el) {
    sectionEl = el;
    observer = new IntersectionObserver(([entry]) => { isInView.value = entry.isIntersecting; }, { threshold: 0.2 });
    observer.observe(sectionEl);
  }

  onMounted(startAutoplay);
  onBeforeUnmount(() => { stopAutoplay(); observer?.disconnect(); });

  return {
    activeIndex, isDragging, cardStyle,
    next, prev, goTo,
    onPointerDown, onPointerMove, onPointerUp,
    isPaused, observeVisibility,
    wasClick: () => !moved,
  };
}