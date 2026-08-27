<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { Info } from "@lucide/vue";

const { t } = useI18n();

const props = defineProps({
  modelValue: { type: Object, default: null },
});
const emit = defineEmits(["update:modelValue"]);

const mapContainer = ref(null);
let map = null;
let marker = null;

const DEFAULT_CENTER = [31.5, 34.47];

const generatorIcon = L.divIcon({
  className: "",
  html: `<div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#3E582E,#8A6D1F);border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>`,
  iconSize: [28, 28],
  iconAnchor: [14, 14],
});

function placeMarker(lat, lng) {
  const latLng = [lat, lng];

  if (marker) {
    marker.setLatLng(latLng);
  } else {
    marker = L.marker(latLng, { icon: generatorIcon, draggable: true }).addTo(map);
    marker.on("dragend", () => {
      const pos = marker.getLatLng();
      emit("update:modelValue", { latitude: pos.lat, longitude: pos.lng });
    });
  }
  emit("update:modelValue", { latitude: lat, longitude: lng });
}

onMounted(() => {
  const initialCenter = props.modelValue
    ? [props.modelValue.latitude, props.modelValue.longitude]
    : DEFAULT_CENTER;

  map = L.map(mapContainer.value, { attributionControl: false }).setView(initialCenter, 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
  }).addTo(map);

  if (props.modelValue) {
    placeMarker(props.modelValue.latitude, props.modelValue.longitude);
  }

  map.on("click", (e) => {
    placeMarker(e.latlng.lat, e.latlng.lng);
  });
});

onUnmounted(() => {
  if (marker) {
    marker.remove();
    marker = null;
  }
  if (map) {
    map.remove();
    map = null;
  }
});

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal && map && (!marker || marker.getLatLng().lat !== newVal.latitude)) {
      map.setView([newVal.latitude, newVal.longitude]);
    }
  }
);
</script>

<template>
  <div class="space-y-2">
    <div ref="mapContainer" class="w-full h-64 rounded-lg border border-border dark:border-white/10 z-0"></div>
    <p class="text-xs text-gray-500 dark:text-gray-400">
      <Info class="me-1" aria-hidden="true" />
      {{ t("generator_form_modal.map_hint") }}
    </p>
    <p v-if="modelValue" class="text-xs text-gray-400 dark:text-gray-500 font-mono" dir="ltr">
      {{ modelValue.latitude.toFixed(5) }},
      {{ modelValue.longitude.toFixed(5) }}
    </p>
  </div>
</template>