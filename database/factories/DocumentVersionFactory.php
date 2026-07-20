<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version' => 1,
            'file_path' => 'documents/test-file-' . $this->faker->uuid() . '.txt',
            'file_name' => 'test-file.txt',
            'file_size' => 1024,
            'mime_type' => 'text/plain',
        ];
    }
}
