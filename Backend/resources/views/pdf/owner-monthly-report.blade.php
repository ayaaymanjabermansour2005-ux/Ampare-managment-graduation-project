<h1>التقرير الشهري — {{ $owner->name }}</h1>
<p>الفترة: {{ $period }}</p>
<table border="1" cellpadding="6" style="width:100%">
    <tr><td>الإيرادات (شيكل)</td><td>{{ number_format($revenue_ils, 2) }}</td></tr>
    <tr><td>عمولة المنصة</td><td>{{ number_format($commission_ils, 2) }}</td></tr>
    <tr><td>صافي الإيراد</td><td>{{ number_format($net_revenue_ils, 2) }}</td></tr>
    <tr><td>عدد الأعطال المبلَّغة</td><td>{{ $faults_count }}</td></tr>
    <tr><td>الوقود المستهلَك (لتر)</td><td>{{ number_format($fuel_liters, 2) }}</td></tr>
    <tr><td>تكلفة الوقود (شيكل)</td><td>{{ number_format($fuel_cost_ils, 2) }}</td></tr>
    <tr><td>عدد المولدات</td><td>{{ $generators_count }}</td></tr>
</table>