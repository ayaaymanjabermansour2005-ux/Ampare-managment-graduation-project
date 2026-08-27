<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من الفاتورة #{{ $invoiceId }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif;
            background: #FCFCFC;
            color: #2F2B33;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #FFFFFF;
            border: 1px solid #E4E3E5;
            border-radius: 0.625rem;
            box-shadow: 0 1px 2px rgba(47, 74, 40, 0.04), 0 8px 24px rgba(47, 74, 40, 0.06);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
        }

        .card-header {
            padding: 28px 24px 20px;
            text-align: center;
            border-bottom: 1px solid #E4E3E5;
        }

        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .badge-paid {
            background: #EEF3E7;
            color: #5D7A3E;
        }

        .badge-pending {
            background: #FDF2E3;
            color: #D97706;
        }

        .badge-partially_paid {
            background: #E6F0FF;
            color: #0077FE;
        }

        .badge-overdue {
            background: #FCEAEA;
            color: #E73F3F;
        }

        .badge-cancelled {
            background: #F2F2F2;
            color: #807C83;
        }

        .card-header h1 {
            font-size: 18px;
            color: #807C83;
            font-weight: 500;
        }

        .card-body {
            padding: 20px 24px 28px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #F2F2F2;
            font-size: 15px;
        }

        .row:last-child {
            border-bottom: none;
        }

        .row .label {
            color: #9C9C9C;
        }

        .row .value {
            color: #2F2B33;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
        }

        .amount {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 8px;
            background: #EEF2EC;
        }

        .amount .value {
            font-size: 32px;
            font-weight: 600;
            color: #2F4A28;
            font-family: 'JetBrains Mono', monospace;
        }

        .amount .currency {
            font-size: 16px;
            color: #7C9770;
            font-weight: 500;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: #9C9C9C;
            padding: 16px 24px;
            background: #FCFCFC;
            border-top: 1px solid #F2F2F2;
        }

        .check-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #EEF2EC;
            color: #2F4A28;
            font-size: 22px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="card-header">
            <div class="check-icon">✓</div>
            <span class="badge badge-{{ $status }}">{{ $statusLabel }}</span>
            <h1>نتيجة التحقق من الفاتورة</h1>
        </div>

        <div class="amount">
            <div class="value">{{ number_format($finalAmount, 2) }}</div>
            <div class="currency">{{ $currency }}</div>
        </div>

        <div class="card-body">
            <div class="row">
                <span class="label">رقم الفاتورة</span>
                <span class="value">#{{ $invoiceId }}</span>
            </div>
            @if ($generatorName)
                <div class="row">
                    <span class="label">المولد</span>
                    <span class="value" style="font-family: 'Cairo', sans-serif;">{{ $generatorName }}</span>
                </div>
            @endif
            @if ($dueDate)
                <div class="row">
                    <span class="label">تاريخ الاستحقاق</span>
                    <span class="value">{{ $dueDate }}</span>
                </div>
            @endif
            @if ($issuedAt)
                <div class="row">
                    <span class="label">تاريخ الإصدار</span>
                    <span class="value">{{ $issuedAt }}</span>
                </div>
            @endif
        </div>

        <div class="footer-note">
            هذه الصفحة تحقّق فقط من صحة بيانات الفاتورة الأساسية عبر رابط موقّع من النظام، ولا تعرض تفاصيل الدفعات أو
            بيانات المشترك.
        </div>
    </div>
</body>

</html>
