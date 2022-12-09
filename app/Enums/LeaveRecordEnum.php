<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeaveRecordEnum extends Enum
{
    const Type_SimpleLeave = 0;
    const Type_CompanyLeave = 1;
    const Type_SpecialLeave = 2;
    const Type_SickLeave = 3;

    const Status_Apply = 0;
    const Status_Allow = 1;
    const Status_Denied = 2;
}
