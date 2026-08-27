<?php

namespace App\Enums;

enum AiChatMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
