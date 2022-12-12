<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveTypesEnum extends Enum
{
    const SIMPLE = 0;
    const COMPANY = 1;
    const SPECIAL = 2;
    const SICK = 3;
}
