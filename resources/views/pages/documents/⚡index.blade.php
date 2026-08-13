<?php

use App\Actions\DeleteDocument;
use App\Actions\IssueDocumentDownloadUrl;
use App\Models\Document;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Documents')] class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
    }

    public function download(string $documentId, IssueDocumentDownloadUrl $issueDocumentDownloadUrl): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $document = Document::query()->findOrFail($documentId);

        return redirect()->away($issueDocumentDownloadUrl->handle($user, $document));
    }

    public function delete(string $documentId, DeleteDocument $deleteDocument): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $document = Document::query()->findOrFail($documentId);

        $deleteDocument->handle($user, $document);

        Flux::toast(variant: 'success', text: __('Document deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, Document>
     */
    #[Computed]
    public function documents(): LengthAwarePaginator
    {
        return Document::query()
            ->with('currentVersion')
            ->latest()
            ->paginate(15);
    }
}; ?>

<section class="flex w-full min-w-0 flex-col gap-6" data-test="documents-index">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading>{{ __('Documents') }}</flux:heading>

        <flux:button
            :href="route('documents.create')"
            variant="primary"
            icon="plus"
            wire:navigate
            class="min-h-11"
            data-test="upload-document-button"
        >
            {{ __('Upload document') }}
        </flux:button>
    </div>

    @forelse ($this->documents as $document)
        <div wire:key="document-{{ $document->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" data-test="document-row">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <flux:heading size="sm">{{ $document->title }}</flux:heading>
                    <flux:text>
                        {{ $document->currentVersion?->original_filename ?? __('No file') }}
                        @if ($document->currentVersion)
                            · {{ number_format($document->currentVersion->size_bytes / 1024, 1) }} KB
                        @endif
                    </flux:text>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    @if ($document->currentVersion)
                        <flux:button
                            wire:click="download('{{ $document->id }}')"
                            variant="ghost"
                            class="min-h-11"
                            data-test="download-document-button"
                        >
                            {{ __('Download') }}
                        </flux:button>
                    @endif
                    <flux:button
                        wire:click="delete('{{ $document->id }}')"
                        wire:confirm="{{ __('Delete this document? The file will be removed from storage.') }}"
                        variant="danger"
                        class="min-h-11"
                        data-test="delete-document-button"
                    >
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @empty
        <x-ui.empty-state :heading="__('No documents yet')" :text="__('Upload a file to the tenant document store. Bytes stay in storage, metadata in the database.')" />
    @endforelse

    @if ($this->documents->hasPages())
        <flux:pagination :paginator="$this->documents" />
    @endif
</section>
