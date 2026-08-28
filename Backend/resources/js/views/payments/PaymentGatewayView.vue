<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { usePaymentGateway } from "../../composables/usePaymentGateway";
import { useAuthStore } from "@/stores/auth";
import { resolveHomeRouteName } from "@/utils/roleRedirect";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Banknote, Check, Clock, CreditCard, Info, Landmark, Wallet, WifiOff } from "@lucide/vue";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();
const invoiceId = route.params.id;

const {
  invoice,
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
} = usePaymentGateway(invoiceId);

const selectedType = ref(null);
const selectedMethodId = ref(null);
const isVerifying = ref(false);
const fileError = ref(null);

// FRONT-001: نفس الفحص المستخدم لإخفاء زر البطاقة — موجود هون كمان لمنع أي
// حالة نظرية يوصل فيها selectedType لقيمة 'card' ببناء إنتاج (الزر الوحيد
// اللي بيضبطها أصلًا مخفي، لكن هاد فحص دفاعي إضافي رخيص لا يضر).
const isProductionBuild = import.meta.env.PROD;

const form = reactive({
  amount: 0,
  walletNumber: "",
  walletPin: "",
  receiptFile: null,
  transactionReference: "",
  note: "",
});

const cardForm = reactive({
  card_holder_name: "",
  card_number: "",
  expiry_month: "",
  expiry_year: "",
  cvv: "",
});
const wasGatewayPayment = ref(false);

const availableTypes = computed(() => {
  const types = [];
  if (walletMethods.value.length) types.push("wallet");
  if (bankMethods.value.length) types.push("bank");
  if (cashMethods.value.length) types.push("cash");
  return types;
});

const methodsForSelectedType = computed(() => {
  if (selectedType.value === "wallet") return walletMethods.value;
  if (selectedType.value === "bank") return bankMethods.value;
  if (selectedType.value === "cash") return cashMethods.value;
  return [];
});

const methodSelectOptions = computed(() =>
  methodsForSelectedType.value.map((m) => ({
    value: m.id,
    label: `${m.type === "wallet" ? t("payment_gateway.wallet_option_label") : m.bank_name} — ${m.account_name}`,
  })),
);

const selectedMethod = computed(
  () =>
    methodsForSelectedType.value.find((m) => m.id === selectedMethodId.value) ??
    null,
);

function chooseType(type) {
  selectedType.value = type;
  selectedMethodId.value = methodsForSelectedType.value[0]?.id ?? null;

  form.walletNumber = "";
  form.walletPin = "";
  form.receiptFile = null;
  form.transactionReference = "";
  fileError.value = null;

  cardForm.card_holder_name = "";
  cardForm.card_number = "";
  cardForm.expiry_month = "";
  cardForm.expiry_year = "";
  cardForm.cvv = "";
}

function handleFileChange(event) {
  const file = event.target.files?.[0] ?? null;
  fileError.value = null;

  if (file) {
    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
    const maxBytes = 2 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
      fileError.value = t("payment_gateway.file_type_error");
      event.target.value = "";
      form.receiptFile = null;
      return;
    }

    if (file.size > maxBytes) {
      fileError.value = t("payment_gateway.file_size_error");
      event.target.value = "";
      form.receiptFile = null;
      return;
    }
  }

  form.receiptFile = file;
}

function formatCardNumberInput(e) {
  const digits = e.target.value.replace(/\D/g, "").slice(0, 19);
  cardForm.card_number = digits;
}

const canSubmit = computed(() => {
  if (!form.amount || form.amount <= 0 || form.amount > remainingBalance.value)
    return false;

  if (selectedType.value === "card") {
    if (isProductionBuild) return false;

    return (
      cardForm.card_number.length >= 13 &&
      cardForm.card_holder_name.trim().length > 0 &&
      /^(0?[1-9]|1[0-2])$/.test(cardForm.expiry_month) &&
      /^\d{2,4}$/.test(cardForm.expiry_year) &&
      /^\d{3,4}$/.test(cardForm.cvv)
    );
  }

  if (!selectedMethodId.value) return false;

  if (selectedType.value === "wallet") {
    return (
      form.walletNumber.trim().length > 0 && form.walletPin.trim().length >= 4
    );
  }

  if (selectedType.value === "bank") {
    return !!form.receiptFile && !fileError.value;
  }

  if (selectedType.value === "cash") {
    return true;
  }

  return false;
});

