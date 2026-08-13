<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

class EnumerationSafeFailedPasswordResetLinkRequestResponse extends SuccessfulPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponse {}
