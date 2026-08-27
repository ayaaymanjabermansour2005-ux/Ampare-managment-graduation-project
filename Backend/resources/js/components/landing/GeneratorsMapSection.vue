<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import L from "leaflet";
import { vReveal } from "@/directives/reveal";
import publicGeneratorsListService from "@/services/publicGeneratorsListService";
import { useLeafletMap } from "@/composables/useLeafletMap";
import { useAuthStore } from "@/stores/auth";
import { Info, LoaderCircle, MapPin, MapPinned, Search, Users, Zap } from "@lucide/vue";

const { t, n, locale } = useI18n();
const router = useRouter();
const authStore = useAuthStore();

/* ---------------- رابط "اشتراك" لكل مولد ----------------
 * مسجّل دخول كمشترك → مباشرة لمركز الاشتراكات (?highlight=ID، الآلية
 * الموجودة أصلًا بـ SubscriptionCenterView/SubscriptionBrowsePanel).
 * غير مسجّل دخول (الحالة الأشيع من اللاندنج بيج) → تسجيل مشترك جديد،
 * مع تمرير generator_id ليُحفظ ويُستهلَك تلقائيًا بعد أول تسجيل دخول
 * (راجع RegisterView.vue وLoginView.vue).
 */
function subscribeRouteFor(g) {
  if (authStore.isAuthenticated && authStore.hasRole("subscriber")) {
    return { name: "subscriber.subscription", query: { highlight: g.id } };
  }
  return { name: "register.subscriber", query: { generator_id: g.id } };
}

function subscribeHrefFor(g) {
  return router.resolve(subscribeRouteFor(g)).href;
}

function formatNum(value) {
  return Number(value || 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}
function localizedGeneratorName(g) {
  return locale.value === "en" && g.name_en ? g.name_en : g.name;
}

const status = ref("loading"); // loading | ready | empty | error
const generators = ref([]);
const totalCities = ref(0);
const activeId = ref(null);
const searchQuery = ref("");

const GAZA_CENTER = [31.45, 34.38];

const filteredGenerators = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  const list = !q
    ? generators.value
    : generators.value.filter((g) => g.name.toLowerCase().includes(q) || (g.name_en ?? "").toLowerCase().includes(q) || g.city.toLowerCase().includes(q));
  return [...list].sort((a, b) => b.subscribers_count - a.subscribers_count);
});

function generatorIcon(g, isActive) {
  const size = isActive ? 40 : 34;
  return L.divIcon({
    className: "",
    html: `
      <div style="position:relative;width:${size}px;height:${size}px;transform:${isActive ? "scale(1.15)" : "scale(1)"};transition:transform .25s ease">
        <svg width="${size}" height="${size}" viewBox="0 0 40 40">
          <path d="M20 2C11 2 4 9 4 18c0 12 16 20 16 20s16-8 16-20C36 9 29 2 20 2Z"
                fill="${isActive ? "#8A6D1F" : "#52733D"}" stroke="white" stroke-width="2"/>
        </svg>
        <div style="position:absolute;top:6px;left:0;right:0;text-align:center;color:#fff;font-size:11px;font-weight:800">
          <Zap aria-hidden="true" />
        </div>
      </div>`,
    iconSize: [size, size],
    iconAnchor: [size / 2, size],
    popupAnchor: [0, -size],
  });
}

function popupHtml(g) {
  return `
    <div style="font-family:inherit;min-width:170px;text-align:center;padding:2px 4px">
      <strong style="font-size:13px">${localizedGeneratorName(g)}</strong><br/>
      <span style="font-size:11px;color:#6B6B6B">${g.city}</span><br/>
      <span style="font-size:11.5px;font-weight:700;color:#52733D">${g.subscribers_count} ${t("landing.map.subscribers_unit")}</span><br/>
      <span style="font-size:12px;font-weight:800;color:#8A6D1F">₪${g.price_per_kw} / ${t("landing.map.kwh_unit")}</span><br/>
      <a href="${subscribeHrefFor(g)}" style="display:inline-block;margin-top:8px;padding:6px 14px;border-radius:999px;background:linear-gradient(to left,#3E582E,#52733D,#8A6D1F);color:#fff;font-size:11.5px;font-weight:700;text-decoration:none">
        ${t("landing.map.subscribe_button")}
      </a>
    </div>`;
}

const { containerRef: mapContainer, init, setMarkers, fitToBounds, focus, openPopup } = useLeafletMap({
  center: GAZA_CENTER,
  zoom: 10,
  tileUrl: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
  tileOptions: { maxZoom: 17, minZoom: 9 },
  mapOptions: { scrollWheelZoom: false },
});

async function loadGenerators() {
  status.value = "loading";
  try {
    const { data } = await publicGeneratorsListService.list();
    generators.value = data.generators ?? [];
    totalCities.value = data.total_cities ?? 0;

    if (!generators.value.length) {
      status.value = "empty";
      return;
    }
    status.value = "ready";

    await nextTick();

    init();
    const bounds = setMarkers(generators.value, {
      getId: (g) => g.id,
      getLatLng: (g) => [g.latitude, g.longitude],
      icon: (g) => generatorIcon(g, false),
      popup: (g) => popupHtml(g),
      onClick: (g) => (activeId.value = g.id),
    });
    fitToBounds(bounds, { padding: [40, 40] });
  } catch {
    status.value = "error";
  }
}

