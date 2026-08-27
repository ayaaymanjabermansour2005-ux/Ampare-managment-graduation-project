import { useRouter, useRoute } from "vue-router";
import { nextTick } from "vue";

export function useScrollToSection() {
  const router = useRouter();
  const route = useRoute();

  async function scrollToSection(sectionId) {
    if (route.name === "landing.home") {
      scrollNow(sectionId);
      return;
    }

    await router.push({ name: "landing.home" });
    await nextTick();
    // مهلة صغيرة إضافية لضمان تحميل مكونات الأقسام (Lazy/async) قبل القياس
    setTimeout(() => scrollNow(sectionId), 150);
  }

  function scrollNow(sectionId) {
    const el = document.getElementById(sectionId);
    if (el) {
      el.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }

  return { scrollToSection };
}