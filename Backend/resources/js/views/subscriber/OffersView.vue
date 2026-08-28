<script setup>
import { onMounted, ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useSubscriberOffers } from "@/composables/useSubscriberOffers";
import { vReveal } from "@/directives/reveal";
import offerService from "@/services/offerService";
import { CalendarDays, ChevronLeft, Eye, Factory, LoaderCircle, Tags, TriangleAlert, X } from "@lucide/vue";

const { t } = useI18n();
const { offers, isLoading, error, fetchOffers } = useSubscriberOffers();

const STATUS_LABELS = computed(() => ({
  active: t("offers_page.status_active"),
  upcoming: t("offers_page.status_upcoming"),
  expired: t("offers_page.status_expired"),
  cancelled: t("offers_page.status_cancelled"),
}));
const STATUS_TONES = {
  active: "chip-success",
  upcoming: "chip-info",
  expired: "chip-neutral",
  cancelled: "chip-danger",
};

// أسماء الحقول الفعلية بالـ API (OfferResource): discount_type ("percentage" | "fixed") +
// discount_value، وتواريخ start_date/end_date — لا يوجد image_url أو generator أو سعر مباشر بالعرض.
function discountLabel(offer) {
  if (!offer.discount_value) return null;
  return offer.discount_type === "percentage"
    ? t("offers_page.discount_percent", { percent: offer.discount_value })
    : t("offers_page.discount_amount", { value: offer.discount_value, currency: "₪" });
}

/* ===================== مودال تفاصيل العرض ===================== */
const selectedOffer = ref(null);
const isLoadingDetail = ref(false);

async function openOfferDetail(offer) {
  selectedOffer.value = offer;
  isLoadingDetail.value = true;
  try {
    const { data } = await offerService.show(offer.id);
    selectedOffer.value = data.data ?? data;
  } catch {
    // نكتفي بالبيانات المعروضة أصلاً بالقائمة لو فشل تحميل التفاصيل
  } finally {
    isLoadingDetail.value = false;
  }
}

onMounted(fetchOffers);
</script>

<template>
  <div class="space-y-6">
    <!-- ===================== رأس الصفحة ===================== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("common.account_breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("menu.offers") }}</span>
      </nav>

      <div class="relative flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-base">
              <Tags aria-hidden="true" />
            </span>
            {{ t("menu.offers") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ t("offers_page.subscriber_subtitle") }}
          </p>
        </div>
      </div>
    </section>

    <!-- ===================== قائمة العروض ===================== -->
    <div v-if="isLoading" class="grid sm:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="glass-card h-40 thumb-loading"></div>
    </div>

    <section v-else-if="error" v-reveal class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
      <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
      {{ error }}
    </section>

    <section v-else-if="offers.length === 0" v-reveal class="glass-card p-10 text-center max-w-md mx-auto">
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-2xl">
        <Tags aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("offers_page.empty_title") }}</h3>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("offers_page.empty_message") }}</p>
    </section>

    <div v-else v-reveal class="grid sm:grid-cols-2 gap-4">
      <div
        v-for="offer in offers"
        :key="offer.id"
        class="glass-card hoverable p-0 overflow-hidden flex flex-col"
      >
        <div class="p-4 lg:p-5 flex-1 flex flex-col">
          <div class="flex items-start justify-between gap-2 mb-1.5">
            <h3 class="font-extrabold text-[13.5px] flex-1">{{ offer.title }}</h3>
            <span v-if="offer.status" class="status-chip shrink-0" :class="STATUS_TONES[offer.status]">
              {{ STATUS_LABELS[offer.status] ?? offer.status }}
            </span>
          </div>

          <p v-if="offer.owner?.name" class="text-[11.5px] text-[#9a9d97] mb-1.5">
            <Factory class="me-1" aria-hidden="true" />{{ offer.owner.name }}
          </p>

          <p v-if="offer.description" class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] line-clamp-2 mb-3">
            {{ offer.description }}
          </p>

          <div class="mt-auto space-y-2">
            <div v-if="discountLabel(offer)" class="flex items-baseline gap-2">
              <span class="status-chip chip-danger">
                {{ discountLabel(offer) }}
              </span>
            </div>

            <p v-if="offer.start_date || offer.end_date" class="text-[11px] text-[#9a9d97] font-mono">
              <CalendarDays class="me-1" aria-hidden="true" />
              {{ offer.start_date }} <span v-if="offer.end_date">— {{ offer.end_date }}</span>
            </p>

            <button
              type="button"
              @click="openOfferDetail(offer)"
              class="btn-outline-brand w-full justify-center mt-2"
            >
              <Eye aria-hidden="true" />
              <span>{{ t("offers_page.view_details_button") }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== مودال تفاصيل العرض ===================== -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="selectedOffer"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
          @click.self="selectedOffer = null"
        >
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--gold shrink-0">
              <div class="modal-head-brand__inner flex-1">
                <span class="modal-head-brand__icon"><Tags aria-hidden="true" /></span>
                <div class="min-w-0 flex-1">
                  <h3 class="modal-head-brand__title flex items-center gap-2 w-full">
                    <span class="truncate">{{ selectedOffer.title }}</span>
                    <span v-if="selectedOffer.status" class="status-chip shrink-0 ms-auto" :class="STATUS_TONES[selectedOffer.status]">
                      {{ STATUS_LABELS[selectedOffer.status] ?? selectedOffer.status }}
                    </span>
                  </h3>
                  <p v-if="selectedOffer.owner?.name" class="modal-head-brand__subtitle">
                    {{ selectedOffer.owner.name }}
                  </p>
                </div>
              </div>
              <button type="button" @click="selectedOffer = null" class="modal-head-brand__close">
                <X aria-hidden="true" />
              </button>
            </div>

            <div class="p-5 overflow-y-auto">
              <div v-if="isLoadingDetail" class="py-10 text-center text-[12.5px] text-[#9a9d97]">
                <LoaderCircle class="animate-spin" aria-hidden="true" /> {{ t("common.loading") }}
              </div>

              <template v-else>
                <p v-if="selectedOffer.description" class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-4">
                  {{ selectedOffer.description }}
                </p>

                <div class="space-y-0.5 mb-4">
                  <div v-if="selectedOffer.owner?.name" class="info-row-simple">
                    <span>{{ t("offers_page.generator_label") }}</span>
                    <b>{{ selectedOffer.owner.name }}</b>
                  </div>
                  <div v-if="selectedOffer.start_date" class="info-row-simple">
                    <span>{{ t("offers_page.start_date_label") }}</span>
                    <b class="font-mono">{{ selectedOffer.start_date }}</b>
                  </div>
                  <div v-if="selectedOffer.end_date" class="info-row-simple">
                    <span>{{ t("offers_page.end_date_label") }}</span>
                    <b class="font-mono">{{ selectedOffer.end_date }}</b>
                  </div>
                  <div v-if="discountLabel(selectedOffer)" class="info-row-simple">
                    <span>{{ t("offers_page.discount_label") }}</span>
                    <b class="font-mono">{{ discountLabel(selectedOffer) }}</b>
                  </div>
                </div>
              </template>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="selectedOffer = null" class="btn-outline-brand">
                <span>{{ t("common.close") }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>