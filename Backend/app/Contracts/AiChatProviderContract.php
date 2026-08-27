<?php

namespace App\Contracts;

use App\Exceptions\AiProviderException;

interface AiChatProviderContract
{
    /**
     * @param  array<int, array{role: string, content: string|array}>  $messages
     *
     * $content is either a plain string (existing behaviour) or an array of content
     * parts for a message that carries an image, shaped like:
     * [['type' => 'text', 'text' => string], ['type' => 'image', 'mime_type' => string, 'data' => string (raw base64, no data: prefix)]]
     * Each provider translates this neutral internal shape to its own wire format.
     *
     * @throws AiProviderException
     */
    public function reply(array $messages, string $systemPrompt): string;
}
