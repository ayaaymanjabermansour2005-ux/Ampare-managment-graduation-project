import { createI18n } from "vue-i18n";
import ar from "./locales/ar";
import en from "./locales/en";

const LOCALE_KEY = "ampere-locale";

function readStoredLocale() {
  try {
    const stored = localStorage.getItem(LOCALE_KEY);
    return stored === "en" ? "en" : "ar";
  } catch {
    return "ar";
  }
}

function applyDirection(locale) {
  document.documentElement.dir = locale === "en" ? "ltr" : "rtl";
  document.documentElement.lang = locale;
}

const initialLocale = readStoredLocale();
applyDirection(initialLocale);

const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: "ar",
  messages: { ar, en },
});

export function setLocale(locale) {
  const normalized = locale === "en" ? "en" : "ar";
  i18n.global.locale.value = normalized;
  applyDirection(normalized);
  try {
    localStorage.setItem(LOCALE_KEY, normalized);
  } catch {
  }
}

export function getLocale() {
  return i18n.global.locale.value;
}

export default i18n;