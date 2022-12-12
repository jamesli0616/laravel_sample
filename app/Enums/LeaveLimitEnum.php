<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveLimitEnum extends Enum
{
    const SIMPLE = 2;
    const COMPANY = 9;
    const SPECIAL = 5;
    const SICK = 3;
}
