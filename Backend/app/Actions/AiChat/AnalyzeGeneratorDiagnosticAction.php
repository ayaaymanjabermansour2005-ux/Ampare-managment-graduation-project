<?php

namespace App\Actions\AiChat;

use App\Contracts\AiChatProviderContract;
use App\Enums\FaultPredictionSource;
use App\Enums\FaultPredictionStatus;
use App\Exceptions\AiProviderException;
use App\Models\FaultPrediction;
use App\Models\Generator;
use App\Models\GeneratorDiagnosticReading;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use JsonException;

final class AnalyzeGeneratorDiagnosticAction
{
    public function __construct(
        private readonly AiChatProviderContract $provider
    ) {}

    public function execute(GeneratorDiagnosticReading $reading): FaultPrediction
    {
        if ($reading->faultPrediction()->exists()) {
            throw ValidationException::withMessages([
                'reading' => ['تم تحليل هذه القراءة مسبقًا.'],
            ]);
        }

        $reading->loadMissing('generator');

        $history = GeneratorDiagnosticReading::where('generator_id', $reading->generator_id)
            ->where('reading_date', '<=', $reading->reading_date)
            ->latest('reading_date')
            ->limit(5)
            ->get()
            ->reverse()
            ->values();

        try {
            $rawReply = $this->provider->reply(
                [['role' => 'user', 'content' => $this->formatReadingsForPrompt($history, $reading->generator)]],
                $this->systemPrompt()
            );

            $parsed = $this->parseJsonReply($rawReply);
        } catch (AiProviderException|JsonException $e) {
            report($e);
            $parsed = [
                'risk_percentage' => null,
                'predicted_fault_type' => null,
                'recommendation' => 'تعذّر إجراء التحليل حاليًا، يرجى المحاولة مرة أخرى بعد قليل.',
            ];
        }

        return FaultPrediction::create([
            'generator_id' => $reading->generator_id,
            'source' => FaultPredictionSource::SensorAnalysis,
            'generator_diagnostic_reading_id' => $reading->id,
            'prediction_type' => $parsed['predicted_fault_type'] ?? 'sensor_diagnosis',
            'confidence' => $parsed['risk_percentage'],
            'recommendation' => $parsed['recommendation'] ?? '—',
            'input_snapshot' => $history->map(fn (GeneratorDiagnosticReading $r) => $r->only([
                'operating_hours',
                'temperature_celsius',
                'oil_level_percent',
                'load_percent',
                'voltage',
                'frequency_hz',
                'smoke_level',
                'vibration_level',
                'reading_date',
            ]))->all(),
            'is_actual_fault' => false,
            'status' => FaultPredictionStatus::Pending->value,
        ]);
    }

    private function formatReadingsForPrompt(Collection $readings, ?Generator $generator = null): string
    {
        $lines = $readings->map(function (GeneratorDiagnosticReading $r) {
            return "التاريخ: {$r->reading_date->toDateString()} | ساعات التشغيل: {$r->operating_hours} | "
                ."الحرارة: {$r->temperature_celsius}°م | مستوى الزيت: {$r->oil_level_percent}% | "
                ."الحمل: {$r->load_percent}% | الجهد: {$r->voltage}V | التردد: {$r->frequency_hz}Hz | "
                ."الدخان: {$r->smoke_level?->value} | الاهتزاز: {$r->vibration_level?->value}";
        })->implode("\n");

        $baseline = $this->formatRatedBaseline($generator);

        return "{$baseline}بيانات القراءات الأخيرة للمولد (من الأقدم للأحدث):\n{$lines}\n\nحلّل هذه البيانات وأعطني تقييمك.";
    }

    /**
     * سطر مرجعي بالقيم المقنَّنة (Rated) للمولد — لو متوفرة — ليقارن النموذج
     * القراءات الفعلية عليها بدل الاعتماد على أرقام مجردة بلا نقطة مرجعية.
     */
    private function formatRatedBaseline(?Generator $generator): string
    {
        if (! $generator) {
            return '';
        }

        $parts = [];
        if ($generator->rated_voltage !== null) {
            $parts[] = "الجهد المقنن: {$generator->rated_voltage}V";
        }
        if ($generator->rated_frequency_hz !== null) {
            $parts[] = "التردد المقنن: {$generator->rated_frequency_hz}Hz";
        }
        if ($generator->rated_load_kw !== null) {
            $parts[] = "الحمل المقنن: {$generator->rated_load_kw}kW";
        }

        if (empty($parts)) {
            return '';
        }

        return 'القيم المرجعية المقنَّنة للمولد (للمقارنة مع القراءات أدناه): '.implode(' | ', $parts)."\n\n";
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
أنت نظام تحليل تقني متخصص بتشخيص أعطال مولدات الديزل الكهربائية بناءً على قراءات تشغيلية رقمية
(ساعات تشغيل، حرارة، مستوى زيت، حمل، جهد، تردد، دخان، اهتزاز).

مهمتك تحليل سلسلة القراءات المرسلة وإرجاع تقييم بصيغة JSON صارمة فقط، بدون أي نص إضافي قبلها أو بعدها،
بالشكل التالي بالضبط:
{"risk_percentage": رقم من 0 إلى 100, "predicted_fault_type": "نوع العطل المتوقع بالعربي أو null إن لم يوجد خطر", "recommendation": "توصية عملية مختصرة بالعربي"}

لا تُرجع أي شرح أو نص خارج بنية الـ JSON هذه إطلاقًا.
PROMPT;
    }

    private function parseJsonReply(string $raw): array
    {
        $clean = preg_replace('/^```json|```$/m', '', trim($raw));
        $decoded = json_decode(trim($clean), true, flags: JSON_THROW_ON_ERROR);

        return [
            'risk_percentage' => isset($decoded['risk_percentage']) ? (float) $decoded['risk_percentage'] : null,
            'predicted_fault_type' => $decoded['predicted_fault_type'] ?? null,
            'recommendation' => $decoded['recommendation'] ?? null,
        ];
    }
}
