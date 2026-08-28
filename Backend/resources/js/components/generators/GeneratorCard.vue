<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import GeneratorStatus from "./GeneratorStatus.vue";
import { Activity, Clock, Info, Pencil, QrCode, Trash2, Users } from "@lucide/vue";

const props = defineProps({
  generator: { type: Object, required: true },
});

// FIX: بطاقات الشبكة (grid view) ما كان فيها زر تشخيص المولد أصلًا، خلافًا
// لعرض الجدول (table view) اللي فيه هذا الزر — فميزة التشخيص كانت مفقودة
// كليًا لمن يستخدم عرض الشبكة.
const emit = defineEmits(["edit", "delete", "show-qr", "view-details", "diagnostics"]);

const { t } = useI18n();

const scheduleLabel = computed(() => {
  const base = t(`owner_generators.schedule.${props.generator.operating_schedule}`, props.generator.operating_schedule);
  if (
    props.generator.operating_schedule === "custom" &&
    props.generator.operating_start_time
  ) {
    return `${base} · ${props.generator.operating_start_time}–${props.generator.operating_end_time}`;
  }
  return base;
});

const locationLabel = computed(() => {
  const loc = props.generator.location;
  if (!loc) return null;
  return loc.neighborhood ? `${loc.city} — ${loc.neighborhood}` : loc.city;
});
</script>

<template>
  <article class="glass-card hoverable group relative">
    <div class="p-5">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div class="min-w-0">
          <h3 class="text-[15px] font-bold truncate">{{ generator.name }}</h3>
          <p v-if="locationLabel" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-0.5">{{ locationLabel }}</p>
        </div>
        <GeneratorStatus :status="generator.status" />
      </div>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="rounded-xl px-3 py-2.5 bg-[#EBF1E7] dark:bg-white/5">
          <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-0.5">{{ t("owner_generators.capacity") }}</p>
          <p class="font-data text-[15px] font-bold text-[#52733D] dark:text-[#8cc35a]">
            {{ generator.capacity_kw ?? "—" }}<span class="text-[11px] font-sans font-normal text-[#9a9d97] dark:text-[#8f938a]"> kW</span>
          </p>
        </div>
        <div class="rounded-xl px-3 py-2.5 bg-[#FBF6E8] dark:bg-white/5">
          <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-0.5">{{ t("owner_generators.price_per_kw") }}</p>
          <p class="font-data text-[15px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5]">
            {{ generator.price_per_kw }}<span class="text-[11px] font-sans font-normal text-[#9a9d97] dark:text-[#8f938a]"> {{ generator.currency }}</span>
          </p>
        </div>
      </div>

      <div class="flex items-center justify-between text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] border-t border-[#eee8da] dark:border-white/10 pt-3">
        <span class="inline-flex items-center gap-1.5">
          <Clock class="text-[#9a9d97] dark:text-[#8f938a]" aria-hidden="true" />
          {{ scheduleLabel }}
        </span>
        <span v-if="generator.active_subscriptions_count !== undefined" class="inline-flex items-center gap-1.5">
          <Users class="text-[#9a9d97] dark:text-[#8f938a]" aria-hidden="true" />
          {{ t("owner_generators.active_subscribers", { count: generator.active_subscriptions_count }) }}
        </span>
      </div>
    </div>

    <div class="flex justify-center pb-4">
      <div class="row-actions">
        <button type="button" class="action-btn action-btn--edit" :title="t('owner_generators.edit')" :aria-label="t('owner_generators.edit')" @click="emit('edit', generator)">
          <Pencil aria-hidden="true" />
        </button>
        <span class="row-actions-divider"></span>
        <button type="button" class="action-btn action-btn--view" :title="t('owner_generators.details')" :aria-label="t('owner_generators.details')" @click="emit('view-details', generator)">
          <Info aria-hidden="true" />
        </button>
        <span class="row-actions-divider"></span>
        <button type="button" class="action-btn action-btn--view !text-[#17A2B8]" :title="t('generator_diagnostics.title')" :aria-label="t('generator_diagnostics.title')" @click="emit('diagnostics', generator)">
          <Activity aria-hidden="true" />
        </button>
        <span class="row-actions-divider"></span>
        <button type="button" class="action-btn action-btn--plan" :title="t('owner_generators.qr_code')" :aria-label="t('owner_generators.qr_code')" @click="emit('show-qr', generator)">
          <QrCode aria-hidden="true" />
        </button>
        <span class="row-actions-divider"></span>
        <button type="button" class="action-btn action-btn--delete" :title="t('owner_generators.delete')" :aria-label="t('owner_generators.delete')" @click="emit('delete', generator)">
          <Trash2 aria-hidden="true" />
        </button>
      </div>
    </div>
  </article>
</template>
