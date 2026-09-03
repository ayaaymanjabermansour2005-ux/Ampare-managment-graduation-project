import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { ref } from 'vue';
import GazaWeatherCard from './GazaWeatherCard.vue';
import AppIcon from '@/components/ui/AppIcon.vue';

// useGazaWeather has its own dedicated spec (useGazaWeather.spec.js) covering the
// fetch/estimate/interval logic — mock it here so this file can focus purely on how
// GazaWeatherCard itself renders the composable's state (loading vs. loaded, isEstimate suffix).
const weather = {
    isLoading: ref(true),
    temp: ref(null),
    conditionLabel: ref('--'),
    conditionIcon: ref('fa-cloud-sun'),
    humidity: ref(null),
    wind: ref(null),
    rain: ref(null),
    peakLabel: ref('--'),
    isEstimate: ref(false),
    loadChipLabel: ref(''),
    loadChipClass: ref('chip-success'),
    loadPercent: ref(0),
    note: ref(''),
};

vi.mock('@/composables/useGazaWeather', () => ({
    useGazaWeather: () => weather,
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            dashboard: {
                weather_title: 'الطقس وتأثيره على الأحمال — غزة',
                expected_peak: 'الذروة المتوقعة',
            },
        },
    },
});

function resetWeather() {
    weather.isLoading.value = true;
    weather.temp.value = null;
    weather.conditionLabel.value = '--';
    weather.conditionIcon.value = 'fa-cloud-sun';
    weather.humidity.value = null;
    weather.wind.value = null;
    weather.rain.value = null;
    weather.peakLabel.value = '--';
    weather.isEstimate.value = false;
    weather.loadChipLabel.value = '';
    weather.loadChipClass.value = 'chip-success';
    weather.loadPercent.value = 0;
    weather.note.value = '';
}

function mountCard() {
    return mount(GazaWeatherCard, { global: { plugins: [i18n] } });
}

describe('GazaWeatherCard', () => {
    it('shows skeleton placeholders and no temperature while loading', () => {
        resetWeather();
        weather.isLoading.value = true;

        const wrapper = mountCard();

        expect(wrapper.findAll('.thumb-loading').length).toBeGreaterThan(0);
        expect(wrapper.find('.text-3xl').exists()).toBe(false);
        expect(wrapper.text()).toContain('الطقس وتأثيره على الأحمال — غزة');
    });

    it('renders the loaded weather data: temperature, condition, metrics and the load chip', () => {
        resetWeather();
        weather.isLoading.value = false;
        weather.temp.value = 25;
        weather.conditionLabel.value = 'صافٍ';
        weather.conditionIcon.value = 'fa-sun';
        weather.humidity.value = 50;
        weather.wind.value = 12;
        weather.rain.value = 10;
        weather.isEstimate.value = false;
        weather.peakLabel.value = '15:00–17:00';
        weather.loadPercent.value = 38;
        weather.loadChipLabel.value = 'حمل طبيعي';
        weather.loadChipClass.value = 'chip-success';
        weather.note.value = 'الأحمال ضمن المعدل الطبيعي حالياً بحسب حالة الطقس.';

        const wrapper = mountCard();

        expect(wrapper.text()).toContain('25°');
        expect(wrapper.text()).toContain('صافٍ');
        expect(wrapper.text()).toContain('15:00–17:00');
        expect(wrapper.text()).toContain('حمل طبيعي');
        expect(wrapper.text()).toContain('الأحمال ضمن المعدل الطبيعي حالياً بحسب حالة الطقس.');
        expect(wrapper.text()).not.toContain('km/h *');
        expect(wrapper.findComponent(AppIcon).props('name')).toBe('fa-sun');
        expect(wrapper.find('.status-chip').classes()).toContain('chip-success');
        expect(wrapper.find('.bar-fill').attributes('style')).toContain('38%');
    });

    it('appends the " *" estimate marker to the metrics line when isEstimate is true', () => {
        resetWeather();
        weather.isLoading.value = false;
        weather.temp.value = 18;
        weather.humidity.value = 64;
        weather.wind.value = 15;
        weather.rain.value = 0;
        weather.isEstimate.value = true;

        const wrapper = mountCard();

        expect(wrapper.text()).toContain('km/h *');
    });

    it('reflects a high-load warning chip class/label when the composable reports one', () => {
        resetWeather();
        weather.isLoading.value = false;
        weather.temp.value = 35;
        weather.loadChipClass.value = 'chip-warning';
        weather.loadChipLabel.value = 'حمل مرتفع متوقع';

        const wrapper = mountCard();

        const chip = wrapper.find('.status-chip');
        expect(chip.classes()).toContain('chip-warning');
        expect(chip.text()).toBe('حمل مرتفع متوقع');
    });
});
