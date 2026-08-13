<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentController extends Controller
{
    public function __invoke(Request $request, string $document): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $record = Document::query()->with('currentVersion')->find($document);

        abort_unless($record instanceof Document, 403);

        Gate::forUser($user)->authorize('view', $record);

        $version = $record->currentVersion;

        abort_unless($version instanceof DocumentVersion, 403);

        return Storage::disk($version->disk)->download(
            $version->storage_key,
            $version->original_filename,
            [
                'Content-Type' => $version->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
