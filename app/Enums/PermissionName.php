<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionName: string
{
    case UsersManage = 'users.manage';
    case FinanceView = 'finance.view';
    case AuditView = 'audit.view';
}
