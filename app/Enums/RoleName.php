<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Treasurer = 'treasurer';
    case Member = 'member';
    case Staff = 'staff';
}
