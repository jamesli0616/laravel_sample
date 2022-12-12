<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveStatusEnum extends Enum
{
    const APPLY = 0;
    const ALLOW = 1;
    const DENIED = 2;
}
