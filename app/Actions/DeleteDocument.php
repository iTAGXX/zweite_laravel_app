<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DeleteDocument
{
    public function handle(User $actor, Document $document): void
    {
        Gate::forUser($actor)->authorize('delete', $document);

        $versions = $document->versions()->get();

        DB::transaction(function () use ($document, $versions): void {
            $document->delete();

            foreach ($versions as $version) {
                Storage::disk($version->disk)->delete($version->storage_key);
            }
        });
    }
}
