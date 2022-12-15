<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveLimitEnum extends Enum
{
    // 病假
    const SICK = 30;
    // 事假
    const SIMPLE = 14;
    // 生理假
    const PERIOD = 12;
    // 喪假
    const FUNERAL = 0;
    // 工傷病假
    const INJURY = 0;
    // 產假
    const MATERNITY = 0;
    // 安胎休養假
    const TOCOLYSIS = 30;
    // 陪產假
    const PATERNITY = 7;
    // 產檢假
    const PRENTAL = 7;
    // 家庭照顧假
    const FAMILYCARE = 7;
    // 公假
    const OFFICIAL = 0;
    // 特休
    const SPECIAL = 0;
}
