<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'png', 'pdf']);
        $storedName = fake()->uuid().'.'.$extension;

        return [
            'uploaded_by' => User::factory(),
            'document_type' => fake()->randomElement(['receipt', 'photo', 'report', 'other']),
            'original_name' => fake()->word().'.'.$extension,
            'stored_name' => $storedName,
            'disk' => 'local',
            'path' => 'attachments/'.$storedName,
            'extension' => $extension,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : "image/{$extension}",
            'file_size' => fake()->numberBetween(10_000, 3_000_000),
            'description' => null,
            'is_public' => false,
        ];
    }

    public function image(): static
    {
        return $this->state(function () {
            $storedName = fake()->uuid().'.jpg';

            return [
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
                'original_name' => fake()->word().'.jpg',
                'stored_name' => $storedName,
                'path' => 'attachments/'.$storedName,
            ];
        });
    }

    public function pdf(): static
    {
        return $this->state(function () {
            $storedName = fake()->uuid().'.pdf';

            return [
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'original_name' => fake()->word().'.pdf',
                'stored_name' => $storedName,
                'path' => 'attachments/'.$storedName,
            ];
        });
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_public' => true]);
    }
}
