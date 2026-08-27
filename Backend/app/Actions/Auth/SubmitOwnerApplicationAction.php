<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\SubmitOwnerApplicationData;
use App\Enums\DocumentType;
use App\Models\OwnerApplication;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SubmitOwnerApplicationAction
{
    public function __construct(private readonly AttachmentService $attachmentService) {}

    public function execute(SubmitOwnerApplicationData $data): OwnerApplication
    {
        return DB::transaction(function () use ($data) {
            $application = OwnerApplication::create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => Hash::make($data->password),
                'notes' => $data->notes,
                'generator_name' => $data->generatorName,
                'generator_price_per_kw' => $data->generatorPricePerKw,
                'generator_currency' => $data->generatorCurrency,
                'generator_capacity_kw' => $data->generatorCapacityKw,
                'generator_city' => $data->generatorCity,
                'generator_neighborhood_id' => $data->generatorNeighborhoodId,
                'generator_address' => $data->generatorAddress,
                'generator_latitude' => $data->generatorLatitude,
                'generator_longitude' => $data->generatorLongitude,
            ]);

            $documentTypeMap = [
                'id_document' => DocumentType::OwnerIdentityDocument,
                'business_license' => DocumentType::BusinessLicense,
                'generator_photo' => DocumentType::GeneratorPhoto,
                'ownership_contract' => DocumentType::GeneratorOwnershipContract,
            ];

            foreach ($documentTypeMap as $field => $documentType) {
                if (! isset($data->documents[$field])) {
                    continue;
                }

                $this->attachmentService->upload(
                    model: $application,
                    file: $data->documents[$field],
                    documentType: $documentType->value,
                );
            }

            return $application->fresh('attachments');
        });
    }
}
