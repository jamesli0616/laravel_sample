<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use App\Repositories\LeaveRecordsRepository;
use App\Repositories\CalendarRepository;
use App\Enums\LeaveLimitEnum;
use App\Enums\LeaveTypesEnum;
use App\Enums\LeaveTimeEnum;
use App\Enums\HolidayEnum;

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
    protected function distinctYears(Collection $record_results)
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
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
                $year.'-01-01',
                $year.'-12-31'
            ),
            'leaveCalendarYears' => $this->distinctYears($this->LeaveRecordsRepository->getLeaveRecordsByDataRange()),
            'leaveRecordYear' => $year
        ];
    }

    public function getLeaveRecordsByUserID(int $uid, int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID(
                $uid,
                $year.'-01-01',
                $year.'-12-31'
            ),
            'leaveCalendarYears' => $this->distinctYears($this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID($uid)),
            'leaveRecordYear' => $year
        ];
    }

    public function createLeaveRecords(array $params)
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
        // 行事曆未建立請假日期
        $start_date_exists = $this->CalendarRepository->getCalendarByDateRange($params['start_date'], $params['start_date'])->count();
        $end_date_exists = $this->CalendarRepository->getCalendarByDateRange($params['end_date'], $params['end_date'])->count();
        if ( $start_date_exists == 0 || $end_date_exists == 0 ) {
            return [
                'status' => -1,
                'message' => '指定日期區間行事曆尚未建立'
            ];
        }
        // 請假時間重疊判斷
        $isConflict = $this->LeaveRecordsRepository->getLeaveRecordConflict(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $params['user_id']
        )->count();
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
        );
        $period = $leave_record_date->count();
        // 起始日下午
        if ( $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
            $period -= 0.5;
        }
        // 結束日上午
        if ( $params['end_hour'] == LeaveTimeEnum::MORNING ) {
            $period -= 0.5;
        }
        // 請假起始或結束日期為假日
        if( $leave_record_date[0]['holiday'] == HolidayEnum::HOLIDAY || $leave_record_date[$period-1]['holiday'] == HolidayEnum::HOLIDAY ) {
            return [
                'status' => -1,
                'message' => '請假起始或結束日為假日'
            ];
        }
        // 請假扣除假日判斷
        foreach($leave_record_date as $rows) {
            if( $rows['holiday'] == HolidayEnum::HOLIDAY ) {
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
        // 工作天數*8轉為時數
        $params['period'] = $period * 8;

        $this->LeaveRecordsRepository->createLeaveRecords($params);

        return [
            'status' => 0,
            'message' => '建立成功'
        ];
    }

    public function updateLeaveRecord(array $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecord($params['leave_id'], $params['valid_status']);

        return [
            'status' => 0,
            'message' => '修改假單狀態完成'
        ];
    }
}
