<?php

namespace App\Services;

use App\Repositories\LeaveRecordsRepository;
use App\Repositories\CalendarRepository;
use App\Enums\LeaveLimitEnum;
use App\Enums\LeaveTypesEnum;

class LeaveRecordsService
{
    protected $LeaveRecordsRepository;
    protected $CalendarRepository;

    public function __construct(
        LeaveRecordsRepository $LeaveRecordsRepository,
        CalendarRepository $CalendarRepository
    )
	{
        $this->LeaveRecordsRepository = $LeaveRecordsRepository;
        $this->CalendarRepository = $CalendarRepository;
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
        // 請假起始結束時間判斷
        if ( $start_date > $end_date ) {
            return [
                'status' => -1,
                'message' => '起始時間大於結束時間'
            ];
        }
        // 請假時間重疊判斷
        $isConflict = $this->LeaveRecordsRepository->getLeaveRecordConflict(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $params['user_id']
        )->get()->count();
        if ( $isConflict != 0 ) {
            return [
                'status' => -1,
                'message' => '請假日期與其他假單重疊'
            ];
        }
        // 請假計算天數
        $period = ( $end_date - $start_date ) / 86400 + 1;
        if ( $params['start_hour'] == 14 ) {
            $period -= 0.5;
        }
        if ( $params['end_hour'] == 13 ) {
            $period -= 0.5;
        }
        // 請假起始或結束日期為假日
        $isHolidayStartDate = $this->CalendarRepository->getIsHolidayByDate($params['start_date'])->get()[0]['holiday'];
        $isHolidayEndDate = $this->CalendarRepository->getIsHolidayByDate($params['end_date'])->get()[0]['holiday'];
        if( $isHolidayStartDate == 2 ||  $isHolidayEndDate == 2 ) {
            return [
                'status' => -1,
                'message' => '請假起始或結束日為假日'
            ];
        }
        // 請假扣除假日判斷
        $holidays = $this->CalendarRepository->getHolidaysInCalendar(
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
        // 請假假別時數上限判斷
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
        $params['period'] = $period * 8;

        $this->LeaveRecordsRepository->createLeaveRecords($params);

        return [
            'status' => 0,
            'message' => '建立成功'
        ];
    }

    public function updateLeaveRecord(mixed $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecord($params['leave_id'], $params['valid_status']);

        return [
            'status' => 0,
            'message' => '修改假單狀態完成'
        ];
    }
}
