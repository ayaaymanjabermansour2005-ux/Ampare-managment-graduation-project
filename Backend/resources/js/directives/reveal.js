const observedElements = new WeakMap();

export const vReveal = {
  mounted(el, binding) {
    el.classList.add("reveal");

    const delay = typeof binding.value === "number" ? binding.value : 0;
    if (delay > 0) {
      el.style.transitionDelay = `${delay}ms`;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            el.classList.add("in");
            observer.unobserve(el);
          }
        });
      },
      { threshold: 0.1 },
    );
    observer.observe(el);
    observedElements.set(el, observer);
  },
  unmounted(el) {
    observedElements.get(el)?.disconnect();
    observedElements.delete(el);
  },
};

export default vReveal;