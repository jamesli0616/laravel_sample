<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveLimitEnum extends Enum
{
    // 喪假
    // 產假
    // 公假
    // 工傷病假
    // 特休
    // 無上限假
    const INFINITE = 0;
    // 病假
    const SICK = 30;
    // 事假
    const SIMPLE = 14;
    // 生理假
    const PERIOD = 12;
    // 安胎休養假
    const TOCOLYSIS = 30;
    // 陪產假
    const PATERNITY = 7;
    // 產檢假
    const PRENTAL = 7;
    // 家庭照顧假
    const FAMILYCARE = 7;
    // 特休
    const SPECIAL = -1;
}
