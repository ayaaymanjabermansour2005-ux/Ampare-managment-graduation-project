<?php

namespace App\Actions\AiChat;

use App\Contracts\AiChatProviderContract;
use App\Enums\AiChatMessageRole;
use App\Enums\DocumentType;
use App\Exceptions\AiProviderException;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Generator;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class SendAiChatMessageAction
{
    public function __construct(
        private readonly AiChatProviderContract $provider,
        private readonly AttachmentService $attachmentService
    ) {}

    public function execute(AiChatSession $session, string $userText, ?UploadedFile $file = null): AiChatMessage
    {
        $session->loadMissing('generator');

        return DB::transaction(function () use ($session, $userText, $file) {
            $userMessage = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => AiChatMessageRole::User,
                'content' => $userText,
            ]);

            if ($file) {
                $this->attachmentService->upload(
                    model: $userMessage,
                    file: $file,
                    documentType: DocumentType::ChatAttachment->value,
                    user: $session->user,
                );
            }

            $isImageAttachment = $file && str_starts_with($file->getMimeType(), 'image/');

            $history = $session->messages()
                ->get()
                ->map(function (AiChatMessage $m) use ($userMessage, $file, $isImageAttachment) {
                    if ($m->id === $userMessage->id && $isImageAttachment) {
                        return [
                            'role' => $m->role->value,
                            'content' => [
                                ['type' => 'text', 'text' => $m->content],
                                [
                                    'type' => 'image',
                                    'mime_type' => $file->getMimeType(),
                                    'data' => base64_encode($file->get()),
                                ],
                            ],
                        ];
                    }

                    return [
                        'role' => $m->role->value,
                        'content' => $m->content.($m->id === $userMessage->id && $file ? ' [مرفق ملف/صورة مع هذه الرسالة]' : ''),
                    ];
                })
                ->all();

            try {
                $replyText = $this->provider->reply($history, $this->systemPrompt($session));
            } catch (AiProviderException $e) {
                report($e);
                $replyText = 'تعذّر الوصول لخدمة المساعد الذكي حاليًا، يرجى المحاولة مرة أخرى بعد قليل.';
            }

            return AiChatMessage::create([
                'session_id' => $session->id,
                'role' => AiChatMessageRole::Assistant,
                'content' => $replyText,
            ]);
        });
    }

    private function systemPrompt(AiChatSession $session): string
    {
        if ($session->user?->isAdmin()) {
            return <<<'PROMPT'
أنت مساعد دعم ذكي لمنصة إدارة المولدات. ساعد مدير النظام في الإجابة عن الأسئلة العامة حول استخدام المنصة، إدارة المولدات، المشتركين، الفواتير، الأعطال، والإجراءات التشغيلية.

قواعد يجب الالتزام بها دائمًا:
- لا تدّعِ تنفيذ أي إجراء في النظام أو تعديل أي سجل.
- إذا احتاج السؤال إلى بيانات غير موجودة في المحادثة، اطلب التفاصيل المناسبة باختصار.
- اجعل الردود عربية، واضحة، وعملية.
PROMPT;
        }

        $name = $session->generator->name ?? 'غير محدد';
        $status = $session->generator->status?->value ?? 'غير معروفة';
        $specsLine = $this->generatorSpecsLine($session->generator);

        return <<<PROMPT
أنت مساعد ذكي متخصص بمولدات الكهرباء وأنظمة توزيع الطاقة الكهربائية بغزة.
مهمتك مساعدة المستخدم على وصف وفهم أعراض عطل محتمل بالمولد "{$name}" (حالته الحالية: {$status})،
وتقديم نصائح أولية للتشخيص.
{$specsLine}
قواعد يجب الالتزام بها دائمًا:
- لا تدّعِ أبدًا أنك سجّلت بلاغ عطل أو أنشأت أي سجل فعلي بالنظام — أنت لا تملك هذه الصلاحية إطلاقًا.
- إذا اقتنع المستخدم بوجود عطل حقيقي، وجّهه دائمًا لتقديم بلاغ رسمي عبر النظام ليتحقق منه مالك المولد.
- اجعل ردودك مختصرة وعملية ومناسبة لسياق المولدات الأهلية بغزة.
PROMPT;
    }

    /**
     * يبني سطرًا مختصرًا بالمواصفات الفنية المرجعية للمولد (القيم المُقنَّنة عند التصنيع)
     * ليقارن المساعد الذكي عليها أعراض العطل التي يصفها المستخدم — لا يُطبع أي حقل غير محدد.
     */
    private function generatorSpecsLine(?Generator $generator): string
    {
        if (! $generator) {
            return '';
        }

        $parts = [];

        $brand = trim(($generator->manufacturer ?? '').' '.($generator->model ?? ''));
        if ($brand !== '') {
            $parts[] = "الصانع/الموديل: {$brand}";
        }
        if ($generator->rated_voltage !== null) {
            $parts[] = "الجهد المقنن: {$generator->rated_voltage}V";
        }
        if ($generator->rated_frequency_hz !== null) {
            $parts[] = "التردد المقنن: {$generator->rated_frequency_hz}Hz";
        }
        if ($generator->phase_count !== null) {
            $parts[] = "عدد الأطوار: {$generator->phase_count}";
        }
        if ($generator->rated_load_kw !== null) {
            $parts[] = "الحمل المقنن: {$generator->rated_load_kw}kW";
        }

        if (empty($parts)) {
            return '';
        }

        return "\nالمواصفات الفنية المرجعية لهذا المولد (قيم مقنَّنة يُقاس عليها، وليست قراءات لحظية): "
            .implode(' | ', $parts)."\n";
    }
}
