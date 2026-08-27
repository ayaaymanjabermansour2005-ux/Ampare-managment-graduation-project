/**
 * مفتاح تخزين محلي مشترك بين RegisterView.vue وLoginView.vue: يحفظ
 * generator_id القادم من رابط "اشتراك" بخريطة اللاندنج بيج أثناء التسجيل،
 * ليُستهلَك تلقائيًا فور نجاح أول تسجيل دخول (بدل ما يختار المشترك
 * المولد مرة ثانية يدويًا).
 */
export const PENDING_SUBSCRIBE_GENERATOR_KEY = "ampere-pending-subscribe-generator-id";

export function consumePendingSubscribeGeneratorId() {
  try {
    const value = localStorage.getItem(PENDING_SUBSCRIBE_GENERATOR_KEY);
    if (value) {
      localStorage.removeItem(PENDING_SUBSCRIBE_GENERATOR_KEY);
    }
    return value;
  } catch {
    return null;
  }
}
