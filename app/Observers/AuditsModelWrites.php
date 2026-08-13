<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\AuditLogger;
use App\Models\Invitation;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditsModelWrites
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function created(Invitation|OrganizationMembership $model): void
    {
        $this->record($model, $this->actionName($model, 'created'));
    }

    public function updated(Invitation|OrganizationMembership $model): void
    {
        if ($model instanceof OrganizationMembership) {
            if (! $model->wasChanged('role_id')) {
                return;
            }

            $this->record($model, 'membership.role_changed', [
                'user_id' => $model->user_id,
                'from_role_id' => $model->getOriginal('role_id'),
                'to_role_id' => $model->role_id,
            ]);

            return;
        }

        $this->record($model, $this->actionName($model, 'updated'));
    }

    public function deleted(Invitation|OrganizationMembership $model): void
    {
        $this->record($model, $this->actionName($model, 'deleted'));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(Model $model, string $action, array $metadata = []): void
    {
        $actor = Auth::user();

        $organizationId = $model->getAttribute('organization_id');

        $this->auditLogger->handle(
            action: $action,
            actor: $actor instanceof User ? $actor : null,
            organizationId: is_string($organizationId) ? $organizationId : null,
            subject: $model,
            metadata: $metadata === [] ? $this->defaultMetadata($model) : $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultMetadata(Model $model): array
    {
        $payload = [];

        foreach (['email', 'role_id', 'user_id', 'name', 'slug'] as $key) {
            $value = $model->getAttribute($key);

            if ($value !== null) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function actionName(Invitation|OrganizationMembership $model, string $event): string
    {
        $resource = $model instanceof Invitation ? 'invitation' : 'membership';

        return $resource.'.'.$event;
    }
}
