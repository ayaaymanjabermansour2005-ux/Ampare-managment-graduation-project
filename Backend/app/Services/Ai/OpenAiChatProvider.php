<?php

namespace App\Services\Ai;

use App\Contracts\AiChatProviderContract;
use App\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class OpenAiChatProvider implements AiChatProviderContract
{
    public function reply(array $messages, string $systemPrompt): string
    {
        $baseUrl = config('services.ai.openai.base_url', 'https://api.openai.com/v1/chat/completions');

        $messages = array_map(fn (array $message) => [
            'role' => $message['role'],
            'content' => $this->formatContent($message['content']),
        ], $messages);

        $response = Http::withToken(config('services.ai.openai.key'))
            ->timeout(20)
            ->post($baseUrl, [
                'model' => config('services.ai.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ...$messages,
                ],
                'max_tokens' => 700,
            ]);

        if ($response->failed()) {
            throw new AiProviderException('OpenAI-compatible request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! $content) {
            throw new AiProviderException('OpenAI-compatible provider returned an empty response.');
        }

        return $content;
    }

    /**
     * @param  string|array  $content
     * @return string|array
     */
    private function formatContent($content)
    {
        if (! is_array($content)) {
            return $content;
        }

        return array_map(fn (array $part) => $part['type'] === 'image'
            ? [
                'type' => 'image_url',
                'image_url' => ['url' => "data:{$part['mime_type']};base64,{$part['data']}"],
            ]
            : ['type' => 'text', 'text' => $part['text']], $content);
    }
}
