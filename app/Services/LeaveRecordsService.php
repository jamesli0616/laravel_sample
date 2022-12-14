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

    // 整理請假紀錄所有年份
    protected function distinctYears(mixed $record_results)
    {
        $years_array = [];
        foreach($record_results as $rows) {
            if (!in_array(date_parse($rows['start_date'])['year'], $years_array)) {
                array_push($years_array, date_parse($rows['start_date'])['year']);
            }
        }
        return $years_array;
    }

    public function getLeaveRecordsByYear(int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByYear($year)->get(),
            'leaveCalendarYears' => $this->distinctYears($this->LeaveRecordsRepository->getLeaveRecords()->get()),
            'leaveRecordYear' => $year
        ];
    }

    public function getLeaveRecordsByUserID(int $uid, int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByYearAndUserID($uid, $year)->get(),
            'leaveCalendarYears' => $this->distinctYears($this->LeaveRecordsRepository->getLeaveRecordsByUserID($uid)->get()),
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
        $leave_record_date = $this->CalendarRepository->getCalendarByDateRange(
            $params['start_date'],
            $params['end_date']
        )->get();
        $period = $leave_record_date->count();
        if ( $params['start_hour'] == 14 ) {
            $period -= 0.5;
        }
        if ( $params['end_hour'] == 13 ) {
            $period -= 0.5;
        }
        // 請假起始或結束日期為假日
        if( $leave_record_date[0]['holiday'] == 2 || $leave_record_date[$period-1]['holiday'] == 2 ) {
            return [
                'status' => -1,
                'message' => '請假起始或結束日為假日'
            ];
        }
        // 請假扣除假日判斷
        foreach($leave_record_date as $rows) {
            if( $rows['holiday'] == 2 ) {
                $period--;
            }
        }
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
