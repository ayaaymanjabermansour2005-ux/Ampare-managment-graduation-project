<script setup>
/**
 * Resolves an icon name still stored as a Font Awesome-style string (e.g. `icon: "fa-user"`
 * in a local stat-card/menu/status data array) to the matching Lucide component.
 *
 * This exists only for that dynamic case. When the icon is a literal in the template,
 * import and use the Lucide component directly instead — that stays fully tree-shakable
 * and doesn't need this indirection. This registry itself only imports the ~90 icons
 * actually referenced by such data arrays across the app (see dynamicIconRegistry.js),
 * not the full icon set, so tree-shaking is preserved.
 */
import { computed } from "vue";
import { DYNAMIC_ICON_MAP } from "@/utils/dynamicIconRegistry";

const props = defineProps({
  name: { type: String, required: true },
});

// Some data sources store just the icon ("fa-user"), others store the full class
// string as originally written for an <i> tag ("fa-solid fa-user", sometimes with
// fa-spin etc. mixed in) — normalize to the bare icon token either way.
const STYLE_MODIFIERS = new Set(["fa-solid", "fa-regular", "fa-brands", "fas", "far", "fab", "fa-fw", "fa-spin"]);
function normalizeIconName(raw) {
  if (!raw) return null;
  for (const token of raw.split(/\s+/)) {
    if (token.startsWith("fa-") && !STYLE_MODIFIERS.has(token)) return token;
  }
  return null;
}

// Lucide has no brand/company logos (WhatsApp, Facebook, Instagram, X) — those stay on
// Font Awesome's brand set everywhere in the app, including this dynamic-lookup path.
const BRAND_ICONS = new Set(["fa-whatsapp", "fa-facebook", "fa-facebook-f", "fa-instagram", "fa-x-twitter"]);

const normalizedKey = computed(() => normalizeIconName(props.name));
const isBrand = computed(() => BRAND_ICONS.has(normalizedKey.value));
const resolved = computed(() => (normalizedKey.value ? (DYNAMIC_ICON_MAP[normalizedKey.value] ?? null) : null));

if (import.meta.env.DEV && !resolved.value && !isBrand.value) {
  // eslint-disable-next-line no-console
  console.warn(`[AppIcon] no Lucide mapping for icon name "${props.name}" — add it to dynamicIconRegistry.js`);
}
</script>

<template>
  <component :is="resolved" v-if="resolved" aria-hidden="true" />
  <i v-else-if="isBrand" :class="`fa-brands ${normalizedKey}`" aria-hidden="true"></i>
</template>
