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

        table.terms {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.terms th,
        table.terms td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }

        table.terms th {
            background: #f3f4f6;
            width: 200px;
        }

        .signatures {
            margin-top: 50px;
            width: 100%;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #9ca3af;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>{{ __('pdf.contract.title') }} #<bdi>{{ $subscription->id }}</bdi></h1>
        <p>{{ __('pdf.common.issue_date') }}: <bdi>{{ now()->format('Y-m-d') }}</bdi></p>
    </div>

    <table class="parties">
        <tr>
            <td>
                <h3>{{ __('pdf.contract.party_one_heading') }}</h3>
                {{ $subscription->generator?->owner?->name }}<br>
                {{ __('pdf.common.generator_label') }}: {{ $subscription->generator?->name }}
            </td>
            <td>
                <h3>{{ __('pdf.contract.party_two_heading') }}</h3>
                {{ $subscription->subscriberMeter?->subscriber?->user?->name }}<br>
                {{ __('pdf.common.meter_number_label') }}: {{ $subscription->subscriberMeter?->meter_number }}
            </td>
        </tr>
    </table>

    <table class="terms">
        <tr>
            <th>{{ __('pdf.contract.price_per_kw') }}</th>
            <td><bdi>{{ number_format((float) $subscription->agreed_price_per_kw, 2) }}</bdi>
                {{ $subscription->currency->symbol() }}</td>
        </tr>
        <tr>
            <th>{{ __('pdf.contract.requested_capacity') }}</th>
            <td><bdi>{{ number_format((float) $subscription->requested_capacity_kw, 2) }}</bdi> {{ __('pdf.contract.kw_unit') }}</td>
        </tr>
        <tr>
            <th>{{ __('pdf.contract.service_period') }}</th>
            <td>
                {{ \App\Support\ExportLabel::forEnum($subscription->schedule) }}
                @if ($subscription->service_start_time)
                    (<bdi>{{ $subscription->service_start_time }} — {{ $subscription->service_end_time }}</bdi>)
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('pdf.contract.contract_type') }}</th>
            <td>{{ $subscription->contract_type ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('pdf.contract.start_date') }}</th>
            <td><bdi>{{ $subscription->start_date?->format('Y-m-d') }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('pdf.contract.end_date') }}</th>
            <td><bdi>{{ $subscription->end_date?->format('Y-m-d') ?? __('pdf.contract.end_date_undefined') }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('pdf.contract.status') }}</th>
            <td>{{ \App\Support\ExportLabel::forEnum($subscription->status) }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>{{ __('pdf.contract.signature_party_one') }}</td>
            <td>{{ __('pdf.contract.signature_party_two') }}</td>
        </tr>
    </table>

</body>

</html>
