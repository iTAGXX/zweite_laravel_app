<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(3),
            'created_by' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->id,
        ]);
    }

    public function withStoredFile(string $contents = '%PDF-1.4 test', string $filename = 'contract.pdf', string $mimeType = 'application/pdf'): static
    {
        return $this->afterCreating(function (Document $document) use ($contents, $filename, $mimeType): void {
            $disk = $this->documentDisk();
            $storageKey = 'documents/'.$document->organization_id.'/'.(string) Str::uuid();

            Storage::disk($disk)->put($storageKey, $contents, 'private');

            DocumentVersion::factory()->create([
                'organization_id' => $document->organization_id,
                'document_id' => $document->id,
                'version_number' => 1,
                'original_filename' => $filename,
                'mime_type' => $mimeType,
                'size_bytes' => strlen($contents),
                'disk' => $disk,
                'storage_key' => $storageKey,
                'checksum' => hash('sha256', $contents),
                'uploaded_by' => $document->created_by,
            ]);
        });
    }

    private function documentDisk(): string
    {
        $disk = config('documents.disk');

        if (! is_string($disk) || $disk === '') {
            return 'local';
        }

        return $disk;
    }
}
