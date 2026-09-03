import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/invoiceService', () => ({
    default: {
        getInvoice: vi.fn(),
        getPaymentMethods: vi.fn(),
    },
}));
vi.mock('@/services/paymentService', () => ({
    default: {
        createPayment: vi.fn(),
        payViaGateway: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            payment_gateway: {
                load_failed: 'تعذر تحميل بيانات الدفع',
                submit_error: 'تعذر إرسال الدفعة',
                gateway_error: 'تعذر إتمام الدفع عبر البوابة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { usePaymentGateway } = await import('./usePaymentGateway');
const invoiceService = (await import('@/services/invoiceService')).default;
const paymentService = (await import('@/services/paymentService')).default;

describe('usePaymentGateway', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading, with no invoice/methods and zero remaining balance', () => {
        const { invoice, paymentMethods, isLoading, isSubmitting, error, successPayment, isQueuedOffline, remainingBalance, walletMethods, bankMethods, cashMethods } =
            usePaymentGateway(42);

        expect(invoice.value).toBeNull();
        expect(paymentMethods.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(isSubmitting.value).toBe(false);
        expect(error.value).toBeNull();
        expect(successPayment.value).toBeNull();
        expect(isQueuedOffline.value).toBe(false);
        expect(remainingBalance.value).toBe(0);
        expect(walletMethods.value).toEqual([]);
        expect(bankMethods.value).toEqual([]);
        expect(cashMethods.value).toEqual([]);
    });

    describe('load()', () => {
        it('populates invoice/paymentMethods and buckets methods by type on success', async () => {
            invoiceService.getInvoice.mockResolvedValue({ data: { data: { id: 42, remaining_balance: '150.50' } } });
            invoiceService.getPaymentMethods.mockResolvedValue({
                data: {
                    data: [
                        { id: 1, type: 'wallet' },
                        { id: 2, type: 'bank' },
                        { id: 3, type: 'cash' },
                        { id: 4, type: 'wallet' },
                    ],
                },
            });

            const { load, invoice, paymentMethods, remainingBalance, walletMethods, bankMethods, cashMethods, isLoading } = usePaymentGateway(42);
            await load();

            expect(invoiceService.getInvoice).toHaveBeenCalledWith(42);
            expect(invoiceService.getPaymentMethods).toHaveBeenCalledWith(42);
            expect(invoice.value).toEqual({ id: 42, remaining_balance: '150.50' });
            expect(remainingBalance.value).toBe(150.5);
            expect(paymentMethods.value).toHaveLength(4);
            expect(walletMethods.value.map((m) => m.id)).toEqual([1, 4]);
            expect(bankMethods.value.map((m) => m.id)).toEqual([2]);
            expect(cashMethods.value.map((m) => m.id)).toEqual([3]);
            expect(isLoading.value).toBe(false);
        });

        it('sets the translated error and re-throws on failure', async () => {
            invoiceService.getInvoice.mockRejectedValue({ message: 'Network Error' });
            invoiceService.getPaymentMethods.mockResolvedValue({ data: { data: [] } });

            const { load, error, isLoading } = usePaymentGateway(42);

            await expect(load()).rejects.toBeTruthy();
            expect(error.value).toBe('تعذر تحميل بيانات الدفع');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('submitPayment()', () => {
        it('builds the FormData with required + optional fields and stores the successful result', async () => {
            paymentService.createPayment.mockResolvedValue({ data: { data: { id: 9, status: 'pending' } } });

            const { submitPayment, successPayment, isQueuedOffline, isSubmitting, error } = usePaymentGateway(42);
            const result = await submitPayment({
                payment_method_id: 3,
                amount: 100,
                transaction_reference: 'REF1',
                note: 'a note',
            });

            expect(result).toEqual({ id: 9, status: 'pending' });
            expect(successPayment.value).toEqual({ id: 9, status: 'pending' });
            expect(isQueuedOffline.value).toBe(false);
            expect(isSubmitting.value).toBe(false);
            expect(error.value).toBeNull();

            const formData = paymentService.createPayment.mock.calls[0][0];
            expect(formData.get('invoice_id')).toBe('42');
            expect(formData.get('payment_method_id')).toBe('3');
            expect(formData.get('amount')).toBe('100');
            expect(formData.get('transaction_reference')).toBe('REF1');
            expect(formData.get('note')).toBe('a note');
            expect(typeof paymentService.createPayment.mock.calls[0][1]).toBe('string');
        });

        it('omits optional fields from the FormData when not provided', async () => {
            paymentService.createPayment.mockResolvedValue({ data: { data: { id: 10 } } });

            const { submitPayment } = usePaymentGateway(42);
            await submitPayment({ payment_method_id: 1, amount: 50 });

            const formData = paymentService.createPayment.mock.calls[0][0];
            expect(formData.get('transaction_reference')).toBeNull();
            expect(formData.get('note')).toBeNull();
            expect(formData.get('proof_image')).toBeNull();
        });

        it('treats a queued (offline) response as neither success nor failure', async () => {
            paymentService.createPayment.mockResolvedValue({ data: { queued: true } });

            const { submitPayment, successPayment, isQueuedOffline } = usePaymentGateway(42);
            const result = await submitPayment({ payment_method_id: 1, amount: 50 });

            expect(result).toBeNull();
            expect(isQueuedOffline.value).toBe(true);
            expect(successPayment.value).toBeNull();
        });

        it('sets the translated error message on failure (network error)', async () => {
            paymentService.createPayment.mockRejectedValue({ message: 'Network Error' });

            const { submitPayment, error, isSubmitting } = usePaymentGateway(42);
            const result = await submitPayment({ payment_method_id: 1, amount: 50 });

            expect(result).toBeUndefined();
            expect(error.value).toBe('تعذر إرسال الدفعة');
            expect(isSubmitting.value).toBe(false);
        });

        it('rotates the idempotency key after each successful submission', async () => {
            paymentService.createPayment.mockResolvedValue({ data: { data: { id: 1 } } });

            const { submitPayment } = usePaymentGateway(42);
            await submitPayment({ payment_method_id: 1, amount: 10 });
            await submitPayment({ payment_method_id: 1, amount: 10 });

            const firstKey = paymentService.createPayment.mock.calls[0][1];
            const secondKey = paymentService.createPayment.mock.calls[1][1];
            expect(firstKey).not.toBe(secondKey);
        });
    });

    describe('submitGatewayPayment()', () => {
        const gatewayPayload = {
            amount: 75,
            card_number: '4111111111111111',
            card_holder_name: 'Test User',
            expiry_month: '12',
            expiry_year: '2030',
            cvv: '123',
        };

        it('sends the mapped payload and stores the successful result', async () => {
            paymentService.payViaGateway.mockResolvedValue({ data: { data: { id: 5, status: 'paid' } } });

            const { submitGatewayPayment, successPayment, gatewayError, isSubmittingGateway } = usePaymentGateway(42);
            const result = await submitGatewayPayment(gatewayPayload);

            expect(result).toEqual({ id: 5, status: 'paid' });
            expect(successPayment.value).toEqual({ id: 5, status: 'paid' });
            expect(gatewayError.value).toBeNull();
            expect(isSubmittingGateway.value).toBe(false);
            expect(paymentService.payViaGateway).toHaveBeenCalledWith(
                { invoice_id: 42, ...gatewayPayload },
                expect.any(String),
            );
        });

        it('stores the raw 422 response payload (message + field errors) on validation failure', async () => {
            paymentService.payViaGateway.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { card_number: ['رقم البطاقة غير صالح.'] } } },
            });

            const { submitGatewayPayment, gatewayError } = usePaymentGateway(42);
            const result = await submitGatewayPayment(gatewayPayload);

            expect(result).toBeNull();
            expect(gatewayError.value).toEqual({ message: 'Invalid.', errors: { card_number: ['رقم البطاقة غير صالح.'] } });
        });

        it('falls back to the translated gateway error message on a network error', async () => {
            paymentService.payViaGateway.mockRejectedValue({ message: 'Network Error' });

            const { submitGatewayPayment, gatewayError } = usePaymentGateway(42);
            const result = await submitGatewayPayment(gatewayPayload);

            expect(result).toBeNull();
            expect(gatewayError.value).toEqual({ message: 'تعذر إتمام الدفع عبر البوابة' });
        });
    });
});
