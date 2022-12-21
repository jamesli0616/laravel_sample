<?php

namespace App\Presenters;

use App\Enums\LeaveStatusEnum;
use App\Enums\LeaveTypesEnum;
use App\Enums\LeaveTimeEnum;
use App\Enums\LeaveMinimumEnum;

class LeaveRecordsPresenter
{
    public function leaveType($leaveType)
    {
        switch($leaveType) {
        case LeaveTypesEnum::SIMPLE:
            return '事假';
        case LeaveTypesEnum::OFFICIAL:
            return '公假';
        case LeaveTypesEnum::SPECIAL:
            return '特休(~3/31)';
        case LeaveTypesEnum::SICK:
            return '病假';
        case LeaveTypesEnum::PERIOD:
            return '生理假';
        case LeaveTypesEnum::FUNERAL:
            return '喪假';
        case LeaveTypesEnum::PATERNITY:
            return '陪產假';
        case LeaveTypesEnum::PRENTAL:
            return '產檢假';
        case LeaveTypesEnum::FAMILYCARE:
            return '家庭照顧假';
        case LeaveTypesEnum::INJURY:
            return '工傷病假';
        case LeaveTypesEnum::MATERNITY:
            return '產假';
        case LeaveTypesEnum::TOCOLYSIS:
            return '安胎休養假';
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

    public function leaveDays($leaveHours)
    {
        return $leaveHours / LeaveMinimumEnum::FULLDAY;
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