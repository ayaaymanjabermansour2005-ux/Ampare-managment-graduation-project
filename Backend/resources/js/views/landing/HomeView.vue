<script setup>
import { defineAsyncComponent } from "vue";
import HeroSection from "@/views/landing/HeroSection.vue";
import StatsBar from "@/components/landing/StatsBar.vue";
import AboutSection from "@/components/landing/AboutSection.vue";
import WhyUsSection from "@/components/landing/WhyUsSection.vue";
import FeatureGridSection from "@/components/landing/FeatureGridSection.vue";
import AnalyticsSection from "@/components/landing/AnalyticsSection.vue";
import RolesSection from "@/components/landing/RolesSection.vue";
import ServicesSection from "@/components/landing/ServicesSection.vue";
import HumanitarianSection from "@/components/landing/HumanitarianSection.vue";
import TestimonialsSection from "@/components/landing/TestimonialsSection.vue";
import BlogTeaserSection from "@/components/landing/BlogTeaserSection.vue";
import FaqSection from "@/components/landing/FaqSection.vue";
import ContactSection from "@/components/landing/ContactSection.vue";
import CtaSection from "@/components/landing/CtaSection.vue";

// Every other landing section above is plain markup/CSS. This one alone drags in
// Leaflet (a large mapping library) — loading it eagerly meant every anonymous
// visitor to the public landing page paid for it up front, even though the map
// sits 8 sections below the fold. Deferred so it loads once the browser is idle
// after the rest of the page has painted, instead of blocking initial JS parse.
const GeneratorsMapSection = defineAsyncComponent(() =>
  import("@/components/landing/GeneratorsMapSection.vue"),
);
</script>

<template>
  <div>
    <HeroSection />
    <StatsBar />
    <AboutSection />
    <WhyUsSection />
    <FeatureGridSection />
    <AnalyticsSection />
    <RolesSection />
    <ServicesSection />
    <GeneratorsMapSection />
    <HumanitarianSection />
    <TestimonialsSection />
    <BlogTeaserSection />
    <FaqSection />
    <ContactSection />
    <CtaSection />
  </div>
</template>