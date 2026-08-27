<?php

namespace App\Services\Ai;

use App\Contracts\AiChatProviderContract;
use App\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class AnthropicChatProvider implements AiChatProviderContract
{
    public function reply(array $messages, string $systemPrompt): string
    {
        $messages = array_map(fn (array $message) => [
            'role' => $message['role'],
            'content' => $this->formatContent($message['content']),
        ], $messages);

        $response = Http::withHeaders([
            'x-api-key' => config('services.ai.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(20)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.ai.anthropic.model', 'claude-3-5-haiku-20241022'),
                'system' => $systemPrompt,
                'messages' => $messages,
                'max_tokens' => 700,
            ]);

        if ($response->failed()) {
            throw new AiProviderException('Anthropic request failed: '.$response->body());
        }

        $content = $response->json('content.0.text');

        if (! $content) {
            throw new AiProviderException('Anthropic returned an empty response.');
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
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $part['mime_type'],
                    'data' => $part['data'],
                ],
            ]
            : ['type' => 'text', 'text' => $part['text']], $content);
    }
}
