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
                'hours' => $this->getUserLeavedHoursByTypeAndDateRange($user_id, $item['type'], $this->getPeriodYearDate($cur_date, $item['type']))
            ];
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

    // 根據日期取得該月起始與結束日
    public function getMonthHeadTailDate(string $date)
    {
        $first_date_in_month = date('Y-m-01',strtotime($date));
        $last_date_in_month = date("Y-m-t", strtotime($date));

        return new Collection(['Start_date' => $first_date_in_month, 'End_date' => $last_date_in_month]);
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

    // 指定區間取得分開工作天時數
    public function getWorkHoursSeprateByDateRange(string $start_date, string $end_date, int $start_hour, int $end_hour, Collection $date_range)
    {
        $calendar = $this->CalendarRepository->getCalendarByDateRange();
        $leave_date_range = $calendar->where('date', '>=', $start_date)->where('date', '<=', $end_date)->values();
        $workDayHours_prePeriod = 0;
        $workDayHours = 0;
        $workDayHours_nextPeriod = 0;
        foreach ($leave_date_range as $rows) {
            if ($rows['holiday'] == HolidayEnum::HOLIDAY) continue;
            if ( $rows['date'] == $start_date && $start_hour == LeaveTimeEnum::AFTERNOON ) {
                $addDays = LeaveMinimumEnum::HALFDAY;
            } else if ( $rows['date'] == $end_date && $end_hour == LeaveTimeEnum::MORNING ) {
                $addDays = LeaveMinimumEnum::HALFDAY;
            } else {
                $addDays = LeaveMinimumEnum::FULLDAY;
            }
            if ( strtotime($rows['date']) < strtotime($date_range['Start_date']) ) {
                $workDayHours_prePeriod += $addDays;
            } else if ( strtotime($rows['date']) > strtotime($date_range['End_date']) ) {
                $workDayHours_nextPeriod += $addDays;
            } else {
                $workDayHours += $addDays;
            }
        }
        return new Collection([ "Hours" => $workDayHours, "Pre_Hours" => $workDayHours_prePeriod, "Next_Hours" => $workDayHours_nextPeriod]);
    }

    // 取得User指定區間的假別總時數
    public function getUserLeavedHoursByTypeAndDateRange(int $user_id, int $type, Collection $calculateDateRange)
    {
        $leave_records = $this->getLeaveRecordsByDataRange($calculateDateRange)->where('user_id', $user_id);
        $leaved_hours = $leave_records->where('type', $type)->sum('hours');
        // 找出日期區間外的假單
        $leave_records_fliter = $leave_records->where('type', $type)->filter(function ($item) use ($calculateDateRange) {
            return $item['start_date'] < $calculateDateRange['Start_date'] || $item['end_date'] > $calculateDateRange['End_date'];
        });
        // 扣除日期區間外的時數
        foreach ($leave_records_fliter as $rows) {
            $leaved_past_hours = $this->getWorkHoursSeprateByDateRange(
                $rows['start_date'],
                $rows['end_date'],
                $rows['start_hour'],
                $rows['end_hour'],
                $calculateDateRange
            );
            $leaved_hours -= $leaved_past_hours['Pre_Hours'] + $leaved_past_hours['Next_Hours'];
        }
        return $leaved_hours;
    }

    // 判斷生理假是否超過每月上限
    public function checkPeriodLeaveMonthIsOverLimit(int $user_id, int $type, int $willLeaveHours, string $date)
    {
        $calculateDateRange = $this->getMonthHeadTailDate($date);
        $leavedHours = $this->getUserLeavedHoursByTypeAndDateRange($user_id, $type, $calculateDateRange);

        if ( $leavedHours + $willLeaveHours > LeaveMinimumEnum::FULLDAY ) {
            throw new CreateLeaveRecordExceptions('生理假超過每月1日上限');
        }
    }

    // 判斷生理假合併病假是否超過上限
    public function checkPeriodLeaveCombineSickIsOverLimit(int $user_id, int $willLeaveHours, string $date)
    {
        $calculateDateRange = $this->getPeriodYearDate($date, LeaveTypesEnum::PERIOD);
        $leavedHours = $this->getUserLeavedHoursByTypeAndDateRange($user_id, LeaveTypesEnum::PERIOD, $calculateDateRange);
        $leavedLimit = $leavedHours + $willLeaveHours - LeaveMinimumEnum::FULLDAY * 3; // 超過3日合併病假
        if ( $leavedLimit > 0 ) {
            if ( $this->checkLeaveYearIsOverLimit($user_id, LeaveTypesEnum::SICK, $leavedLimit, $date) )
                return true;
        }
        return false;
    }

    // 判斷整年假別加總時數是否超過上限
    public function checkLeaveYearIsOverLimit(int $user_id, int $type, int $willLeaveHours, string $date)
    {
        $calculateDateRange = $this->getPeriodYearDate($date, $type);
        $leaveLimitDays = $this->LEAVE_CONFIG_ARRAY[$type]['Limit'];
        if ( $leaveLimitDays == LeaveLimitEnum::INFINITE ) return false;
        if ( $type == LeaveTypesEnum::FAMILYCARE ) {
            if ( $this->checkLeaveYearIsOverLimit($user_id, LeaveTypesEnum::SIMPLE, $willLeaveHours, $calculateDateRange) ) {
                throw new CreateLeaveRecordExceptions('合併事假時數超過上限');
            }
        }
        $leavedHours = $this->getUserLeavedHoursByTypeAndDateRange($user_id, $type, $calculateDateRange);
        return ($leavedHours + $willLeaveHours) > $leaveLimitDays * LeaveMinimumEnum::FULLDAY;
    }

    public function createLeaveRecords(array $params)
    {
        if ( strtotime($params['start_date']) > strtotime($params['end_date']) ) {
            throw new CreateLeaveRecordExceptions('起始時間大於結束時間');
        }
        if ( !$this->LeaveRecordsRepository->getLeaveRecordConflict($params)->isEmpty() ) {
            throw new CreateLeaveRecordExceptions('請假日期與其他假單重疊');
        }
        $calendar = $this->CalendarRepository->getCalendarByDateRange();
        if( $calendar->where('date', $params['start_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY ||
            $calendar->where('date', $params['end_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY 
        ) {
            throw new CreateLeaveRecordExceptions('請假起始或結束日為假日');
        }
        // 取得本次請假區間涵蓋工作時數
        $workHours = $this->getWorkHoursSeprateByDateRange(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $this->getPeriodYearDate($params['start_date'], $params['type'])
        );
        if ( $params['type'] == LeaveTypesEnum::PERIOD ) {
            $monthHours = $this->getWorkHoursSeprateByDateRange(
                $params['start_date'],
                $params['end_date'],
                $params['start_hour'],
                $params['end_hour'],
                $this->getMonthHeadTailDate($params['start_date'])
            );
            $this->checkPeriodLeaveMonthIsOverLimit($params['user_id'], $params['type'], $monthHours['Hours'], $params['start_date']);      // 當月份
            $this->checkPeriodLeaveMonthIsOverLimit($params['user_id'], $params['type'], $monthHours['Next_Hours'], $params['end_date']);   // 下月份
            if ( $this->checkPeriodLeaveCombineSickIsOverLimit($params['user_id'], $workHours['Hours'], $params['start_date']) ||       // 當年度
                 $this->checkPeriodLeaveCombineSickIsOverLimit($params['user_id'], $workHours['Next_Hours'], $params['end_date'])       // 下年度
            ) {
                $params['warning'] = '合併病假已超過上限特別標示';
            }
        }
        if ( $this->checkLeaveYearIsOverLimit($params['user_id'], $params['type'], $workHours['Hours'], $params['start_date']) ||     // 當年度
             $this->checkLeaveYearIsOverLimit($params['user_id'], $params['type'], $workHours['Next_Hours'], $params['end_date'])     // 下年度
        ) {
            if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                $params['warning'] = '已超過上限特別標示';
            } else {
                throw new CreateLeaveRecordExceptions('請假時數超過上限');
            }
        }

        $params['hours'] = $workHours->sum();

        $this->LeaveRecordsRepository->createLeaveRecords($params);

        return [ 'status' => 0, 'message' => '建立成功'];
    }

    public function updateLeaveRecord(array $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecord($params);

        return [ 'status' => 0, 'message' => '修改假單狀態完成'];
    }
}
