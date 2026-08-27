<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group 	  				المرفقات
 */
class AttachmentController extends Controller
{
    use ApiResponse;

    public function show(Attachment $attachment): JsonResponse
    {
        $this->authorize('view', $attachment);

        return $this->success(
            message: 'بيانات المرفق.',
            data: new AttachmentResource($attachment->load('uploader'))
        );
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        abort_unless(
            Storage::disk($attachment->disk)->exists($attachment->path),
            404,
            'الملف غير موجود على الخادم.'
        );

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    public function preview(Attachment $attachment): Response
    {
        $this->authorize('view', $attachment);

        abort_unless(
            Storage::disk($attachment->disk)->exists($attachment->path),
            404,
            'الملف غير موجود على الخادم.'
        );

        $fileContents = Storage::disk($attachment->disk)->get($attachment->path);

        return response($fileContents, 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->authorize('delete', $attachment);

        $attachment->delete();

        return $this->success(message: 'تم حذف المرفق بنجاح.');
    }
}
