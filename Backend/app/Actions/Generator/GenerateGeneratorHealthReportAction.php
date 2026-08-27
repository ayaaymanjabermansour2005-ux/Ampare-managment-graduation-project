<?php

namespace App\Actions\Generator;

use App\Contracts\AiChatProviderContract;
use App\Enums\HealthRiskLevel;
use App\Exceptions\AiProviderException;
use App\Models\Fault;
use App\Models\Generator;
use App\Models\GeneratorHealthReport;
use App\Models\MeterReading;
use Carbon\Carbon;
use JsonException;

final class GenerateGeneratorHealthReportAction
{
    public function __construct(
        private readonly AiChatProviderContract $provider
    ) {}

    public function execute(Generator $generator, Carbon $periodStart, Carbon $periodEnd): GeneratorHealthReport
    {
        $faultsCount = Fault::where('generator_id', $generator->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $consumedKw = (float) MeterReading::whereHas('subscription', fn ($q) => $q->where('generator_id', $generator->id))
            ->whereBetween('reading_date', [$periodStart, $periodEnd])
            ->sum('consumed_kw');

        $lastDiagnostic = $generator->diagnosticReadings()->latest('reading_date')->first();

        $snapshot = [
            'faults_count' => $faultsCount,
            'total_consumed_kw' => $consumedKw,
            'last_diagnostic' => $lastDiagnostic?->only([
                'operating_hours',
                'temperature_celsius',
                'oil_level_percent',
                'load_percent',
            ]),
        ];

        if ($generator->installed_at !== null) {
            $snapshot['equipment_age'] = $generator->installed_at->diffForHumans(null, true);
        }

        if ($generator->next_service_due_at !== null) {
            $daysUntilDue = (int) Carbon::now()->diffInDays($generator->next_service_due_at, false);
            $snapshot['maintenance_status'] = $daysUntilDue < 0
                ? 'متأخر عن موعد الصيانة القادمة بـ '.abs($daysUntilDue).' يوم'
                : 'متبقٍ على موعد الصيانة القادمة '.$daysUntilDue.' يوم';
        }

        try {
            $rawReply = $this->provider->reply(
                [['role' => 'user', 'content' => json_encode($snapshot, JSON_UNESCAPED_UNICODE)]],
                $this->systemPrompt()
            );
            $parsed = $this->parseJsonReply($rawReply);
        } catch (AiProviderException|JsonException $e) {
            report($e);
            $parsed = [
                'risk_level' => 'medium',
                'summary' => 'تعذّر إجراء التحليل الآلي لهذه الفترة، يُنصح بمراجعة يدوية.',
                'recommendation' => null,
            ];
        }

        return GeneratorHealthReport::create([
            'generator_id' => $generator->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'risk_level' => HealthRiskLevel::tryFrom($parsed['risk_level'] ?? '')?->value ?? HealthRiskLevel::Medium->value,
            'summary' => $parsed['summary'] ?? 'لا يوجد ملخص.',
            'recommendation' => $parsed['recommendation'] ?? null,
            'input_snapshot' => $snapshot,
        ]);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
أنت نظام تحليل صحة مولدات كهربائية. ستستقبل بيانات إحصائية عن مولد خلال فترة زمنية
(عدد الأعطال، إجمالي الاستهلاك بالكيلوواط، آخر قراءة تشخيصية). لخّص حالة المولد العامة
بجملتين إلى ثلاث، وحدد مستوى الخطورة، ورشّح توصية عملية واحدة إن وُجدت.

أعد ردك بصيغة JSON فقط، بدون أي نص إضافي، بهذا الشكل بالضبط:
{"risk_level": "low|medium|high", "summary": "...", "recommendation": "..."}
PROMPT;
    }

    private function parseJsonReply(string $raw): array
    {
        $clean = preg_replace('/^```json|```$/m', '', trim($raw));

        return json_decode(trim($clean), true, flags: JSON_THROW_ON_ERROR);
    }
}
