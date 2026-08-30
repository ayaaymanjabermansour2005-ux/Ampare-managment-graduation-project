import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import invoiceService from "../services/invoiceService";
import paymentService from "../services/paymentService";

export function usePaymentGateway(invoiceId) {
  const { t } = useI18n();
  const invoice = ref(null);
  const paymentMethods = ref([]);
  const isLoading = ref(true);
  const isSubmitting = ref(false);
  const error = ref(null);
  const successPayment = ref(null);
  const isQueuedOffline = ref(false);

  let paymentIdempotencyKey = crypto.randomUUID();

  const remainingBalance = computed(() =>
    invoice.value ? Number(invoice.value.remaining_balance) : 0,
  );

  const walletMethods = computed(() =>
    paymentMethods.value.filter((m) => m.type === "wallet"),
  );
  const bankMethods = computed(() =>
    paymentMethods.value.filter((m) => m.type === "bank"),
  );
  const cashMethods = computed(() =>
    paymentMethods.value.filter((m) => m.type === "cash"),
  );

  async function load() {
    isLoading.value = true;
    error.value = null;

    paymentIdempotencyKey = crypto.randomUUID();

    try {
      const [invoiceRes, methodsRes] = await Promise.all([
        invoiceService.getInvoice(invoiceId),
        invoiceService.getPaymentMethods(invoiceId),
      ]);

      invoice.value = invoiceRes.data.data;
      paymentMethods.value = methodsRes.data.data;
    } catch (err) {
      error.value =
        normalizeApiError(err, t("payment_gateway.load_failed")).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * @param {{ payment_method_id: number, amount: number, proof_image?: File|null, transaction_reference?: string, note?: string }} payload
   */
  async function submitPayment(payload) {
    isSubmitting.value = true;
    error.value = null;
    isQueuedOffline.value = false;

    try {
      const formData = new FormData();
      formData.append("invoice_id", invoiceId);
      formData.append("payment_method_id", payload.payment_method_id);
      formData.append("amount", payload.amount);

      if (payload.transaction_reference) {
        formData.append("transaction_reference", payload.transaction_reference);
      }

      if (payload.note) {
        formData.append("note", payload.note);
      }

      if (payload.proof_image) {
        formData.append("proof_image", payload.proof_image);
      }
      const { data } = await paymentService.createPayment(
        formData,
        paymentIdempotencyKey,
      );

      if (data.queued) {
        isQueuedOffline.value = true;
        successPayment.value = null;
        return null;
      }

      successPayment.value = data.data;

      paymentIdempotencyKey = crypto.randomUUID();

      return data.data;
    } catch (err) {
      error.value = normalizeApiError(err, t("payment_gateway.submit_error")).message;
    } finally {
      isSubmitting.value = false;
    }
  }

  /**
   * @param {{ amount: number, card_number: string, card_holder_name: string, expiry_month: string, expiry_year: string, cvv: string }} payload
   */
  const gatewayError = ref(null);
  const isSubmittingGateway = ref(false);

  async function submitGatewayPayment(payload) {
    isSubmittingGateway.value = true;
    gatewayError.value = null;

    try {
      const { data } = await paymentService.payViaGateway(
        {
          invoice_id: invoiceId,
          amount: payload.amount,
          card_number: payload.card_number,
          card_holder_name: payload.card_holder_name,
          expiry_month: payload.expiry_month,
          expiry_year: payload.expiry_year,
          cvv: payload.cvv,
        },
        paymentIdempotencyKey,
      );

      successPayment.value = data.data;
      paymentIdempotencyKey = crypto.randomUUID();

      return data.data;
    } catch (err) {
      gatewayError.value = err.response?.data ?? {
        message: t("payment_gateway.gateway_error"),
      };
      return null;
    } finally {
      isSubmittingGateway.value = false;
    }
  }

  return {
    invoice,
    paymentMethods,
    walletMethods,
    bankMethods,
    cashMethods,
    remainingBalance,
    isLoading,
    isSubmitting,
    error,
    successPayment,
    isQueuedOffline,
    load,
    submitPayment,
    isSubmittingGateway,
    gatewayError,
    submitGatewayPayment,
  };
}