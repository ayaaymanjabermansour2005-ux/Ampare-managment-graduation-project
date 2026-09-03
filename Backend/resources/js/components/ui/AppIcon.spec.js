import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppIcon from './AppIcon.vue';

describe('AppIcon', () => {
    it('renders the mapped Lucide component for a bare fa- icon name', () => {
        const wrapper = mount(AppIcon, { props: { name: 'fa-user' } });

        // The Lucide <User> SVG carries lucide's own class marker.
        const svg = wrapper.find('svg');
        expect(svg.exists()).toBe(true);
        expect(svg.attributes('aria-hidden')).toBe('true');
        expect(wrapper.find('i').exists()).toBe(false);
    });

    it('normalizes a full class string ("fa-solid fa-user") to the bare token before lookup', () => {
        const wrapper = mount(AppIcon, { props: { name: 'fa-solid fa-user' } });

        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders a different icon for a different mapped name', () => {
        const gear = mount(AppIcon, { props: { name: 'fa-gear' } });
        const robot = mount(AppIcon, { props: { name: 'fa-robot' } });

        expect(gear.find('svg').exists()).toBe(true);
        expect(robot.find('svg').exists()).toBe(true);
        // Different lucide icons render different path data.
        expect(gear.html()).not.toBe(robot.html());
    });

    it('falls back to a Font Awesome brand <i> tag for brand icons not in the Lucide map', () => {
        const wrapper = mount(AppIcon, { props: { name: 'fa-whatsapp' } });

        const icon = wrapper.find('i');
        expect(icon.exists()).toBe(true);
        expect(icon.classes()).toContain('fa-brands');
        expect(icon.classes()).toContain('fa-whatsapp');
        expect(icon.attributes('aria-hidden')).toBe('true');
        expect(wrapper.find('svg').exists()).toBe(false);
    });

    it('renders nothing for an icon name that is neither mapped nor a known brand', () => {
        const wrapper = mount(AppIcon, { props: { name: 'fa-totally-unknown-icon' } });

        expect(wrapper.find('svg').exists()).toBe(false);
        expect(wrapper.find('i').exists()).toBe(false);
        expect(wrapper.html().trim()).toBe('<!--v-if-->');
    });
});
