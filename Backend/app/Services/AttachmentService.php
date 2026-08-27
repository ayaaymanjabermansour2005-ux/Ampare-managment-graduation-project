<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public function upload(
        Model $model,
        UploadedFile $file,
        string $documentType,
        ?User $user = null,
        ?string $description = null,
        bool $isPublic = false,
        ?string $disk = null
    ): Attachment {

        $disk ??= config('attachments.disk', 'attachments');

        $this->validateFile($file);

        $folder = strtolower(class_basename($model));
        $extension = $file->extension();
        $storedName = Str::uuid().'.'.$extension;

        $path = $file->storeAs(
            "attachments/{$folder}",
            $storedName,
            $disk
        );

        return $model->attachments()->create([
            'uploaded_by' => $user?->id,
            'document_type' => $documentType,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => $disk,
            'path' => $path,
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $description,
            'is_public' => $isPublic,
        ]);
    }

    public function uploadMany(
        Model $model,
        array $files,
        string $documentType,
        ?User $user = null,
        ?string $description = null,
        bool $isPublic = false,
        ?string $disk = null
    ): void {
        foreach ($files as $file) {
            $this->upload(
                model: $model,
                file: $file,
                documentType: $documentType,
                user: $user,
                description: $description,
                isPublic: $isPublic,
                disk: $disk
            );
        }
    }

    public function storeMany(Model $model, array $files, ?User $user = null): void
    {
        $this->uploadMany(
            model: $model,
            files: $files,
            documentType: DocumentType::PaymentReceipt->value,
            user: $user,
        );
    }

    public function delete(Attachment $attachment): void
    {
        $attachment->delete();
    }

    public function forceDelete(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->forceDelete();
    }

    private function validateFile(UploadedFile $file): void
    {
        $maxKb = config('attachments.max_size_kb');
        $allowedMimes = config('attachments.allowed_mimes');

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => ["حجم الملف يتجاوز الحد المسموح ({$maxKb} كيلوبايت)."],
            ]);
        }

        if (! in_array($file->getMimeType(), $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => ['نوع الملف غير مدعوم.'],
            ]);
        }
    }
}
