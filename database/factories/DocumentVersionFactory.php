<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $disk = config('documents.disk');
        $diskName = is_string($disk) && $disk !== '' ? $disk : 'local';

        return [
            'document_id' => Document::factory(),
            'version_number' => 1,
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(100, 50_000),
            'disk' => $diskName,
            'storage_key' => 'documents/'.Str::uuid().'/'.Str::uuid(),
            'checksum' => hash('sha256', fake()->sha256()),
            'uploaded_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (DocumentVersion $version): void {
            if ($version->organization_id !== '') {
                return;
            }

            $documentId = $version->document_id;

            if ($documentId === '') {
                return;
            }

            $organizationId = Document::withoutGlobalScopes()
                ->whereKey($documentId)
                ->value('organization_id');

            if (is_string($organizationId)) {
                $version->organization_id = $organizationId;
            }
        });
    }
}
