const state = new WeakMap();

function currentLocale(el) {
  const dirHost = el.closest("[dir]");
  const dir = dirHost?.dir || document.documentElement.dir || "rtl";
  return dir === "rtl" ? "ar-EG" : "en-US";
}

function animate(el, target, decimals) {
  const numericTarget = Number(target) || 0;
  let current = 0;
  const step = Math.max(numericTarget / 40, numericTarget < 1 ? 0.01 : 1);

  cancelAnimationFrame(state.get(el)?.frame);

  function format(value) {
    return decimals > 0
      ? value.toFixed(decimals)
      : Math.floor(value).toLocaleString(currentLocale(el));
  }

  function tick() {
    current += step;
    if (current >= numericTarget) {
      el.textContent = decimals > 0 ? numericTarget.toFixed(decimals) : numericTarget.toLocaleString(currentLocale(el));
      state.set(el, { ...state.get(el), done: true });
      return;
    }
    el.textContent = format(current);
    const frame = requestAnimationFrame(tick);
    state.set(el, { ...state.get(el), frame });
  }
  tick();
}

function resolveBinding(value) {
  if (value && typeof value === "object" && "value" in value) {
    return { target: value.value, decimals: value.decimals ?? 0 };
  }
  return { target: value, decimals: 0 };
}

export const vCountUp = {
  mounted(el, binding) {
    el.textContent = "0";
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const { target, decimals } = resolveBinding(binding.value);
            animate(el, target, decimals);
            observer.disconnect();
          }
        });
      },
      { threshold: 0.4 },
    );
    observer.observe(el);
    state.set(el, { observer, done: false });
  },
  updated(el, binding) {
    if (state.get(el)?.done) {
      const { target, decimals } = resolveBinding(binding.value);
      el.textContent = decimals > 0 ? Number(target || 0).toFixed(decimals) : Number(target || 0).toLocaleString(currentLocale(el));
    }
  },
  unmounted(el) {
    state.get(el)?.observer?.disconnect();
    cancelAnimationFrame(state.get(el)?.frame);
    state.delete(el);
  },
};

export default vCountUp;