<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import subscriberMeterService from "@/services/subscriberMeterService";
import { Lock, ZoomOut } from "@lucide/vue";

const route = useRoute();
const { t } = useI18n();
const meterId = route.params.id;

const meter = ref(null);
const isLoading = ref(true);
const errorMessage = ref(null);
const isForbidden = ref(false);
const isNotFound = ref(false);

const STATUS_LABELS = computed(() => ({
  active: t("subscriber_meter_detail_page.status_active"),
  inactive: t("subscriber_meter_detail_page.status_inactive"),
}));

const STATUS_TONES = {
  active: "bg-success-bg text-success",
  inactive: "bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-[#a8aaa5]",
};

async function load() {
  isLoading.value = true;
  errorMessage.value = null;
  isForbidden.value = false;
  isNotFound.value = false;

  try {
    const { data } = await subscriberMeterService.show(meterId);
    meter.value = data.data;
  } catch (err) {
    const status = err.response?.status;

    if (status === 403) {
      isForbidden.value = true;
    } else if (status === 404) {
      isNotFound.value = true;
    } else {
      errorMessage.value =
        err.response?.data?.message ?? t("subscriber_meter_detail_page.load_failed");
    }
  } finally {
    isLoading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="max-w-xl mx-auto">
    <div
      v-if="isLoading"
      class="bg-surface dark:bg-white/[0.04] rounded-card shadow-sm p-8 text-center text-gray-500 dark:text-[#a8aaa5]"
    >
      {{ t("subscriber_meter_detail_page.loading") }}
    </div>

    <div
      v-else-if="isForbidden"
      class="bg-surface dark:bg-white/[0.04] rounded-card shadow-sm p-8 text-center"
    >
      <Lock class="text-[#D9534F] text-3xl mb-3" aria-hidden="true" />
      <h1 class="text-lg font-bold text-gray-700 dark:text-[#eceee8] mb-2">
        {{ t("subscriber_meter_detail_page.forbidden_title") }}
      </h1>
      <p class="text-sm text-gray-500 dark:text-[#a8aaa5]">
        {{ t("subscriber_meter_detail_page.forbidden_message") }}
      </p>
    </div>

    <div
      v-else-if="isNotFound"
      class="bg-surface dark:bg-white/[0.04] rounded-card shadow-sm p-8 text-center"
    >
      <ZoomOut class="text-[#8A6D1F] text-3xl mb-3" aria-hidden="true" />
      <h1 class="text-lg font-bold text-gray-700 dark:text-[#eceee8] mb-2">
        {{ t("subscriber_meter_detail_page.not_found_title") }}
      </h1>
      <p class="text-sm text-gray-500 dark:text-[#a8aaa5]">
        {{ t("subscriber_meter_detail_page.not_found_message") }}
      </p>
    </div>

    <div
      v-else-if="errorMessage"
      class="bg-surface dark:bg-white/[0.04] rounded-card shadow-sm p-8 text-center text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-else-if="meter"
      class="bg-surface dark:bg-white/[0.04] rounded-card shadow-sm p-6 space-y-5"
    >
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-gray-700 dark:text-[#eceee8]">
            {{ t("subscriber_meter_detail_page.meter_number_title", { number: meter.meter_number }) }}
          </h1>
          <p class="text-xs text-gray-400 dark:text-[#7d8079] mt-1">{{ t("subscriber_meter_detail_page.meter_id_label", { id: meter.id }) }}</p>
        </div>
        <span
          class="text-xs rounded-full px-3 py-1 shrink-0"
          :class="STATUS_TONES[meter.status] ?? 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-[#a8aaa5]'"
        >
          {{ STATUS_LABELS[meter.status] ?? meter.status }}
        </span>
      </div>

      <div v-if="meter.property_label" class="text-sm">
        <p class="text-gray-400 dark:text-[#7d8079] text-xs mb-0.5">{{ t("subscriber_meter_detail_page.property_label") }}</p>
        <p class="text-gray-700 dark:text-[#eceee8] font-medium">{{ meter.property_label }}</p>
      </div>

      <div
        v-if="meter.subscriptions_count !== undefined"
        class="border-t border-border dark:border-white/10 pt-4 text-sm"
      >
        <p class="text-gray-400 dark:text-[#7d8079] text-xs mb-0.5">{{ t("subscriber_meter_detail_page.subscriptions_count_label") }}</p>
        <p class="text-gray-700 dark:text-[#eceee8] font-medium">{{ meter.subscriptions_count }}</p>
      </div>
    </div>
  </div>
</template>