<?php

namespace App\Services;

use App\Exceptions\CreateLeaveRecordExceptions;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\LeaveRecordsRepository;
use App\Repositories\CalendarRepository;
use App\Enums\LeaveLimitEnum;
use App\Enums\LeaveTypesEnum;
use App\Enums\LeaveTimeEnum;
use App\Enums\LeaveMinimumEnum;
use App\Enums\LeavePeriodEnum;
use App\Enums\HolidayEnum;

class LeaveRecordsService
{
    // 各假別的 上限 / 計算年度 / 最小時數 設定項目
    protected $LEAVE_CONFIG_ARRAY = [
        LeaveTypesEnum::SICK => [ // 病假
            'Limit' => LeaveLimitEnum::SICK, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::SIMPLE => [ // 事假
            'Limit' => LeaveLimitEnum::SIMPLE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::PERIOD => [ // 生理假
            'Limit' => LeaveLimitEnum::PERIOD, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::FUNERAL => [ // 喪假
            'Limit' => LeaveLimitEnum::INFINITE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::INJURY => [ // 工傷病假
            'Limit' => LeaveLimitEnum::INFINITE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::MATERNITY => [ // 產假
            'Limit' => LeaveLimitEnum::INFINITE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::TOCOLYSIS => [ // 安胎休養假
            'Limit' => LeaveLimitEnum::TOCOLYSIS, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::PATERNITY => [ // 陪產假
            'Limit' => LeaveLimitEnum::PATERNITY, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::PRENTAL => [ // 產檢假
            'Limit' => LeaveLimitEnum::INFINITE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::FAMILYCARE => [ // 家庭照顧假
            'Limit' => LeaveLimitEnum::FAMILYCARE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::OFFICIAL => [ // 公假
            'Limit' => LeaveLimitEnum::INFINITE, 
            'Period' => LeavePeriodEnum::SIMPLEYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ],
        LeaveTypesEnum::SPECIAL => [ // 特休
            'Limit' => LeaveLimitEnum::SPECIAL, 
            'Period' => LeavePeriodEnum::JAPANYEAR, 
            'Minimum' => LeaveMinimumEnum::HALFDAY
        ]
    ];

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

    // 取得假單每一筆起始與結束日期不重複的年份
    protected function getDistinctYears(Collection $record_results)
    {
        $start_years = $record_results->map(function($item, $key) {
            return ['year' => date_parse($item['start_date'])['year']];
        });
        $end_years = $record_results->map(function($item, $key) {
            return ['year' => date_parse($item['end_date'])['year']];
        });

        return $start_years->merge($end_years)->unique('year')->toArray();
    }

    // 取得所有不重複假別年度總時數
    protected function getDistinctTypeHours(Collection $record_results, int $user_id, string $cur_date)
    {
        $types = $record_results->map(function($item, $key) {
            return ['type' => $item['type']];
        })->unique('type')->values();
        $leaved_hours = $types->map(function($item, $key) use ($record_results, $user_id, $cur_date){
            return [
                'type' => $item['type'],
                'hours' => $this->getUserLeavedHoursByTypeAndDateRange($user_id, $item['type'], $this->getPeriodYearDate($cur_date, $item['type']))];
        });
        return $leaved_hours->toArray();
    }

    // 取得指定日期區間的假單紀錄
    protected function getLeaveRecordsByDataRange(Collection $calculateDateRange)
    {
        return $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
            $calculateDateRange['Start_date'],
            $calculateDateRange['End_date']
        );
    }

    // 取得所有假單
    public function getLeaveRecordsByYear(int $year)
    {
        $cur_date = date("Y-m-d", strtotime($year.'-01-01')); // 預設為該年的1/1
        $leave_records = $this->getLeaveRecordsByDataRange($this->getPeriodYearDate($cur_date, LeaveTypesEnum::SIMPLE, true));
        return [
            'leaveCalendar' => $leave_records,
            'leaveCalendarYears' => $this->getDistinctYears($leave_records),
            'leaveRecordYear' => $year
        ];
    }

    // 取得所有假單 by user_id
    public function getLeaveRecordsByUserID(int $user_id, int $year)
    {
        $cur_date = date("Y-m-d", strtotime($year.'-01-01')); // 預設為該年的1/1
        $leave_records = $this->getLeaveRecordsByDataRange($this->getPeriodYearDate($cur_date, LeaveTypesEnum::SIMPLE, true))->where('user_id', $user_id);
        return [
            'leaveCalendar' =>  $leave_records,
            'leaveCalendarYears' => $this->getDistinctYears($leave_records),
            'leaveHoursList' => $this->getDistinctTypeHours($leave_records, $user_id, $cur_date),
            'leaveRecordYear' => $year
        ];
    }

    // 根據日期取得假別計算年度起始結束日
    public function getPeriodYearDate(string $date, int $type, bool $isDefault = false)
    {
        $parse_date = date_parse($date);
        $leavePeriod = $this->LEAVE_CONFIG_ARRAY[$type]['Period'];
        if($isDefault) { // 預設回傳一般年度計算區間 (+1年~-1年)
            return new Collection(['Start_date' => ($parse_date['year']-1).'-01-01', 'End_date' => ($parse_date['year']+1).'-12-31']);
        }
        switch($leavePeriod) {
        case LeavePeriodEnum::SIMPLEYEAR:
            return new Collection(['Start_date' => $parse_date['year'].'-01-01', 'End_date' => $parse_date['year'].'-12-31']);
        case LeavePeriodEnum::JAPANYEAR: {
                if ( $parse_date['month'] > 3) {
                    return new Collection(['Start_date' => $parse_date['year'].'-04-01', 'End_date' => ($parse_date['year']+1).'-03-31']);
                } else {
                    return new Collection(['Start_date' => ($parse_date['year']-1).'-04-01', 'End_date' => $parse_date['year'].'-03-31']);
                }
            }
        }
    }

    // 計算指定範圍的假別總時數
    public function calculateLeaveHoursByDateRange(Collection $leave_records, Collection $calculateDateRange, int $type)
    {
        $total_hours = $leave_records->where('type', $type)->sum('hours');
        // 檢查假單移除小於區間範圍的時數
        $check_past_year = $leave_records->where('type', $type)->where('start_date', '<', $calculateDateRange['Start_date']);
        if( !$check_past_year->isEmpty() ) {
            $past_year_workdays = $this->getWorkHoursSeprateByDate(
                $check_past_year->values()[0]['start_date'],
                $check_past_year->values()[0]['end_date'],
                $check_past_year->values()[0]['start_hour'],
                $check_past_year->values()[0]['end_hour'],
                $type,
                date('Y-m-d',(strtotime ('-1 day', strtotime($calculateDateRange['Start_date']))))
            );
            $total_hours -= $past_year_workdays['Pre_Hours'];
        }
        // 檢查假單移除大於區間範圍的時數
        $check_past_year = $leave_records->where('type', $type)->where('end_date', '>', $calculateDateRange['End_date']);
        if( !$check_past_year->isEmpty() ) {
            $past_year_workdays = $this->getWorkHoursSeprateByDate(
                $check_past_year->values()[0]['start_date'],
                $check_past_year->values()[0]['end_date'],
                $check_past_year->values()[0]['start_hour'],
                $check_past_year->values()[0]['end_hour'],
                $type,
                $calculateDateRange['End_date']
            );
            $total_hours -= $past_year_workdays['Hours'];
        }
        return $total_hours;
    }

    // 判斷休假時數是否超過假別上限
    public function checkIsOverLimit(int $willLeaveHours, int $type)
    {
        $leaveLimitDays = $this->LEAVE_CONFIG_ARRAY[$type]['Limit'];

        if ( $leaveLimitDays == LeaveLimitEnum::INFINITE ) return false;

        return $willLeaveHours > $leaveLimitDays * LeaveMinimumEnum::FULLDAY;
    }

    // 指定跨越日期取得分開工作天時數
    public function getWorkHoursSeprateByDate(string $start_date, string $end_date, int $start_hour, int $end_hour, int $type, string $past_year_date)
    {
        $calendar = $this->CalendarRepository->getCalendarByDateRange();
        $leavePeriod = $this->LEAVE_CONFIG_ARRAY[$type]['Period'];
        $leave_date_range = $calendar->where('date', '>=', $start_date)->where('date', '<=', $end_date)->values();
        $workDayHours = 0;
        $workDayHours_preYear = 0;
        foreach ($leave_date_range as $rows) {
            if ($rows['holiday'] == HolidayEnum::HOLIDAY) continue;
            if ( $rows['date'] == $start_date && $start_hour == LeaveTimeEnum::AFTERNOON ) {
                $workDayHours -= LeaveMinimumEnum::HALFDAY;
            }
            if ( $rows['date'] == $end_date && $end_hour == LeaveTimeEnum::MORNING ) {
                $workDayHours -= LeaveMinimumEnum::HALFDAY;
            }
            if ( $workDayHours_preYear == 0 && strtotime($rows['date']) > strtotime($past_year_date) ) {
                // 時間跨越指定日先結算跨越前總時數
                $workDayHours_preYear = $workDayHours;
                $workDayHours = 0;
            }
            $workDayHours += LeaveMinimumEnum::FULLDAY;
        }
        return new Collection([ "Hours" => $workDayHours, "Pre_Hours" => $workDayHours_preYear]);
    }

    // 取得User指定區間的假別總時數
    public function getUserLeavedHoursByTypeAndDateRange(int $user_id, int $type, Collection $calculateDateRange)
    {
        $leave_records = $this->getLeaveRecordsByDataRange($calculateDateRange)->where('user_id', $user_id);
        return $this->calculateLeaveHoursByDateRange($leave_records, $calculateDateRange, $type);
    }

    public function createLeaveRecords(array $params)
    {
        $calendar = $this->CalendarRepository->getCalendarByDateRange();
        if ( strtotime($params['start_date']) > strtotime($params['end_date']) ) {
            throw new CreateLeaveRecordExceptions('起始時間大於結束時間');
        }
        if( $calendar->where('date', $params['start_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY ||
            $calendar->where('date', $params['end_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY 
        ) {
            throw new CreateLeaveRecordExceptions('請假起始或結束日為假日');
        }
        // 請假時間重疊其他假單判斷
        if ( !$this->LeaveRecordsRepository->getLeaveRecordConflict($params)->isEmpty()) {
            throw new CreateLeaveRecordExceptions('請假日期與其他假單重疊');
        }
        // 取得本次請假時數
        $workHours = $this->getWorkHoursSeprateByDate(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $params['type'],
            $this->getPeriodYearDate($params['start_date'], $params['type'])['End_date']
        );
        // 取得請假起始與結束年度該假別的總時數
        $leavedHours = $this->getUserLeavedHoursByTypeAndDateRange($params['user_id'], $params['type'], $this->getPeriodYearDate($params['start_date'], $params['type']));
        $leavedhours_next_year = $this->getUserLeavedHoursByTypeAndDateRange($params['user_id'], $params['type'], $this->getPeriodYearDate($params['end_date'], $params['type']));

        $params['hours'] = $workHours['Hours'] + $workHours['Pre_Hours'];

        if ( $params['type'] == LeaveTypesEnum::FAMILYCARE ) {
            // 取得事假起始與結束年度該假別的總時數
            $leavedHours_simple = $this->getUserLeavedHoursByTypeAndDateRange($params['user_id'], LeaveTypesEnum::SIMPLE, $this->getPeriodYearDate($params['start_date'], LeaveTypesEnum::SIMPLE));
            $leavedHours_simple_next_year = $this->getUserLeavedHoursByTypeAndDateRange($params['user_id'], LeaveTypesEnum::SIMPLE, $this->getPeriodYearDate($params['end_date'], LeaveTypesEnum::SIMPLE));

            if ( $this->checkIsOverLimit($leavedHours + $workHours['Pre_Hours'] + $leavedHours_simple, LeaveTypesEnum::SIMPLE) ||     // 前一年
                $this->checkIsOverLimit($leavedhours_next_year + $workHours['Hours'] + $leavedHours_simple_next_year, LeaveTypesEnum::SIMPLE)  // 後一年 or 未跨年
            ) {
                throw new CreateLeaveRecordExceptions('合併事假時數超過上限');
            }
        }

        if ( $this->checkIsOverLimit($leavedHours + $workHours['Pre_Hours'], $params['type']) ||     // 前一年
            $this->checkIsOverLimit($leavedhours_next_year + $workHours['Hours'], $params['type'])  // 後一年 or 未跨年
        ) {
            if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                $params['warning'] = '已超過上限';
            } else {
                throw new CreateLeaveRecordExceptions('請假時數超過上限');
            }
        }
        
        $this->LeaveRecordsRepository->createLeaveRecords($params);

        return [ 'status' => 0, 'message' => '建立成功'];
    }

    public function updateLeaveRecord(array $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecord($params);

        return [ 'status' => 0, 'message' => '修改假單狀態完成'];
    }
}
