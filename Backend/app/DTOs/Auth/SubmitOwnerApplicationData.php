<?php

namespace App\DTOs\Auth;

use Illuminate\Http\UploadedFile;

final readonly class SubmitOwnerApplicationData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone,
        public ?string $notes,

        public string $generatorName,
        public float $generatorPricePerKw,
        public string $generatorCurrency,
        public ?int $generatorCapacityKw,
        public string $generatorCity,
        public ?int $generatorNeighborhoodId,
        public ?string $generatorAddress,
        public ?float $generatorLatitude,
        public ?float $generatorLongitude,

        /**
         * @var array{
         *     id_document: UploadedFile,
         *     business_license: UploadedFile,
         *     generator_photo: UploadedFile,
         *     ownership_contract: UploadedFile
         * }
         */
        public array $documents,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'] ?? null,
            notes: $data['notes'] ?? null,
            generatorName: $data['generator_name'],
            generatorPricePerKw: (float) $data['generator_price_per_kw'],
            generatorCurrency: $data['generator_currency'] ?? 'ILS',
            generatorCapacityKw: isset($data['generator_capacity_kw']) ? (int) $data['generator_capacity_kw'] : null,
            generatorCity: $data['generator_city'],
            generatorNeighborhoodId: isset($data['generator_neighborhood_id']) ? (int) $data['generator_neighborhood_id'] : null,
            generatorAddress: $data['generator_address'] ?? null,
            generatorLatitude: isset($data['generator_latitude']) ? (float) $data['generator_latitude'] : null,
            generatorLongitude: isset($data['generator_longitude']) ? (float) $data['generator_longitude'] : null,
            documents: $data['documents'] ?? [],
        );
    }
}
