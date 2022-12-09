<?php

namespace App\Presenters;

use App\Enums\LeaveRecordEnum;

class LeaveRecordsPresenter
{
    public function leaveType($leaveType)
    {
        switch($leaveType) {
        case LeaveRecordEnum::Type_SimpleLeave:
            return '事假';
        case LeaveRecordEnum::Type_CompanyLeave:
            return '公假';
        case LeaveRecordEnum::Type_SpecialLeave:
            return '特休';
        case LeaveRecordEnum::Type_SickLeave:
            return '病假';
        }
    }

    public function leaveStatus($leaveStatus)
    {
        switch($leaveStatus) {
        case LeaveRecordEnum::Status_Apply:
            return '申請中';
        case LeaveRecordEnum::Status_Allow:
            return '許可';
        case LeaveRecordEnum::Status_Denied:
            return '拒絕';
        }
    }
}