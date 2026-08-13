<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ArchivePerson
{
    public function handle(User $actor, Person $person): Person
    {
        Gate::forUser($actor)->authorize('archive', $person);

        $person->archive();

        return $person->refresh();
    }
}