async function handleSubmit() {
  if (!canSubmit.value) return;

  if (selectedType.value === "card") {
    wasGatewayPayment.value = true;
    await submitGatewayPayment({
      amount: form.amount,
      card_holder_name: cardForm.card_holder_name,
      card_number: cardForm.card_number,
      expiry_month: cardForm.expiry_month,
      expiry_year: cardForm.expiry_year,
      cvv: cardForm.cvv,
    });
    return;
  }

  wasGatewayPayment.value = false;

  if (selectedType.value === "wallet") {
    isVerifying.value = true;
    await new Promise((resolve) => setTimeout(resolve, 1200));
    isVerifying.value = false;
    form.walletPin = "";
  }

  try {
    await submitPayment({
      payment_method_id: selectedMethodId.value,
      amount: form.amount,
      proof_image: selectedType.value === "bank" ? form.receiptFile : null,
      transaction_reference:
        selectedType.value === "wallet"
          ? `SIM-${form.walletNumber.slice(-4)}-${Date.now().toString().slice(-6)}`
          : form.transactionReference || undefined,
      note: form.note || undefined,
    });
  } catch {}
}

function goHome() {
  router.push({ name: resolveHomeRouteName(authStore) });
}

onMounted(async () => {
  await load();
  form.amount = remainingBalance.value;
});
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <div
      v-if="isLoading"
      class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8 text-center text-gray-500 dark:text-gray-400"
    >
      {{ t("payment_gateway.loading") }}
    </div>

    <div
      v-else-if="error && !invoice"
      class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8 text-center text-danger"
    >
      {{ error }}
    </div>

    <!-- نجاح: دفع فوري عبر البطاقة -->
    <div
      v-else-if="successPayment && wasGatewayPayment"
      class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8 text-center"
    >
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-success-bg flex items-center justify-center">
        <Check class="text-success text-2xl" aria-hidden="true" />
      </div>

      <h1 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">{{ t("payment_gateway.gateway_success_title") }}</h1>
      <p class="text-gray-600 dark:text-gray-300 mb-1">{{ t("payment_gateway.gateway_success_message") }}</p>
      <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">
        {{ t("payment_gateway.amount_with_ref", { amount: successPayment.amount }) }}
        <span v-if="successPayment.transaction_reference">
          {{ t("payment_gateway.reference_suffix", { reference: successPayment.transaction_reference }) }}
        </span>
      </p>

      <button
        @click="goHome"
        class="bg-gradient-to-l from-primary-600 via-primary-500 to-secondary-600 text-white px-6 py-2.5 rounded-lg hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200 font-medium"
      >
        {{ t("payment_gateway.back_to_dashboard") }}
      </button>
    </div>

    <!-- نجاح: دفعة بانتظار مراجعة (بنك/كاش/محفظة) -->
    <div
      v-else-if="successPayment"
      class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8 text-center"
    >
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center">
        <Clock class="text-primary-500 text-2xl" aria-hidden="true" />
      </div>

      <h1 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">
        {{ t("payment_gateway.pending_review_title") }}
      </h1>

      <p class="text-gray-600 dark:text-gray-300 mb-1">
        {{ t("payment_gateway.pending_review_message") }}
      </p>

      <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">
        {{ t("payment_gateway.amount_with_ref", { amount: successPayment.amount }) }}
        <span v-if="successPayment.transaction_reference">
          {{ t("payment_gateway.reference_suffix", { reference: successPayment.transaction_reference }) }}
        </span>
      </p>

      <button
        @click="goHome"
        class="bg-primary-500 text-white px-6 py-2 rounded-md hover:bg-primary-600 transition"
      >
        {{ t("payment_gateway.back_to_dashboard") }}
      </button>
    </div>

    <div
      v-else-if="isQueuedOffline"
      class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8 text-center"
    >
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-50 dark:bg-white/5 flex items-center justify-center">
        <WifiOff class="text-gray-500 dark:text-gray-400 text-2xl" aria-hidden="true" />
      </div>

      <h1 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">
        {{ t("payment_gateway.offline_title") }}
      </h1>

      <p class="text-gray-600 dark:text-gray-300 mb-1">
        {{ t("payment_gateway.offline_message") }}
      </p>

      <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">
        {{ t("payment_gateway.offline_note") }}
      </p>

      <button
        @click="goHome"
        class="bg-primary-500 text-white px-6 py-2 rounded-md hover:bg-primary-600 transition"
      >
        {{ t("payment_gateway.back_to_dashboard") }}
      </button>
    </div>

    <div v-else-if="invoice" class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8">
      <h1 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-1">{{ t("payment_gateway.page_title") }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        {{ t("payment_gateway.invoice_hash_remaining", { id: invoice.id }) }}
        <strong class="text-gray-700 dark:text-gray-200">{{ remainingBalance }} ₪</strong>
      </p>

      <div
        v-if="error"
        class="bg-danger-bg text-danger text-sm rounded-md p-3 mb-4"
      >
        {{ error }}
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2"
          >{{ t("payment_gateway.payment_method_label") }}</label
        >
        <div class="flex gap-3 flex-wrap">
          <!-- FRONT-001: بطاقة الدفع التجريبية (بوابة محاكاة، بلا Stripe حقيقي)
               مخفية بالكامل في أي بناء إنتاج (import.meta.env.PROD) — الـ
               backend أصلًا يرفضها بـ ProcessGatewayPaymentAction عند
               app()->environment('production')، لكن ذلك الرفض بيصير بعد ما
               المستخدم يعبّي النموذج ويحاول الدفع، وهو تجربة استخدام مربكة.
               إخفاؤها هون من الواجهة أوضح: "معطّلة بالكامل" فعليًا لا "تظهر
               وتفشل لاحقًا". -->
          <button
            v-if="!isProductionBuild"
            type="button"
            @click="chooseType('card')"
            :class="[
              'flex-1 min-w-[7rem] border rounded-lg py-3 px-4 text-sm font-medium transition-colors duration-150',
              selectedType === 'card'
                ? 'border-secondary-500 bg-secondary-50 dark:bg-secondary-500/10 text-secondary-700 dark:text-secondary-300'
                : 'border-border dark:border-white/10 text-gray-600 dark:text-gray-300 hover:border-gray-300 dark:hover:border-white/20',
            ]"
          >
            <CreditCard class="me-1" aria-hidden="true" /> {{ t("payment_gateway.method_card") }}
            <span class="status-chip chip-warning ms-1 align-middle">{{ t("payment_gateway.demo_badge") }}</span>
          </button>

          <button
            v-if="walletMethods.length"
            type="button"
            @click="chooseType('wallet')"
            :class="[
              'flex-1 min-w-[7rem] border rounded-lg py-3 px-4 text-sm font-medium transition-colors duration-150',
              selectedType === 'wallet'
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300'
                : 'border-border dark:border-white/10 text-gray-600 dark:text-gray-300 hover:border-gray-300 dark:hover:border-white/20',
            ]"
          >
            <Wallet class="me-1" aria-hidden="true" /> {{ t("payment_gateway.method_wallet") }}
          </button>

          <button
            v-if="bankMethods.length"
            type="button"
            @click="chooseType('bank')"
            :class="[
              'flex-1 min-w-[7rem] border rounded-lg py-3 px-4 text-sm font-medium transition-colors duration-150',
              selectedType === 'bank'
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300'
                : 'border-border dark:border-white/10 text-gray-600 dark:text-gray-300 hover:border-gray-300 dark:hover:border-white/20',
            ]"
          >
            <Landmark class="me-1" aria-hidden="true" /> {{ t("payment_gateway.method_bank") }}
          </button>

          <button
            v-if="cashMethods.length"
            type="button"
            @click="chooseType('cash')"
            :class="[
              'flex-1 min-w-[7rem] border rounded-lg py-3 px-4 text-sm font-medium transition-colors duration-150',
              selectedType === 'cash'
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300'
                : 'border-border dark:border-white/10 text-gray-600 dark:text-gray-300 hover:border-gray-300 dark:hover:border-white/20',
            ]"
          >
            <Banknote class="me-1" aria-hidden="true" /> {{ t("payment_gateway.method_cash") }}
          </button>
        </div>
      </div>

      <form
        v-if="selectedType"
        @submit.prevent="handleSubmit"
        class="space-y-5"
      >
        <!-- ==================== نموذج البطاقة ==================== -->
        <template v-if="selectedType === 'card'">
          <div class="bg-secondary-50 dark:bg-secondary-500/10 rounded-lg p-3 text-xs text-secondary-700 dark:text-secondary-300 flex items-start gap-2">
            <Info class="mt-0.5" aria-hidden="true" />
            <span>
              {{ t("payment_gateway.card_sandbox_notice", { success_card: "4242 4242 4242 4242", decline_card: "4000 0000 0000 0002" }) }}
            </span>
          </div>

          <div v-if="gatewayError?.message" class="bg-danger-bg text-danger text-sm rounded-md p-3">
            {{ gatewayError.message }}
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ t("payment_gateway.card_holder_label") }}</label>
            <input
              v-model="cardForm.card_holder_name"
              type="text"
              :placeholder="t('payment_gateway.card_holder_placeholder')"
              class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-200"
            />
            <p v-if="gatewayError?.errors?.card_holder_name" class="text-xs text-danger mt-1">
              {{ gatewayError.errors.card_holder_name[0] }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ t("payment_gateway.card_number_label") }}</label>
            <input
              :value="cardForm.card_number"
              @input="formatCardNumberInput"
              type="text"
              inputmode="numeric"
              dir="ltr"
              placeholder="4242 4242 4242 4242"
              maxlength="19"
              class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm font-mono tracking-widest bg-transparent text-gray-700 dark:text-gray-200"
            />
            <p v-if="gatewayError?.errors?.card_number" class="text-xs text-danger mt-1">
              {{ gatewayError.errors.card_number[0] }}
            </p>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ t("payment_gateway.expiry_month_label") }}</label>
              <input v-model="cardForm.expiry_month" type="text" inputmode="numeric" dir="ltr" maxlength="2" placeholder="MM" class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm text-center font-mono bg-transparent text-gray-700 dark:text-gray-200" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ t("payment_gateway.expiry_year_label") }}</label>
              <input v-model="cardForm.expiry_year" type="text" inputmode="numeric" dir="ltr" maxlength="2" placeholder="YY" class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm text-center font-mono bg-transparent text-gray-700 dark:text-gray-200" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ t("payment_gateway.cvv_label") }}</label>
              <input v-model="cardForm.cvv" type="password" inputmode="numeric" dir="ltr" maxlength="4" placeholder="•••" class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm text-center font-mono bg-transparent text-gray-700 dark:text-gray-200" />
            </div>
          </div>
        </template>

        <template v-else>
          <div v-if="methodsForSelectedType.length > 1">
            <label
              for="method-select"
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
              >{{ t("payment_gateway.select_account_label") }}</label
            >
            <AppDropdownSelect
              v-model="selectedMethodId"
              :options="methodSelectOptions"
              variant="field" width-class="w-full" match-trigger-width
            />
          </div>

          <div
            v-if="selectedType === 'bank' && selectedMethod"
            class="bg-gray-50 dark:bg-white/5 rounded-md p-4 text-sm space-y-1 text-gray-700 dark:text-gray-200"
          >
            <p>
              <span class="text-gray-500 dark:text-gray-400">{{ t("payment_gateway.bank_name_label") }}</span>
              {{ selectedMethod.bank_name }}
            </p>
            <p>
              <span class="text-gray-500 dark:text-gray-400">{{ t("payment_gateway.beneficiary_name_label") }}</span>
              {{ selectedMethod.account_name }}
            </p>
            <p>
              <span class="text-gray-500 dark:text-gray-400">{{ t("payment_gateway.account_number_label") }}</span>
              {{ selectedMethod.account_number }}
            </p>
          </div>

          <div
            v-if="selectedType === 'cash' && selectedMethod"
            class="bg-gray-50 dark:bg-white/5 rounded-md p-4 text-sm space-y-1 text-gray-700 dark:text-gray-200"
          >
            <p>
              <span class="text-gray-500 dark:text-gray-400">{{ t("payment_gateway.cash_recipient_label") }}</span>
              {{ selectedMethod.account_name }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500">
              {{ t("payment_gateway.cash_note", { action: t("payment_gateway.submit_for_review") }) }}
            </p>
          </div>
        </template>

        <div>
          <label
            for="amount"
            class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
            >{{ t("payment_gateway.amount_to_pay_label") }}</label
          >
          <input
            id="amount"
            v-model.number="form.amount"
            type="number"
            step="0.01"
            :min="0.01"
            :max="remainingBalance"
            class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-200"
          />
          <p
            v-if="form.amount > remainingBalance"
            class="text-xs text-danger mt-1"
          >
            {{ t("payment_gateway.amount_exceeds_balance", { amount: remainingBalance }) }}
          </p>
        </div>

        <template v-if="selectedType === 'wallet'">
          <div>
            <label
              for="wallet-number"
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
              >{{ t("payment_gateway.wallet_number_label") }}</label
            >
            <input
              id="wallet-number"
              v-model="form.walletNumber"
              type="text"
              inputmode="numeric"
              dir="ltr"
              :placeholder="t('payment_gateway.wallet_number_placeholder')"
              class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-200"
            />
          </div>

          <div>
            <label
              for="wallet-pin"
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
              >{{ t("payment_gateway.wallet_pin_label") }}</label
            >
            <input
              id="wallet-pin"
              v-model="form.walletPin"
              type="password"
              inputmode="numeric"
              dir="ltr"
              maxlength="6"
              placeholder="••••"
              class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-200"
            />
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
              {{ t("payment_gateway.wallet_pin_note") }}
            </p>
          </div>
        </template>

        <template v-if="selectedType === 'bank'">
          <div>
            <label
              for="receipt-file"
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
              >{{ t("payment_gateway.receipt_image_label") }}</label
            >
            <input
              id="receipt-file"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              @change="handleFileChange"
              class="w-full text-sm text-gray-600 dark:text-gray-300"
            />
            <p v-if="fileError" class="text-xs text-danger mt-1">
              {{ fileError }}
            </p>
          </div>

          <div>
            <label
              for="tx-reference"
              class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
            >
              {{ t("payment_gateway.tx_reference_label") }}
            </label>
            <input
              id="tx-reference"
              v-model="form.transactionReference"
              type="text"
              dir="ltr"
              class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-200"
            />
          </div>
        </template>

        <div v-if="selectedType !== 'card'">
          <label
            for="note"
            class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
          >
            {{ t("payment_gateway.note_label") }}
          </label>
          <textarea
            id="note"
            v-model="form.note"
            rows="2"
            maxlength="500"
            :placeholder="t('payment_gateway.note_placeholder')"
            class="w-full border border-gray-300 dark:border-white/10 rounded-md px-3 py-2 text-sm resize-none bg-transparent text-gray-700 dark:text-gray-200"
          ></textarea>
        </div>

        <button
          type="submit"
          :disabled="!canSubmit || isSubmitting || isVerifying || isSubmittingGateway"
          class="w-full text-white py-2.5 rounded-md disabled:opacity-50 transition-colors duration-150 font-medium"
          :class="
            selectedType === 'card'
              ? 'bg-gradient-to-l from-primary-600 via-primary-500 to-secondary-600 hover:shadow-lg'
              : 'bg-primary-500 hover:bg-primary-600'
          "
        >
          <span v-if="isVerifying">{{ t("payment_gateway.verifying_ellipsis") }}</span>
          <span v-else-if="isSubmittingGateway">{{ t("payment_gateway.paying_ellipsis") }}</span>
          <span v-else-if="isSubmitting">{{ t("payment_gateway.sending_ellipsis") }}</span>
          <span v-else-if="selectedType === 'card'">{{ t("payment_gateway.pay_now") }}</span>
          <span v-else>{{ t("payment_gateway.submit_for_review") }}</span>
        </button>
      </form>
    </div>

    <div
      v-else
      class="bg-surface dark:bg-[#1c1e20] rounded-card shadow-sm p-8 text-center text-gray-500 dark:text-gray-400"
    >
      {{ t("payment_gateway.load_failed") }}
    </div>
  </div>
</template>