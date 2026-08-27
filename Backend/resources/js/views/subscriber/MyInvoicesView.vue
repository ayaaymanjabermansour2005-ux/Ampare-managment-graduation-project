<script setup>
import { onMounted } from "vue";
import { useSubscriberInvoices } from "@/composables/useSubscriberInvoices";
import { useSubscriberPayments } from "@/composables/useSubscriberPayments";
import InvoicesPaymentsPanel from "@/components/invoices/InvoicesPaymentsPanel.vue";
import { ref } from "vue";

const {
  invoices, pagination: invoicesPagination, isLoading: isLoadingInvoices,
  error: invoicesError, totalDue, fetchInvoices, isLoadingDetail, fetchInvoiceDetail,
} = useSubscriberInvoices();

const {
  payments, pagination: paymentsPagination, isLoading: isLoadingPayments,
  error: paymentsError, fetchPayments, isResubmitting, resubmitError,
  resubmitPayment, isCancelling, cancelPayment,
} = useSubscriberPayments();

const panelRef = ref(null);

async function handleFetchInvoiceDetail(id, cb) {
  const detail = await fetchInvoiceDetail(id);
  cb(detail);
}

async function handleResubmitPayment({ id, payload }) {
  const ok = await resubmitPayment(id, payload);
  if (ok) panelRef.value?.onResubmitDone();
}

onMounted(() => {
  fetchInvoices();
  fetchPayments();
});
</script>

<template>
  <InvoicesPaymentsPanel
    ref="panelRef"
    :invoices="invoices"
    :invoices-pagination="invoicesPagination"
    :is-loading-invoices="isLoadingInvoices"
    :invoices-error="invoicesError"
    :total-due="totalDue"
    :is-loading-invoice-detail="isLoadingDetail"
    :payments="payments"
    :payments-pagination="paymentsPagination"
    :is-loading-payments="isLoadingPayments"
    :payments-error="paymentsError"
    :is-resubmitting="isResubmitting"
    :resubmit-error="resubmitError"
    :is-cancelling="isCancelling"
    @fetch-invoices="fetchInvoices"
    @fetch-invoice-detail="handleFetchInvoiceDetail"
    @fetch-payments="fetchPayments"
    @resubmit-payment="handleResubmitPayment"
    @cancel-payment="cancelPayment"
  />
</template>