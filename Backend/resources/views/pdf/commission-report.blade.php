<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header h1 {
            font-size: 17px;
            margin: 0 0 4px;
        }

        .header p {
            margin: 0;
            color: #6b7280;
            font-size: 11px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.items th,
        table.items td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: right;
        }

        table.items th {
            background: #f3f4f6;
        }

        .status-pending {
            color: #92400e;
        }

        .status-earned {
            color: #1d4ed8;
        }

        .status-paid {
            color: #166534;
        }

        .totals {
            margin-top: 16px;
            text-align: left;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>تقرير عمولات المنصة — {{ $owner->name }}</h1>
        <p>
            الفترة:
            {{ $from?->format('Y-m-d') ?? 'البداية' }}
            —
            {{ $to?->format('Y-m-d') ?? 'اليوم' }}
        </p>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>المولد</th>
                <th>نسبة العمولة</th>
                <th>قيمة العمولة</th>
                <th>الحالة</th>
                <th>تاريخ الاستحقاق</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commissions as $commission)
                <tr>
                    <td>#{{ $commission->invoice_id }}</td>
                    <td>{{ $commission->invoice?->subscription?->generator?->name }}</td>
                    <td>{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                    <td>{{ number_format((float) $commission->commission_amount, 2) }} ₪</td>
                    <td class="status-{{ $commission->status->value }}">{{ $commission->status->label() }}</td>
                    <td>{{ $commission->earned_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center">لا توجد عمولات ضمن هذه الفترة</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="totals">إجمالي العمولات: {{ number_format($totalAmount, 2) }} ₪</p>

</body>

</html>
