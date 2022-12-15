<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveTypesEnum extends Enum
{
    // 病假
    const SICK = 0;
    // 事假
    const SIMPLE = 1;
    // 生理假
    const PERIOD = 2;
    // 喪假
    const FUNERAL = 3;
    // 工傷病假
    const INJURY = 4;
    // 產假
    const MATERNITY = 5;
    // 安胎休養假
    const TOCOLYSIS = 6;
    // 陪產假
    const PATERNITY = 7;
    // 產檢假
    const PRENTAL = 8;
    // 家庭照顧假
    const FAMILYCARE = 9;
    // 公假
    const OFFICIAL = 10;
    // 特休
    const SPECIAL = 11;
}
