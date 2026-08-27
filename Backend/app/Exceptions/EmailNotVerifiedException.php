<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class EmailNotVerifiedException extends Exception
{
    public function __construct(
        string $message = 'يجب توثيق بريدك الإلكتروني قبل تسجيل الدخول. تحقق من صندوق الوارد.'
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'يرجى تفعيل بريدك الإلكتروني قبل تسجيل الدخول.',
            'data' => null,
            'errors' => ['code' => 'EMAIL_NOT_VERIFIED'],
        ], 403);
    }
}
