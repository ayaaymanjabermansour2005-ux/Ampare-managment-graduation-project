import { defineStore } from "pinia";
import { ref } from "vue";
import settingService from "@/services/settingService";

/**
 * Public, unauthenticated platform branding (site name/logo) for guest-facing
 * pages (login/register/landing) — fetched once and shared across every
 * consumer, since it never changes within a single page load.
 */
export const usePlatformIdentityStore = defineStore("platformIdentity", () => {
  const siteName = ref(null);
  const logoUrl = ref(null);
  const faviconUrl = ref(null);
  const isLoaded = ref(false);

  let inFlight = null;

  function fetch() {
    if (isLoaded.value) return Promise.resolve();
    if (inFlight) return inFlight;

    inFlight = settingService
      .publicIdentity()
      .then(({ data }) => {
        const identity = data.data ?? {};
        siteName.value = identity.site_name || null;
        logoUrl.value = identity.logo_url || null;
        faviconUrl.value = identity.favicon_url || null;
        isLoaded.value = true;
      })
      .catch(() => {
        // Guest pages must still render with their static fallbacks if this
        // fails (e.g. offline) — never block login/landing on this request.
      })
      .finally(() => {
        inFlight = null;
      });

    return inFlight;
  }

  return { siteName, logoUrl, faviconUrl, isLoaded, fetch };
});
