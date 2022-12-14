<?php

namespace App\Presenters;

use App\Enums\LeaveStatusEnum;
use App\Enums\LeaveTypesEnum;
use App\Enums\LeaveTimeEnum;

class LeaveRecordsPresenter
{
    public function leaveType($leaveType)
    {
        switch($leaveType) {
        case LeaveTypesEnum::SIMPLE:
            return '事假';
        case LeaveTypesEnum::COMPANY:
            return '公假';
        case LeaveTypesEnum::SPECIAL:
            return '特休';
        case LeaveTypesEnum::SICK:
            return '病假';
        }
    }

    public function leaveStatus($leaveStatus)
    {
        switch($leaveStatus) {
        case LeaveStatusEnum::APPLY:
            return '申請中';
        case LeaveStatusEnum::ALLOW:
            return '許可';
        case LeaveStatusEnum::DENIED:
            return '拒絕';
        case LeaveStatusEnum::CANCELED:
            return '取消';
        }
    }

    public function leaveTime($leaveTime)
    {
        switch($leaveTime) {
        case LeaveTimeEnum::MORNING:
            return '上午';
        case LeaveTimeEnum::AFTERNOON:
            return '下午';
        }
    }
}