<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\DocumentValidationRules;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UploadDocument
{
    use DocumentValidationRules;

    public function handle(User $actor, string $title, UploadedFile $file): Document
    {
        Gate::forUser($actor)->authorize('create', Document::class);

        $organizationId = EnsureActiveOrganization::id();

        abort_unless(is_string($organizationId), 403);

        $mimeType = $this->detectedMimeType($file);

        if (! $this->isAllowedDocumentMime($mimeType)) {
            throw ValidationException::withMessages([
                'file' => __('This file type is not allowed.'),
            ]);
        }

        $disk = $this->documentDisk();
        $directory = 'documents/'.$organizationId;
        $filename = (string) Str::uuid();
        $storageKey = $file->storeAs($directory, $filename, [
            'disk' => $disk,
            'visibility' => 'private',
        ]);

        if (! is_string($storageKey) || $storageKey === '') {
            throw new RuntimeException('Failed to store the uploaded document.');
        }

        try {
            return DB::transaction(function () use ($actor, $title, $file, $organizationId, $disk, $storageKey, $mimeType): Document {
                $document = Document::query()->create([
                    'organization_id' => $organizationId,
                    'title' => $title,
                    'created_by' => $actor->id,
                ]);

                DocumentVersion::query()->create([
                    'organization_id' => $organizationId,
                    'document_id' => $document->id,
                    'version_number' => 1,
                    'original_filename' => $this->originalFilename($file),
                    'mime_type' => $mimeType,
                    'size_bytes' => $file->getSize() ?: 0,
                    'disk' => $disk,
                    'storage_key' => $storageKey,
                    'checksum' => $this->checksum($file),
                    'uploaded_by' => $actor->id,
                ]);

                return $document->load('currentVersion');
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($storageKey);

            throw $exception;
        }
    }

    private function originalFilename(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));

        return $name !== '' ? $name : 'download';
    }

    private function checksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (is_string($path) && $path !== '' && is_file($path)) {
            $hash = hash_file('sha256', $path);

            if (is_string($hash)) {
                return $hash;
            }
        }

        return hash('sha256', $file->getContent());
    }
}