function focusGenerator(g) {
  activeId.value = g.id;
  focus(g.latitude, g.longitude, 14);
  openPopup(g.id);
}

onMounted(loadGenerators);
</script>

<template>
  <section id="map" class="py-14 sm:py-24 grid-texture">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-2xl mx-auto mb-4" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.map.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.map.title") }}</h2>
        <p class="text-[12.5px] sm:text-[13px] text-[#666] dark:text-[#aeb1ab] mt-2 sm:mt-3 max-w-lg mx-auto">{{ t("landing.map.subtitle") }}</p>

        <div v-if="status === 'ready'" class="flex items-center justify-center gap-4 sm:gap-6 mt-4 sm:mt-5">
          <div class="text-center">
            <p class="text-lg sm:text-xl font-extrabold brand-gradient-text">{{ formatNum(generators.length) }}</p>
            <p class="text-[10px] sm:text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("landing.map.stat_generators") }}</p>
          </div>
          <div class="w-px h-7 sm:h-8 bg-[#e7e2d6] dark:bg-white/10"></div>
          <div class="text-center">
            <p class="text-lg sm:text-xl font-extrabold brand-gradient-text">{{ formatNum(totalCities) }}</p>
            <p class="text-[10px] sm:text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("landing.map.stat_cities") }}</p>
          </div>
        </div>
      </div>

      <!-- شريط بحث -->
      <div v-if="status === 'ready'" class="max-w-sm mx-auto mt-6 sm:mt-8 mb-5 sm:mb-6 relative px-2 sm:px-0" v-reveal>
        <Search class="absolute top-1/2 -translate-y-1/2 start-6 sm:start-4 text-[#9a9d97] text-[12px]" aria-hidden="true" />
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="t('landing.map.search_placeholder')"
          class="w-full ps-10 pe-4 py-2.5 rounded-full bg-white/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 text-[16px] sm:text-[12.5px] outline-none focus:border-[#D4AF37] transition-colors"
        />
      </div>

      <div v-if="status === 'loading'" class="glass-card h-[300px] sm:h-[400px] flex items-center justify-center gap-2 text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
        <LoaderCircle class="animate-spin" aria-hidden="true" /> {{ t("landing.map.loading") }}
      </div>
      <div v-else-if="status === 'error' || status === 'empty'" class="glass-card h-[300px] sm:h-[400px] flex flex-col items-center justify-center gap-2 text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
        <MapPinned class="text-2xl opacity-40" aria-hidden="true" />
        <span>{{ status === "error" ? t("landing.map.error") : t("landing.map.empty") }}</span>
      </div>

      <div v-show="status === 'ready'" class="grid lg:grid-cols-5 gap-4 sm:gap-5">
        <div class="lg:col-span-3 glass-card p-2 sm:p-3 overflow-hidden order-1" v-reveal>
          <div ref="mapContainer" class="w-full h-[280px] sm:h-[360px] lg:h-[520px] rounded-xl z-0"></div>
        </div>

        <div class="lg:col-span-2 space-y-3 max-h-[420px] sm:max-h-[520px] overflow-y-auto ps-1 order-2" v-reveal>
          <p v-if="filteredGenerators.length === 0" class="text-center text-[12px] text-[#9a9d97] py-8">
            {{ t("landing.map.no_results") }}
          </p>
          <div
            v-for="g in filteredGenerators"
            :key="g.id"
            class="gen-card glass-card p-3.5 sm:p-4 cursor-pointer transition-all duration-300"
            :class="activeId === g.id ? '!border-[#D4AF37] shadow-lg' : ''"
            @click="focusGenerator(g)"
          >
            <div class="flex items-center justify-between mb-1.5 gap-2">
              <h4 class="font-bold text-[13px] sm:text-[13.5px] truncate">{{ localizedGeneratorName(g) }}</h4>
              <span class="shrink-0 text-[9.5px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#10B981]/12 text-[#10B981]">
                {{ t("landing.map.status_active") }}
              </span>
            </div>
            <p class="text-[11px] sm:text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] flex items-center gap-1">
              <MapPin class="text-[10px]" aria-hidden="true" /> {{ g.city }}
            </p>
            <div class="flex items-center justify-between mt-2 text-[11.5px] sm:text-[12px] flex-wrap gap-1.5">
              <span class="font-semibold text-[#52733D] dark:text-[#8cc35a] flex items-center gap-1">
                <Users class="text-[10px]" aria-hidden="true" /> {{ g.subscribers_count }} {{ t("landing.map.subscribers_unit") }}
              </span>
              <span class="font-bold text-[#8A6D1F]">₪{{ g.price_per_kw }} / {{ t("landing.map.kwh_unit") }}</span>
            </div>
            <RouterLink
              :to="subscribeRouteFor(g)"
              class="btn-fill mt-3 w-full flex items-center justify-center gap-1.5 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12px] font-bold py-2.5 sm:py-2 rounded-full min-h-[40px]"
              @click.stop
            >
              <Zap aria-hidden="true" /> {{ t("landing.map.subscribe_button") }}
            </RouterLink>
          </div>
        </div>
      </div>

      <div class="glass-card p-4 sm:p-6 mt-5 sm:mt-6 flex items-start gap-3" v-reveal>
        <div class="w-8 h-8 sm:w-9 sm:h-9 shrink-0 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F]">
          <Info aria-hidden="true" />
        </div>
        <p class="text-[12px] sm:text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ t("landing.map.disclaimer") }}</p>
      </div>
    </div>
  </section>
</template>