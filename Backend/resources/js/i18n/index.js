import { createI18n } from "vue-i18n";
import ar from "./locales/ar";

// "en" is loaded on demand (see loadLocaleMessages) instead of bundled here too —
// the large majority of users never switch away from the default "ar", so shipping
// both ~4000-line translation files in the initial JS is pure dead weight for them.
const LOCALE_LOADERS = {
  en: () => import("./locales/en"),
};

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
  // Always boot with "ar" messages available synchronously — it's both the
  // default locale for new visitors and the fallbackLocale below, so it can
  // never be the one that's lazy. If the stored preference is "en", the
  // messages are fetched right after and the visible locale flips once ready
  // (see the initial-locale block at the bottom of this file).
  locale: "ar",
  fallbackLocale: "ar",
  messages: { ar },
});

async function loadLocaleMessages(locale) {
  if (locale === "ar" || i18n.global.availableLocales.includes(locale)) return;
  const loader = LOCALE_LOADERS[locale];
  if (!loader) return;
  const messages = await loader();
  i18n.global.setLocaleMessage(locale, messages.default);
}

export function setLocale(locale) {
  const normalized = locale === "en" ? "en" : "ar";
  // Flip the visible locale/direction synchronously, same as before — vue-i18n
  // falls back to "ar" for any key not yet in the target locale's catalog, so
  // switching to "en" before its messages arrive shows (very briefly, for one
  // dynamic-import round trip) "ar" text in an "ltr" shell rather than blocking
  // the toggle itself on the network/parse time of a ~160 KB chunk.
  i18n.global.locale.value = normalized;
  applyDirection(normalized);
  try {
    localStorage.setItem(LOCALE_KEY, normalized);
  } catch {
  }
  loadLocaleMessages(normalized);
}

if (initialLocale === "en") {
  loadLocaleMessages("en").then(() => {
    i18n.global.locale.value = "en";
  });
}

export function getLocale() {
  return i18n.global.locale.value;
}

export default i18n;