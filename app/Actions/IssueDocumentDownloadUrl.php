<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\DocumentValidationRules;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class IssueDocumentDownloadUrl
{
    use DocumentValidationRules;

    public function handle(User $actor, Document $document): string
    {
        Gate::forUser($actor)->authorize('view', $document);

        abort_unless($document->currentVersion !== null, 403);

        return URL::temporarySignedRoute(
            'documents.download',
            now()->addMinutes($this->temporaryUrlMinutes()),
            ['document' => $document->id],
        );
    }
}
