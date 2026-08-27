<script setup>
import { ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import invoiceService from "@/services/invoiceService";
import paymentService from "@/services/paymentService";
import { Ban, CircleAlert, CircleCheck, Eye, FileDown, Info, LoaderCircle, Receipt, RotateCw, TriangleAlert, Wallet, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const props = defineProps({
  invoices: { type: Array, required: true },
  invoicesPagination: { type: Object, required: true },
  isLoadingInvoices: { type: Boolean, required: true },
  invoicesError: { type: [String, null], default: null },
  totalDue: { type: Number, required: true },
  isLoadingInvoiceDetail: { type: Boolean, required: true },

  payments: { type: Array, required: true },
  paymentsPagination: { type: Object, required: true },
  isLoadingPayments: { type: Boolean, required: true },
  paymentsError: { type: [String, null], default: null },
  isResubmitting: { type: Boolean, required: true },
  resubmitError: { type: [Object, null], default: null },
  isCancelling: { type: Boolean, required: true },
});

const emit = defineEmits([
  "fetch-invoices",
  "fetch-invoice-detail",
  "fetch-payments",
  "resubmit-payment",
  "cancel-payment",
]);

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const INVOICE_STATUS_TONES = {
  pending: "chip-warning",
  partially_paid: "chip-info",
  paid: "chip-success",
  overdue: "chip-danger",
  cancelled: "chip-neutral",
};
function invoiceStatusLabel(status) {
  return t(`invoices_payments_panel.status_${status}`, status);
}

const PAYMENT_STATUS_TONES = {
  pending: "chip-warning",
  paid: "chip-success",
  rejected: "chip-danger",
  needs_correction: "chip-info",
};
function paymentStatusLabel(status) {
  return t(`invoices_payments_panel.payment_status_${status}`, status);
}
function methodLabel(method) {
  return t(`invoices_payments_panel.method_${method}`, method);
}

function goToPay(invoiceId) {
  router.push({ name: "payments.gateway", params: { id: invoiceId } });
}

const selectedInvoice = ref(null);
async function openInvoiceDetail(invoice) {
  selectedInvoice.value = invoice;
  emit("fetch-invoice-detail", invoice.id, (detail) => {
    if (detail) selectedInvoice.value = detail;
  });
}

const resubmitTarget = ref(null);
function handleResubmit(payload) {
  emit("resubmit-payment", { id: resubmitTarget.value.id, payload });
}
function onResubmitDone() {
  resubmitTarget.value = null;
}
defineExpose({ onResubmitDone });

const TABS = computed(() => [
  { key: "invoices", label: t("invoices_payments_panel.tab_invoices"), icon: "fa-file-invoice" },
  { key: "payments", label: t("invoices_payments_panel.tab_payments"), icon: "fa-wallet" },
]);
const activeTab = ref(route.query.tab === "payments" ? "payments" : "invoices");

function setTab(key) {
  activeTab.value = key;
  router.replace({ query: { ...route.query, tab: key } });
}

const hasOverdueInvoices = computed(() => props.invoices.some((i) => i.status === "overdue"));
const hasPendingInvoices = computed(() =>
  props.invoices.some((i) => ["pending", "partially_paid"].includes(i.status)),
);
const hasRejectedPayments = computed(() => props.payments.some((p) => p.status === "rejected"));
const hasAttentionPayments = computed(() =>
  props.payments.some((p) => ["pending", "needs_correction"].includes(p.status)),
);

const accent = computed(() => {
  if (activeTab.value === "invoices") {
    if (hasOverdueInvoices.value) return { hex: "#D9534F", glow: "rgba(217,83,79,0.22)", icon: "fa-triangle-exclamation" };
    if (hasPendingInvoices.value) return { hex: "#D4AF37", glow: "rgba(212,175,55,0.22)", icon: "fa-hourglass-half" };
    return { hex: "#52733D", glow: "rgba(82,115,61,0.2)", icon: "fa-file-invoice" };
  }
  if (hasRejectedPayments.value) return { hex: "#D9534F", glow: "rgba(217,83,79,0.22)", icon: "fa-triangle-exclamation" };
  if (hasAttentionPayments.value) return { hex: "#D4AF37", glow: "rgba(212,175,55,0.22)", icon: "fa-hourglass-half" };
  return { hex: "#52733D", glow: "rgba(82,115,61,0.2)", icon: "fa-wallet" };
});

async function handleCancelPayment(payment, confirmFn) {
  const confirmed = await confirmFn();
  if (confirmed) emit("cancel-payment", payment.id);
}
</script>

<template>
  <div class="space-y-6">
    <section class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 rounded-full blur-[100px] pointer-events-none transition-colors duration-500" :style="{ background: accent.glow }"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <div class="relative flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold flex items-center gap-2.5 mb-1.5">
            <span class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-base transition-colors duration-500" :style="{ background: `linear-gradient(135deg, ${accent.hex}, #3E582E)` }">
              <AppIcon :name="accent.icon" />
            </span>
            {{ t("invoices_payments_panel.title") }}
          </h1>
          <p v-if="activeTab === 'invoices' && totalDue > 0" class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
            {{ t("invoices_payments_panel.due_prefix") }} <span class="font-mono font-data font-bold text-[#D9534F]">{{ totalDue.toFixed(2) }} ₪</span> {{ t("invoices_payments_panel.due_suffix") }}
          </p>
          <p v-else-if="activeTab === 'invoices'" class="text-[12.5px] text-[#1f7a37] dark:text-[#7fe19c]">
            <CircleCheck aria-hidden="true" /> {{ t("invoices_payments_panel.no_due_message") }}
          </p>
        </div>

        <a v-if="activeTab === 'payments' && payments.length > 0" :href="paymentService.exportUrl()" target="_blank" class="btn-fill-brand shrink-0">
          <FileDown aria-hidden="true" />
          <span>{{ t("invoices_payments_panel.export_excel") }}</span>
        </a>
      </div>

      <div class="relative inline-flex items-center gap-1 p-1 rounded-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#eee8da] dark:border-white/10">
        <button
          v-for="tab in TABS" :key="tab.key" type="button"
          class="flex items-center gap-2 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
          :class="activeTab === tab.key ? 'bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white shadow-md' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          @click="setTab(tab.key)"
        >
          <AppIcon :name="tab.icon" />
          <span>{{ tab.label }}</span>
          <span v-if="tab.key === 'invoices' && hasOverdueInvoices" class="w-1.5 h-1.5 rounded-full bg-[#D9534F]" :class="activeTab === tab.key ? '!bg-white' : ''"></span>
          <span v-else-if="tab.key === 'payments' && hasRejectedPayments" class="w-1.5 h-1.5 rounded-full bg-[#D9534F]" :class="activeTab === tab.key ? '!bg-white' : ''"></span>
        </button>
      </div>
    </section>

    <template v-if="activeTab === 'invoices'">
      <div v-if="isLoadingInvoices" class="space-y-3">
        <div v-for="i in 4" :key="i" class="glass-card h-24 thumb-loading"></div>
      </div>

      <section v-else-if="invoicesError" class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
        <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
        {{ invoicesError }}
      </section>

      <section v-else-if="invoices.length === 0" class="glass-card p-10 text-center max-w-md mx-auto">
        <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
          <Receipt aria-hidden="true" />
        </span>
        <h3 class="font-extrabold text-[14px] mb-1">{{ t("invoices_payments_panel.no_invoices_title") }}</h3>
      </section>

      <div v-else class="space-y-3">
        <div v-for="invoice in invoices" :key="invoice.id" class="glass-card hoverable p-4 lg:p-5">
          <div class="flex items-start justify-between gap-3">
            <button type="button" @click="openInvoiceDetail(invoice)" class="text-start min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-extrabold text-[13.5px] hover:text-[#8A6D1F] dark:hover:text-[#F4E0A5] transition-colors">{{ t("common.invoice_hash", { id: invoice.id }) }}</h3>
                <span class="status-chip" :class="INVOICE_STATUS_TONES[invoice.status]">{{ invoiceStatusLabel(invoice.status) }}</span>
              </div>
              <p class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] mt-1 truncate">
                {{ invoice.generator?.name }}
                <span v-if="invoice.due_date">· {{ t("invoices_payments_panel.due_date_prefix") }} {{ invoice.due_date }}</span>
              </p>
            </button>
            <div class="text-end shrink-0">
              <p class="font-mono font-data font-extrabold text-[15px]">{{ invoice.final_amount }} {{ invoice.currency }}</p>
              <p v-if="invoice.remaining_balance_ils > 0" class="text-[11px] font-mono text-[#D97706]">{{ t("invoices_payments_panel.remaining_prefix") }} {{ invoice.remaining_balance_ils.toFixed(2) }} ₪</p>
            </div>
          </div>

          <div class="flex items-center justify-between gap-2 mt-3.5 pt-3.5 border-t border-[#f0ece0] dark:border-white/10">
            <button v-if="['pending', 'partially_paid', 'overdue'].includes(invoice.status)" type="button" @click="goToPay(invoice.id)" class="btn-fill-brand flex-1 justify-center">
              <Wallet aria-hidden="true" />
              <span>{{ t("invoices_payments_panel.pay_now") }}</span>
            </button>
            <div class="row-actions" :class="{ 'w-full justify-between': !['pending', 'partially_paid', 'overdue'].includes(invoice.status) }">
              <button type="button" class="action-btn action-btn--view" :title="t('invoices_payments_panel.details_title')" :aria-label="t('invoices_payments_panel.details_title')" @click="openInvoiceDetail(invoice)"><Eye aria-hidden="true" /></button>
              <div class="row-actions-divider"></div>
              <a :href="invoiceService.downloadPdfUrl(invoice.id)" target="_blank" class="action-btn" style="color: #8A6D1F" :title="t('invoices_payments_panel.download_pdf')" :aria-label="t('invoices_payments_panel.download_pdf')"><FileDown aria-hidden="true" /></a>
            </div>
          </div>
        </div>
      </div>

      <div v-if="invoicesPagination.last_page > 1" class="flex justify-center flex-wrap gap-2 pt-2">
        <button
          v-for="page in invoicesPagination.last_page" :key="page" type="button"
          class="w-9 h-9 rounded-lg text-[12px] font-mono font-data font-bold transition-colors"
          :class="page === invoicesPagination.current_page ? 'bg-gradient-to-l from-[#3E582E] to-[#8A6D1F] text-white shadow-md' : 'bg-white/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 text-[#6B6B6B] dark:text-[#a8aaa5] hover:border-[#8A6D1F]/40'"
          @click="emit('fetch-invoices', page)"
        >{{ page }}</button>
      </div>
    </template>

    <template v-else>
      <div v-if="isLoadingPayments" class="space-y-3">
        <div v-for="i in 4" :key="i" class="glass-card h-20 thumb-loading"></div>
      </div>

      <section v-else-if="paymentsError" class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
        <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
        {{ paymentsError }}
      </section>

      <section v-else-if="payments.length === 0" class="glass-card p-10 text-center max-w-md mx-auto">
        <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
          <Wallet aria-hidden="true" />
        </span>
        <h3 class="font-extrabold text-[14px] mb-1">{{ t("invoices_payments_panel.no_payments_title") }}</h3>
      </section>

      <div v-else class="space-y-3">
        <div v-for="payment in payments" :key="payment.id" class="glass-card p-4 lg:p-5" :class="{ 'border-[#D9534F]/30': payment.status === 'rejected' }">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 w-full">
                <p class="font-extrabold text-[13.5px]">{{ methodLabel(payment.payment_method_type) }}</p>
                <span class="status-chip shrink-0 ms-auto" :class="PAYMENT_STATUS_TONES[payment.status]">{{ paymentStatusLabel(payment.status) }}</span>
              </div>
              <p class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] mt-1 truncate">{{ payment.generator?.name }} · {{ t("common.invoice_hash", { id: payment.invoice_id }) }}</p>
            </div>
            <p class="font-mono font-data font-extrabold text-[15px] shrink-0">{{ payment.amount }} {{ payment.currency }}</p>
          </div>

          <div v-if="payment.status === 'rejected' && payment.rejection_reason" class="alert-box mt-3">
            <CircleAlert class="shrink-0" aria-hidden="true" />
            <span><strong>{{ t("invoices_payments_panel.rejection_reason_label") }}</strong> {{ payment.rejection_reason }}</span>
          </div>
          <div v-if="payment.status === 'needs_correction' && payment.review_note" class="flex items-start gap-2 text-[11.5px] mt-3 rounded-xl px-3.5 py-2.5" style="color: #0f6c7d; background: rgba(23,162,184,0.1)">
            <Info class="mt-0.5 shrink-0" aria-hidden="true" />
            <span><strong>{{ t("invoices_payments_panel.needs_correction_label") }}</strong> {{ payment.review_note }}</span>
          </div>

          <div v-if="payment.status === 'needs_correction'" class="flex gap-2 mt-3.5 pt-3.5 border-t border-[#f0ece0] dark:border-white/10">
            <button type="button" @click="resubmitTarget = payment" class="btn-fill-brand flex-1 justify-center">
              <RotateCw aria-hidden="true" />
              <span>{{ t("invoices_payments_panel.resubmit_action") }}</span>
            </button>
          </div>
          <div v-else-if="payment.status === 'pending'" class="flex gap-2 mt-3.5 pt-3.5 border-t border-[#f0ece0] dark:border-white/10">
            <button type="button" :disabled="isCancelling" class="btn-fill-brand btn-fill-brand--danger flex-1 justify-center" @click="emit('cancel-payment', payment.id)">
              <Ban aria-hidden="true" />
              <span>{{ t("invoices_payments_panel.cancel_payment_action") }}</span>
            </button>
          </div>
        </div>
      </div>

      <div v-if="paymentsPagination.last_page > 1" class="flex justify-center flex-wrap gap-2 pt-2">
        <button
          v-for="page in paymentsPagination.last_page" :key="page" type="button"
          class="w-9 h-9 rounded-lg text-[12px] font-mono font-data font-bold transition-colors"
          :class="page === paymentsPagination.current_page ? 'bg-gradient-to-l from-[#3E582E] to-[#8A6D1F] text-white shadow-md' : 'bg-white/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 text-[#6B6B6B] dark:text-[#a8aaa5] hover:border-[#8A6D1F]/40'"
          @click="emit('fetch-payments', page)"
        >{{ page }}</button>
      </div>
    </template>

    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="selectedInvoice" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="selectedInvoice = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--gold shrink-0">
              <div class="modal-head-brand__inner flex-1">
                <span class="modal-head-brand__icon"><Receipt aria-hidden="true" /></span>
                <div class="min-w-0 flex-1">
                  <h3 class="modal-head-brand__title flex items-center gap-2 w-full">
                    <span>{{ t("common.invoice_hash", { id: selectedInvoice.id }) }}</span>
                    <span class="status-chip shrink-0 ms-auto" :class="INVOICE_STATUS_TONES[selectedInvoice.status]">{{ invoiceStatusLabel(selectedInvoice.status) }}</span>
                  </h3>
                  <p class="modal-head-brand__subtitle">{{ selectedInvoice.generator?.name }}</p>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="selectedInvoice = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 overflow-y-auto">
              <div v-if="isLoadingInvoiceDetail" class="py-10 text-center text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">
                <LoaderCircle class="animate-spin" aria-hidden="true" /> {{ t("common.loading") }}
              </div>
              <template v-else>
                <div class="space-y-0.5 mb-4">
                  <div class="info-row-simple"><span>{{ t("invoices_payments_panel.detail_generator_label") }}</span><b>{{ selectedInvoice.generator?.name }}</b></div>
                  <div class="info-row-simple"><span>{{ t("invoices_payments_panel.detail_due_date_label") }}</span><b class="font-mono">{{ selectedInvoice.due_date }}</b></div>
                  <div v-if="selectedInvoice.consumed_kw" class="info-row-simple"><span>{{ t("invoices_payments_panel.detail_consumption_label") }}</span><b class="font-mono">{{ selectedInvoice.consumed_kw }} kW</b></div>
                  <div v-if="selectedInvoice.price_per_kw" class="info-row-simple"><span>{{ t("invoices_payments_panel.detail_unit_price_label") }}</span><b class="font-mono">{{ selectedInvoice.price_per_kw }} {{ selectedInvoice.currency }}/kW</b></div>
                </div>
                <div v-if="selectedInvoice.items?.length" class="form-section !p-0 overflow-hidden mb-4">
                  <div v-for="(item, i) in selectedInvoice.items" :key="i" class="flex items-center justify-between px-3.5 py-2.5 text-[12.5px]" :class="{ 'border-t border-[#eee8da] dark:border-white/10': i > 0 }">
                    <span class="text-[#6B6B6B] dark:text-[#a8aaa5]">{{ item.label }}</span>
                    <span class="font-mono font-bold">{{ item.amount }} {{ selectedInvoice.currency }}</span>
                  </div>
                </div>
                <div class="flex items-center justify-between py-3.5 border-y border-[#eee8da] dark:border-white/10 font-extrabold">
                  <span>{{ t("invoices_payments_panel.total_label") }}</span>
                  <span class="font-mono font-data text-[16px] text-[#8A6D1F]">{{ selectedInvoice.final_amount }} {{ selectedInvoice.currency }}</span>
                </div>
                <div v-if="selectedInvoice.qr_code_url" class="flex flex-col items-center gap-2 py-4">
                  <img :src="selectedInvoice.qr_code_url" :alt="t('invoices_payments_panel.qr_alt')" class="w-36 h-36 rounded-xl border border-[#eee8da] dark:border-white/10 p-2 bg-white" />
                  <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("invoices_payments_panel.qr_hint") }}</p>
                </div>
              </template>
            </div>

            <div class="modal-footer-brand">
              <a :href="invoiceService.downloadPdfUrl(selectedInvoice.id)" target="_blank" class="btn-outline-brand flex items-center gap-2">
                <FileDown aria-hidden="true" />
                <span>{{ t("invoices_payments_panel.download_pdf") }}</span>
              </a>
              <button v-if="['pending', 'partially_paid', 'overdue'].includes(selectedInvoice.status)" type="button" @click="goToPay(selectedInvoice.id)" class="btn-fill-brand">
                <Wallet aria-hidden="true" />
                <span>{{ t("invoices_payments_panel.pay_now") }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <PaymentResubmitForm
      :open="!!resubmitTarget"
      :payment="resubmitTarget"
      :is-submitting="isResubmitting"
      :server-error="resubmitError"
      @close="resubmitTarget = null"
      @submit="handleResubmit"
    />
  </div>
</template>