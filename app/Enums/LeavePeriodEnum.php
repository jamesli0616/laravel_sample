<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeavePeriodEnum extends Enum
{
    // 1/1~12/31
    const SIMPLEYEAR = 0;
    // 4/1~3/31
    const JAPANYEAR = 1;
}
