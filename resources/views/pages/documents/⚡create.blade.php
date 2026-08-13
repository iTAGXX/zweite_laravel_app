<?php

use App\Actions\UploadDocument;
use App\Concerns\DocumentValidationRules;
use App\Models\Document;
use App\Models\User;
use Flux\Flux;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Upload document')] class extends Component
{
    use DocumentValidationRules, WithFileUploads;

    public string $title = '';

    public mixed $file = null;

    public function mount(): void
    {
        $this->authorize('create', Document::class);
    }

    public function save(UploadDocument $uploadDocument): void
    {
        $this->authorize('create', Document::class);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $validated = $this->validate($this->documentUploadRules());

        $file = $this->file;

        abort_unless($file instanceof UploadedFile, 403);

        $uploadDocument->handle($user, $validated['title'], $file);

        Flux::toast(variant: 'success', text: __('Document uploaded.'));

        $this->redirect(route('documents.index'), navigate: true);
    }
}; ?>

<section class="flex w-full min-w-0 flex-col gap-6">
    <div class="flex flex-col gap-2">
        <flux:heading>{{ __('Upload document') }}</flux:heading>
        <flux:subheading>{{ __('Files are stored privately per organization. Downloads use a short-lived signed URL.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-lg space-y-6" data-test="document-form">
        <x-ui.form-errors />

        <flux:input wire:model="title" :label="__('Title')" required />

        <flux:field>
            <flux:label>{{ __('File') }}</flux:label>
            <input
                type="file"
                wire:model="file"
                class="block w-full min-h-11 text-sm text-zinc-800 file:me-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-4 file:text-sm file:font-medium dark:text-zinc-200 dark:file:bg-zinc-700"
                data-test="document-file-input"
            />
            <flux:error name="file" />
        </flux:field>

        <div class="flex flex-col gap-3 sm:flex-row">
            <flux:button variant="primary" type="submit" class="min-h-11" data-test="save-document-button">
                {{ __('Save') }}
            </flux:button>
            <flux:button :href="route('documents.index')" variant="ghost" wire:navigate class="min-h-11">
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
