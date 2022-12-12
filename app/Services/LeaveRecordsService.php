<?php

namespace App\Services;

use App\Repositories\LeaveRecordsRepository;
use App\Enums\LeaveLimitEnum;
use App\Enums\LeaveTypesEnum;

class LeaveRecordsService
{
    protected $LeaveRecordsRepository;

    public function __construct(
        LeaveRecordsRepository $LeaveRecordsRepository
    )
	{
        $this->LeaveRecordsRepository = $LeaveRecordsRepository;
	}

    public function getLeaveRecordsByYear(int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByYear($year)->get(),
            'leaveCalendarYears' => $this->LeaveRecordsRepository->getLeaveRecordsYears()->get(),
            'leaveRecordYear' => $year
        ];
    }

    public function getLeaveRecordsByUserID(int $uid, int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByUserID($uid, $year)->get(),
            'leaveCalendarYears' => $this->LeaveRecordsRepository->getLeaveRecordsYearsByUserID($uid)->get(),
            'leaveRecordYear' => $year
        ];
    }

    public function createLeaveRecords(mixed $params)
    {
        $start_date = strtotime($params['start_date']);
        $end_date = strtotime($params['end_date']);

        if ( $start_date > $end_date ) {
            return [
                'status' => -1,
                'message' => '起始時間大於結束時間'
            ];
        }

        $isConflict = $this->LeaveRecordsRepository->getLeaveRecordConflict(
            $params['start_date'],
            $params['end_date'],
            $params['user_id']
        )->get()->count();
        if ( $isConflict != 0 ) {
            return [
                'status' => -1,
                'message' => '請假日期與其他假單重疊'
            ];
        }

        $period = ( $end_date - $start_date ) / 86400 + 1;
        if ( $params['start_hour'] == 14 ) {
            $period -= 0.5;
        }
        if ( $params['end_hour'] == 13 ) {
            $period -= 0.5;
        }
        $holidays = $this->LeaveRecordsRepository->getHloidaysInCalendar(
            $params['start_date'],
            $params['end_date']
        )->get()->count();
        $period -= $holidays;
        if( $period <= 0 ) {
            return [
                'status' => -1,
                'message' => '請假時間小於等於0'
            ];
        }
        
        $limitDays = 0;
        switch($params['type']) {
        case LeaveTypesEnum::SIMPLE:
            $limitDays = LeaveLimitEnum::SIMPLE;
            break;
        case LeaveTypesEnum::COMPANY:
            $limitDays = LeaveLimitEnum::COMPANY;
            break;
        case LeaveTypesEnum::SPECIAL:
            $limitDays = LeaveLimitEnum::SPECIAL;
            break;
        case LeaveTypesEnum::SICK:
            $limitDays = LeaveLimitEnum::SICK;
            break;
        }
        if( $limitDays - $period < 0 ) {
            return [
                'status' => -1,
                'message' => '請假超過時數上限'
            ];
        }


        $this->LeaveRecordsRepository->createLeaveRecords(
            $params['user_id'],
            $params['type'],
            $params['comment'],
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $period * 8
        );

        return [
            'status' => 0,
            'message' => '建立成功'
        ];
    }

    public function updateLeaveRecordsStatus(mixed $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecordsStatus($params['leave_id'], $params['valid_status']);

        return [
            'status' => 0,
            'message' => '修改假單狀態完成'
        ];
    }
}
