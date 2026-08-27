/**
 * normalizeApiError — نقطة مركزية واحدة لتفسير أخطاء axios القادمة من الـ
 * API، بدل تكرار `err.response?.data?.message` يدويًا بكل composable/View.
 *
 * يدعم كل الأشكال الفعلية التي يرجعها bootstrap/app.php (withExceptions):
 * 422 (Validation, فيها `errors` لكل حقل)، 401/403/404/429/500 (رسالة عامة
 * فقط)، وأخطاء الشبكة (لا يوجد `error.response` إطلاقًا — مثلاً انقطاع
 * الإنترنت). حالة 419 (CSRF منتهي) تُعالَج تلقائيًا بـ `http.js` نفسه
 * (إعادة محاولة واحدة) قبل ما توصل لهذا المستوى أصلًا.
 *
 * @param {unknown} error - الخطأ الملتقَط من catch(err) بعد طلب axios.
 * @param {string} [fallbackMessage] - رسالة احتياطية لو الباك ما أرجع message (مثلاً بخطأ شبكة).
 * @returns {{
 *   message: string,
 *   fieldErrors: Record<string, string[]>,
 *   status: number|null,
 *   isNetworkError: boolean,
 *   fieldError: (field: string) => string|null,
 * }}
 */
export function normalizeApiError(error, fallbackMessage = "حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.") {
  const response = error?.response;
  const status = response?.status ?? null;
  const isNetworkError = !response;
  const fieldErrors = response?.data?.errors ?? {};

  return {
    message: response?.data?.message ?? fallbackMessage,
    fieldErrors,
    status,
    isNetworkError,
    fieldError(field) {
      return fieldErrors?.[field]?.[0] ?? null;
    },
  };
}
