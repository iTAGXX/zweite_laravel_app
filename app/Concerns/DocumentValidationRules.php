<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

trait DocumentValidationRules
{
    /**
     * @return array<string, list<File|ValidationRule|string>>
     */
    protected function documentUploadRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                File::types($this->allowedDocumentExtensions())
                    ->max($this->maxDocumentKilobytes()),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function allowedDocumentExtensions(): array
    {
        $extensions = config('documents.allowed_extensions', []);

        if (! is_array($extensions)) {
            return [];
        }

        return array_values(array_filter($extensions, is_string(...)));
    }

    /**
     * @return list<string>
     */
    protected function allowedDocumentMimetypes(): array
    {
        $mimetypes = config('documents.allowed_mimetypes', []);

        if (! is_array($mimetypes)) {
            return [];
        }

        return array_values(array_filter($mimetypes, is_string(...)));
    }

    protected function maxDocumentKilobytes(): int
    {
        $max = config('documents.max_kilobytes', 10240);

        return is_numeric($max) ? (int) $max : 10240;
    }

    protected function documentDisk(): string
    {
        $disk = config('documents.disk');

        if (! is_string($disk) || $disk === '') {
            return 'local';
        }

        return $disk;
    }

    protected function temporaryUrlMinutes(): int
    {
        $minutes = config('documents.temporary_url_minutes', 5);

        return is_numeric($minutes) ? (int) $minutes : 5;
    }

    protected function detectedMimeType(UploadedFile $file): ?string
    {
        $detected = $file->getMimeType();

        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        $client = $file->getClientMimeType();

        return $client !== '' ? $client : null;
    }

    protected function isAllowedDocumentMime(?string $mimeType): bool
    {
        if ($mimeType === null) {
            return false;
        }

        return in_array($mimeType, $this->allowedDocumentMimetypes(), true);
    }
}
