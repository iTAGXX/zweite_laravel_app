<?php

declare(strict_types=1);

use App\Actions\IssueDocumentDownloadUrl;
use App\Enums\RoleName;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Storage::fake(config('documents.disk'));
});

test('documents and versions use the belongs to organization scope', function () {
    expect(class_uses_recursive(Document::class))->toContain(BelongsToOrganization::class)
        ->and(class_uses_recursive(DocumentVersion::class))->toContain(BelongsToOrganization::class);
});

test('queries without an active organization return no documents', function () {
    Document::factory()->withStoredFile()->create();

    EnsureActiveOrganization::forget();

    expect(Document::query()->count())->toBe(0)
        ->and(DocumentVersion::query()->count())->toBe(0);
});

test('metadata stays in the database and bytes stay on the storage disk', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $disk = config('documents.disk');

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::documents.create')
        ->set('title', 'Stable contract')
        ->set('file', UploadedFile::fake()->createWithContent('contract.pdf', "%PDF-1.4\n%%EOF"))
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('title', 'Stable contract')->first();

    expect($document)->not->toBeNull()
        ->and($document?->organization_id)->toBe($organization->id)
        ->and($document?->currentVersion)->not->toBeNull();

    $version = $document?->currentVersion;

    expect($version)->not->toBeNull()
        ->and($version?->original_filename)->toBe('contract.pdf')
        ->and($version?->mime_type)->toBe('application/pdf')
        ->and($version?->disk)->toBe($disk)
        ->and($version?->storage_key)->not->toContain('contract.pdf')
        ->and(Str::isUuid(basename((string) $version?->storage_key)))->toBeTrue()
        ->and(Schema::hasColumn('documents', 'content'))->toBeFalse()
        ->and(Schema::hasColumn('document_versions', 'content'))->toBeFalse()
        ->and(Schema::hasColumn('document_versions', 'bytes'))->toBeFalse();

    Storage::disk($disk)->assertExists((string) $version?->storage_key);
});

test('upload rejects disallowed mime types', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::documents.create')
        ->set('title', 'Malicious script')
        ->set('file', UploadedFile::fake()->create('shell.php', 20, 'application/x-php'))
        ->call('save')
        ->assertHasErrors(['file']);

    Livewire::test('pages::documents.create')
        ->set('title', 'Embedded page')
        ->set('file', UploadedFile::fake()->create('page.html', 20, 'text/html'))
        ->call('save')
        ->assertHasErrors(['file']);

    expect(Document::query()->count())->toBe(0);
});

test('upload rejects files that exceed the size whitelist', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $maxKilobytes = (int) config('documents.max_kilobytes');

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::documents.create')
        ->set('title', 'Huge scan')
        ->set('file', UploadedFile::fake()->create('scan.pdf', $maxKilobytes + 1, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['file']);

    expect(Document::query()->count())->toBe(0);
});

test('a signed download uses attachment and is not executable inline', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $document = Document::factory()->for($organization)->createdBy($admin)->withStoredFile()->create();

    $this->actingAs($admin);

    $url = app(IssueDocumentDownloadUrl::class)->handle($admin, $document);

    $response = $this->get($url);

    $response->assertOk();

    $disposition = (string) $response->headers->get('content-disposition');

    expect($disposition)->toContain('attachment')
        ->and(Str::lower($disposition))->not->toContain('inline');
});

test('a foreign tenant receives 403 when downloading another organizations file', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    setActiveOrganization($organizationA->id);

    $document = Document::factory()->for($organizationA)->createdBy($userA)->withStoredFile(
        contents: 'secret-tenant-bytes',
        filename: 'secret.pdf',
    )->create();

    $url = URL::temporarySignedRoute(
        'documents.download',
        now()->addMinutes(5),
        ['document' => $document->id],
    );

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($userB)
        ->get($url)
        ->assertForbidden()
        ->assertDontSee('secret-tenant-bytes')
        ->assertDontSee('secret.pdf');
});

test('eloquent cannot read another organizations document', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    $documentA = Document::factory()->for($organizationA)->withStoredFile()->create();

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $documentB = Document::factory()->for($organizationB)->withStoredFile()->create();

    setActiveOrganization($organizationA->id);

    expect(Document::query()->find($documentA->id))->not->toBeNull()
        ->and(Document::query()->find($documentB->id))->toBeNull()
        ->and(Document::query()->count())->toBe(1)
        ->and(DocumentVersion::query()->count())->toBe(1);
});

test('staff cannot open documents pages', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();

    $this->actingAs($staff)
        ->get(route('documents.index'))
        ->assertForbidden();

    $this->actingAs($staff)
        ->get(route('documents.create'))
        ->assertForbidden();
});

test('unsigned download urls are rejected', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $document = Document::factory()->for($organization)->createdBy($admin)->withStoredFile()->create();

    $this->actingAs($admin)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

test('admins can delete a document and its stored bytes', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $disk = config('documents.disk');
    setActiveOrganization($organization->id);

    $document = Document::factory()->for($organization)->createdBy($admin)->withStoredFile()->create();
    $storageKey = $document->currentVersion?->storage_key;

    expect($storageKey)->toBeString();

    $this->actingAs($admin);

    Livewire::test('pages::documents.index')
        ->call('delete', $document->id)
        ->assertHasNoErrors();

    EnsureActiveOrganization::forget();
    setActiveOrganization($organization->id);

    expect(Document::query()->find($document->id))->toBeNull();

    Storage::disk($disk)->assertMissing((string) $storageKey);
});

test('documents index is displayed for admins', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get(route('documents.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::documents.index')
        ->assertSee(__('Documents'));
});

test('guests are redirected from documents to login', function () {
    $this->get(route('documents.index'))->assertRedirect(route('login'));
});
