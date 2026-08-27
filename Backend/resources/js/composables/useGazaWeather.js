import { ref, onMounted, onUnmounted } from "vue";
import i18n from "@/i18n";

const WEATHER_CODES = {
  0: { key: "gaza_weather.condition_clear", icon: "fa-sun" },
  1: { key: "gaza_weather.condition_mostly_sunny", icon: "fa-sun" },
  2: { key: "gaza_weather.condition_partly_cloudy", icon: "fa-cloud-sun" },
  3: { key: "gaza_weather.condition_cloudy", icon: "fa-cloud" },
  45: { key: "gaza_weather.condition_fog", icon: "fa-smog" },
  48: { key: "gaza_weather.condition_freezing_fog", icon: "fa-smog" },
  51: { key: "gaza_weather.condition_light_drizzle", icon: "fa-cloud-rain" },
  53: { key: "gaza_weather.condition_drizzle", icon: "fa-cloud-rain" },
  55: { key: "gaza_weather.condition_dense_drizzle", icon: "fa-cloud-rain" },
  61: { key: "gaza_weather.condition_light_rain", icon: "fa-cloud-rain" },
  63: { key: "gaza_weather.condition_rain", icon: "fa-cloud-showers-heavy" },
  65: { key: "gaza_weather.condition_heavy_rain", icon: "fa-cloud-showers-heavy" },
  80: { key: "gaza_weather.condition_rain_showers", icon: "fa-cloud-showers-heavy" },
  81: { key: "gaza_weather.condition_moderate_rain_showers", icon: "fa-cloud-showers-heavy" },
  82: { key: "gaza_weather.condition_violent_rain_showers", icon: "fa-cloud-showers-heavy" },
  95: { key: "gaza_weather.condition_thunderstorm", icon: "fa-cloud-bolt" },
};
function codeInfo(code) {
  return WEATHER_CODES[code] || { key: null, icon: "fa-cloud-sun" };
}

function estimateWeather() {
  const monthlyMinMax = [
    [10, 18], [10, 19], [12, 21], [15, 24], [18, 27], [21, 29],
    [23, 31], [24, 31], [23, 30], [20, 28], [16, 24], [12, 19],
  ];
  const now = new Date();
  const [minT, maxT] = monthlyMinMax[now.getMonth()];
  const mid = (minT + maxT) / 2, amp = (maxT - minT) / 2;
  const hour = now.getHours() + now.getMinutes() / 60;
  const temp = Math.round(mid + amp * Math.cos((2 * Math.PI * (hour - 15)) / 24));
  const humidity = Math.max(40, Math.min(80, Math.round(66 - 0.6 * (temp - mid))));
  return {
    temp, humidity, wind: Math.max(5, Math.round(9 + 5 * Math.sin(hour / 6) + 3)),
    rain: 0, condition: codeInfo(0), peakLabel: "15:00–17:00", isEstimate: true,
  };
}

async function fetchOpenMeteo() {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 7000);
  const resp = await fetch(
    "https://api.open-meteo.com/v1/forecast?latitude=31.5017&longitude=34.4668&current=temperature_2m,relative_humidity_2m,wind_speed_10m,precipitation_probability,weather_code&hourly=temperature_2m&timezone=auto",
    { signal: controller.signal },
  );
  clearTimeout(timeoutId);
  if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
  const data = await resp.json();
  if (!data?.current) throw new Error("Unexpected response shape");

  const temp = Math.round(data.current.temperature_2m);
  const humidity = Math.round(data.current.relative_humidity_2m);
  const wind = Math.round(data.current.wind_speed_10m);
  const rain = data.current.precipitation_probability != null ? Math.round(data.current.precipitation_probability) : 0;
  const condition = codeInfo(data.current.weather_code);

  let peakLabel = "--";
  try {
    const today = new Date().toISOString().slice(0, 10);
    const { time: times, temperature_2m: temps } = data.hourly;
    let maxIdx = -1, maxTemp = -Infinity;
    times.forEach((t, i) => {
      if (t.startsWith(today) && temps[i] > maxTemp) { maxTemp = temps[i]; maxIdx = i; }
    });
    if (maxIdx >= 0) {
      const h = parseInt(times[maxIdx].slice(11, 13), 10);
      peakLabel = `${h}:00–${(h + 2) % 24}:00`;
    }
  } catch { /* تجاهل — القيمة الافتراضية "--" كافية */ }

  return { temp, humidity, wind, rain, condition, peakLabel, isEstimate: false };
}

export function useGazaWeather() {
  const isLoading = ref(true);
  const temp = ref(null);
  const conditionLabel = ref("--");
  const conditionIcon = ref("fa-cloud-sun");
  const humidity = ref(null);
  const wind = ref(null);
  const rain = ref(null);
  const peakLabel = ref("--");
  const isEstimate = ref(false);
  const loadChipLabel = ref("");
  const loadChipClass = ref("chip-success");
  const loadPercent = ref(0);
  const note = ref("");

  function apply(w) {
    const t = i18n.global.t;
    temp.value = w.temp;
    conditionLabel.value = w.condition.key ? t(w.condition.key) : "--";
    conditionIcon.value = w.condition.icon;
    humidity.value = w.humidity;
    wind.value = w.wind;
    rain.value = w.rain;
    peakLabel.value = w.peakLabel;
    isEstimate.value = w.isEstimate;

    if (w.temp >= 32) {
      loadPercent.value = 75; loadChipClass.value = "chip-warning";
      loadChipLabel.value = t("gaza_weather.load_high_heat");
      note.value = t("gaza_weather.note_high_heat", { temp: w.temp });
    } else if (w.temp <= 10) {
      loadPercent.value = 68; loadChipClass.value = "chip-warning";
      loadChipLabel.value = t("gaza_weather.load_high_cold");
      note.value = t("gaza_weather.note_high_cold", { temp: w.temp });
    } else {
      loadPercent.value = 38; loadChipClass.value = "chip-success";
      loadChipLabel.value = t("gaza_weather.load_normal");
      note.value = t("gaza_weather.note_normal");
    }
    if (w.isEstimate) note.value += t("gaza_weather.note_estimate_suffix");
    isLoading.value = false;
  }

  async function refresh() {
    try { apply(await fetchOpenMeteo()); return; } catch { /* جرّب المصدر التالي */ }
    apply(estimateWeather());
  }

  let intervalId = null;
  onMounted(() => {
    refresh();
    intervalId = setInterval(refresh, 15 * 60 * 1000);
  });
  onUnmounted(() => clearInterval(intervalId));

  return {
    isLoading, temp, conditionLabel, conditionIcon, humidity, wind, rain,
    peakLabel, isEstimate, loadChipLabel, loadChipClass, loadPercent, note, refresh,
  };
}