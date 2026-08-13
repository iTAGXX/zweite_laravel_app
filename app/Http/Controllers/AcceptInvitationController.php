<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcceptInvitationController extends Controller
{
    public function __invoke(Request $request, string $invitation, string $token, AcceptInvitation $acceptInvitation): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $acceptInvitation->handle($user, $invitation, $token);

        return redirect()->route('dashboard');
    }
}
