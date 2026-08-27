<?php

return [

    'invoice_due_days' => (int) env('BILLING_INVOICE_DUE_DAYS', 7),

    'commission_rate' => (float) env('BILLING_COMMISSION_RATE', 10),

    'overdue_grace_days' => (int) env('BILLING_OVERDUE_GRACE_DAYS', 3),

];
