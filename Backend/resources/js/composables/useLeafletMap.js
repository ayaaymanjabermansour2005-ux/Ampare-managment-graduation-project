import { ref, onBeforeUnmount } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

/**
 *
 * @param {Object}   options
 * @param {[number,number]} options.center       مركز الخريطة الابتدائي [lat, lng]
 * @param {number}   options.zoom                مستوى التكبير الابتدائي
 * @param {string}   options.tileUrl             رابط الـ tile layer
 * @param {Object}   options.tileOptions         خيارات إضافية للـ tile layer (maxZoom, minZoom...)
 * @param {Object}   options.mapOptions          خيارات إضافية لـ L.map (scrollWheelZoom, attributionControl...)
 */
export function useLeafletMap({
  center = [31.5017, 34.4668],
  zoom = 11,
  tileUrl = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
  tileOptions = {},
  mapOptions = {},
} = {}) {
  const containerRef = ref(null);

  let map = null;
  let markersById = {};

  function init() {
    if (map || !containerRef.value) return map;

    map = L.map(containerRef.value, {
      attributionControl: false,
      ...mapOptions,
    }).setView(center, zoom);

    L.tileLayer(tileUrl, { maxZoom: 18, ...tileOptions }).addTo(map);

    return map;
  }

  function clearMarkers() {
    Object.values(markersById).forEach((m) => m.remove());
    markersById = {};
  }

  /**
   * بناء ماركرز من مصفوفة عناصر.
   * @param {Array} items
   * @param {Object} config
   * @param {(item:any)=>string|number} config.getId      استخراج id فريد للعنصر
   * @param {(item:any)=>[number,number]} config.getLatLng استخراج [lat,lng]
   * @param {(item:any)=>L.DivIcon} [config.icon]           بناء أيقونة مخصصة (اختياري)
   * @param {(item:any)=>string} [config.popup]             بناء محتوى الـ popup (اختياري)
   * @param {(item:any, marker:L.Marker)=>void} [config.onClick] هاندلر عند الضغط على الماركر
   * @returns {Array<[number,number]>} bounds كل النقاط المرسومة (لاستخدامها مع fitToBounds)
   */
  function setMarkers(items = [], { getId = (i) => i.id, getLatLng, icon, popup, onClick } = {}) {
    if (!map) return [];
    clearMarkers();

    const bounds = [];
    items.forEach((item) => {
      const latLng = getLatLng(item);
      if (!latLng || latLng[0] == null || latLng[1] == null) return;

      const marker = L.marker(latLng, icon ? { icon: icon(item) } : undefined).addTo(map);
      if (popup) marker.bindPopup(popup(item));
      if (onClick) marker.on("click", () => onClick(item, marker));

      markersById[getId(item)] = marker;
      bounds.push(latLng);
    });

    return bounds;
  }

  function fitToBounds(bounds, options = { padding: [32, 32] }) {
    if (!map || !bounds?.length) return;
    if (bounds.length === 1) {
      map.setView(bounds[0], Math.max(zoom, 14));
    } else {
      map.fitBounds(bounds, options);
    }
  }

  function focus(lat, lng, focusZoom = 14) {
    map?.setView([lat, lng], focusZoom, { animate: true });
  }

  function openPopup(id) {
    markersById[id]?.openPopup();
  }

  function getMarker(id) {
    return markersById[id] ?? null;
  }

  function invalidateSize(delay = 0) {
    if (delay) {
      setTimeout(() => map?.invalidateSize(), delay);
    } else {
      map?.invalidateSize();
    }
  }

  function destroy() {
    clearMarkers();
    if (map) {
      map.remove();
      map = null;
    }
  }

  onBeforeUnmount(destroy);

  return {
    containerRef,
    init,
    setMarkers,
    clearMarkers,
    fitToBounds,
    focus,
    openPopup,
    getMarker,
    invalidateSize,
    destroy,
    getMap: () => map,
  };
}