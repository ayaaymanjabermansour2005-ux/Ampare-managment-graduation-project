<!DOCTYPE html>
@php($isRtl = app()->getLocale() === 'ar')
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.7;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }

        .header p {
            margin: 0;
            color: #6b7280;
            font-size: 11px;
        }

        .parties {
            width: 100%;
            margin-bottom: 18px;
        }

        .parties td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #d1d5db;
        }

        .parties h3 {
            margin: 0 0 6px;
            font-size: 13px;
        }

        table.amounts {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.amounts th,
        table.amounts td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }

        table.amounts th {
            background: #f3f4f6;
            width: 220px;
        }

        .ils-note {
            color: #6b7280;
            font-size: 10px;
        }

        .status-pending {
            color: #92400e;
        }

        .status-partially_paid {
            color: #1d4ed8;
        }

        .status-paid {
            color: #166534;
        }

        .status-overdue {
            color: #b91c1c;
        }

        .status-cancelled {
            color: #6b7280;
        }

        table.payments {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        table.payments th,
        table.payments td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
            font-size: 11px;
        }

        table.payments th {
            background: #f3f4f6;
        }

        .footer {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .footer .qr {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
        }

        .footer .verify-note {
            display: table-cell;
            vertical-align: middle;
            padding-{{ $isRtl ? 'right' : 'left' }}: 12px;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>{{ __('pdf.invoice.title') }} #<bdi>{{ $invoice->id }}</bdi></h1>
        <p>{{ __('pdf.common.issue_date') }}: <bdi>{{ $invoice->created_at?->format('Y-m-d') }}</bdi></p>
    </div>

    <table class="parties">
        <tr>
            <td>
                <h3>{{ __('pdf.common.owner_heading') }}</h3>
                {{ $invoice->subscription?->generator?->owner?->name }}<br>
                {{ __('pdf.common.generator_label') }}: {{ $invoice->subscription?->generator?->name }}
            </td>
            <td>
                <h3>{{ __('pdf.common.subscriber_heading') }}</h3>
                {{ $invoice->subscription?->subscriberMeter?->subscriber?->user?->name }}<br>
                {{ __('pdf.common.meter_number_label') }}: {{ $invoice->subscription?->subscriberMeter?->meter_number }}
            </td>
        </tr>
    </table>

    <table class="amounts">
        <tr>
            <th>{{ __('pdf.invoice.amount') }}</th>
            <td><bdi>{{ number_format((float) $invoice->amount, 2) }}</bdi> {{ $invoice->currency->symbol() }}</td>
        </tr>
        <tr>
            <th>{{ __('pdf.invoice.discount') }}</th>
            <td>
                <bdi>{{ number_format((float) $invoice->discount_amount, 2) }}</bdi> {{ $invoice->currency->symbol() }}
                @if ($invoice->appliedOffer)
                    <br>
                    <span class="ils-note">({{ __('pdf.invoice.offer_note') }}: {{ $invoice->appliedOffer->title }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('pdf.invoice.final_amount') }}</th>
            <td>
                <bdi>{{ number_format((float) $invoice->final_amount, 2) }}</bdi> {{ $invoice->currency->symbol() }}
                @if ($invoice->currency->value !== 'ILS')
                    <br>
                    <span class="ils-note">
                        ({{ __('pdf.invoice.equivalent_note') }} <bdi>{{ number_format((float) $invoice->final_amount_ils, 2) }}</bdi> ₪
                        {{ __('pdf.invoice.exchange_rate_note') }} <bdi>{{ number_format((float) $invoice->exchange_rate, 4) }}</bdi>)
                    </span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('pdf.invoice.remaining') }}</th>
            <td><bdi>{{ number_format($remainingBalance, 2) }}</bdi> ₪</td>
        </tr>
        <tr>
            <th>{{ __('pdf.invoice.due_date') }}</th>
            <td><bdi>{{ $invoice->due_date?->format('Y-m-d') }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('pdf.invoice.status') }}</th>
            <td class="status-{{ $invoice->status->value }}">{{ \App\Support\ExportLabel::forEnum($invoice->status) }}</td>
        </tr>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <table class="payments">
            <thead>
                <tr>
                    <th>{{ __('pdf.invoice.payment_number_col') }}</th>
                    <th>{{ __('pdf.invoice.amount_col') }}</th>
                    <th>{{ __('pdf.invoice.amount_ils_col') }}</th>
                    <th>{{ __('pdf.invoice.paid_at_col') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->payments as $payment)
                    <tr>
                        <td>#<bdi>{{ $payment->id }}</bdi></td>
                        <td><bdi>{{ number_format((float) $payment->amount, 2) }}</bdi> {{ $payment->currency->symbol() }}</td>
                        <td><bdi>{{ number_format((float) $payment->amount_ils, 2) }}</bdi> ₪</td>
                        <td><bdi>{{ $payment->paid_at?->format('Y-m-d') }}</bdi></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <div class="qr">
            <img src="{{ $verificationQr }}" width="90" height="90" alt="QR">
        </div>
        <div class="verify-note">
            {{ __('pdf.invoice.verify_note') }}
        </div>
    </div>

</body>

</html>
