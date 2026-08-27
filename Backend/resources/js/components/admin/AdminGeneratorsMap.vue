<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import adminDashboardService from "@/services/adminDashboardService";
import { useAdminUiStore } from "@/stores/adminUi";
import { ArrowLeft, ArrowRight, LoaderCircle, MapPinned } from "@lucide/vue";

const { t } = useI18n();
const router = useRouter();
const ui = useAdminUiStore();

const mapContainer = ref(null);
const isLoading = ref(true);
const points = ref([]);
let map = null;
let markers = [];
let tileLayer = null;

const TILE_URLS = {
  light: "https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png",
  dark: "https://{s}.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}{r}.png",
};

const STATUS_COLORS = {
  active: "#28A745",
  maintenance: "#FFC107",
  inactive: "#D9534F",
  pending_verification: "#17A2B8",
  rejected: "#D9534F",
};

const LEGEND_ITEMS = [
  { status: "active", color: STATUS_COLORS.active },
  { status: "maintenance", color: STATUS_COLORS.maintenance },
  { status: "pending_verification", color: STATUS_COLORS.pending_verification },
  { status: "inactive", color: STATUS_COLORS.inactive },
];

function statusLabel(status) {
  return t(`status.${status}`, status);
}

function coloredIcon(color) {
  return L.divIcon({
    className: "generator-map-marker",
    html: `<div style="width:20px;height:20px;border-radius:50%;background:${color};border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.35);transition:transform .2s ease"></div>`,
    iconSize: [20, 20],
    iconAnchor: [10, 10],
  });
}

async function fetchPoints() {
  isLoading.value = true;
  try {
    const { data } = await adminDashboardService.generatorsMap();
    points.value = data.data;
    renderMarkers();
  } finally {
    isLoading.value = false;
  }
}

function renderMarkers() {
  if (!map) return;

  markers.forEach((m) => m.remove());
  markers = [];

  const bounds = [];

  points.value.forEach((p) => {
    const color = STATUS_COLORS[p.status] ?? "#52733D";
    const marker = L.marker([p.lat, p.lng], { icon: coloredIcon(color) }).addTo(map);

    const viewLinkLabel = t("generators_map.view_generator_link");
    marker.bindPopup(
      `<div class="generator-map-popup" style="font-family:inherit;min-width:160px">
         <strong style="font-size:13px">${p.name}</strong><br/>
         <span class="generator-map-popup-secondary" style="font-size:11.5px">${p.city ?? ""}</span><br/>
         <span style="font-size:11.5px;font-weight:700;color:${color}">${statusLabel(p.status)}</span><br/>
         <a id="view-generator-${p.id}" style="display:inline-flex;align-items:center;gap:4px;margin-top:8px;font-size:11.5px;font-weight:700;color:#8A6D1F;cursor:pointer;text-decoration:underline">
           <span>${viewLinkLabel}</span>
           <ArrowLeft class="rtl:inline ltr:hidden" aria-hidden="true" style="font-size:10px" />
           <ArrowRight class="ltr:inline rtl:hidden" aria-hidden="true" style="font-size:10px" />
         </a>
       </div>`
    );

    marker.on("popupopen", () => {
      document
        .getElementById(`view-generator-${p.id}`)
        ?.addEventListener("click", () => router.push({ name: "generators.show", params: { id: p.id } }));
    });

    markers.push(marker);
    bounds.push([p.lat, p.lng]);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [32, 32], maxZoom: 14 });
  }
}

onMounted(() => {
  map = L.map(mapContainer.value, { attributionControl: false }).setView([31.5017, 34.4668], 11);

  tileLayer = L.tileLayer(TILE_URLS[ui.isDark ? "dark" : "light"], {
    maxZoom: 18,
  }).addTo(map);

  fetchPoints();
});

onUnmounted(() => {
  markers.forEach((m) => m.remove());
  markers = [];
  if (map) {
    map.remove();
    map = null;
  }
});

watch(
  () => ui.isDark,
  (isDark) => {
    if (map && tileLayer) {
      tileLayer.setUrl(TILE_URLS[isDark ? "dark" : "light"]);
    }
    setTimeout(() => map?.invalidateSize(), 350);
  }
);
</script>

<template>
  <div class="relative">
    <div ref="mapContainer" class="map-illustration h-80 w-full rounded-[1rem]"></div>

    <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-black/40 rounded-[1rem]">
      <LoaderCircle class="text-[#8A6D1F] animate-spin" aria-hidden="true" />
    </div>

    <div v-else-if="points.length === 0" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-white/70 dark:bg-[#15171a]/70 rounded-[1rem] backdrop-blur-sm">
      <MapPinned class="text-2xl text-[#c9c4b4]" aria-hidden="true" />
      <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] px-4 text-center">
        {{ t("generators_map.no_coordinates") }}
      </span>
    </div>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-2.5">
      <span v-for="item in LEGEND_ITEMS" :key="item.status" class="flex items-center gap-1.5 text-[10.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5]">
        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: item.color }"></span>
        {{ statusLabel(item.status) }}
      </span>
    </div>
  </div>
</template>
