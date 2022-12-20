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
    
    // 計算年度的起始與結束日期
    protected $LEAVE_PERIOD_DATE = [
        LeavePeriodEnum::SIMPLEYEAR => [ // 一般年度
            'Start_date' => '-01-01', 
            'End_date' => '-12-31'
        ],
        LeavePeriodEnum::JAPANYEAR => [ // 日本年度
            'Start_date' => '-04-01', 
            'End_date' => '-03-31'
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

    // 取得所有不重複年份
    protected function distinctYears(Collection $record_results)
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
    protected function distinctType(Collection $record_results, int $year)
    {
        $types = $record_results->map(function($item, $key) {
            return ['type' => $item['type']];
        })->unique('type')->values();
        $leaved_hours = $types->map(function($item, $key) use ($record_results, $year){
            return [
                'type' => $item['type'],
                'hours' => $this->calculateLeaveHoursInYear($record_results, $item['type'], $year)];
        });
        return $leaved_hours->toArray();
    }

    // 根據計算年度取得整年的假單紀錄(預設為一般年度)
    protected function getLeaveRecordYearByPeriod(int $year, int $period = LeavePeriodEnum::SIMPLEYEAR)
    {
        return $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
            $year.$this->LEAVE_PERIOD_DATE[$period]['Start_date'],
            $year.$this->LEAVE_PERIOD_DATE[$period]['End_date']
        );
    }

    // 取得所有假單
    public function getLeaveRecordsByYear(int $year)
    {
        $leave_records = $this->getLeaveRecordYearByPeriod($year);
        return [
            'leaveCalendar' => $leave_records,
            'leaveCalendarYears' => $this->distinctYears($leave_records),
            'leaveRecordYear' => $year
        ];
    }

    // 取得所有假單 by user_id
    public function getLeaveRecordsByUserID(int $user_id, int $year)
    {
        $leave_records = $this->getLeaveRecordYearByPeriod($year)->where('user_id', $user_id);
        return [
            'leaveCalendar' =>  $leave_records,
            'leaveCalendarYears' => $this->distinctYears($leave_records),
            'leaveHoursList' => $this->distinctType($leave_records, $year),
            'leaveRecordYear' => $year
        ];
    }

    // 根據日期範圍從行事曆取得工作天數
    public function getWorkingDaysInCalendar(string $start_date, string $end_date)
    {
        $calendar = $this->CalendarRepository->getCalendarByDateRange();

        return $calendar
            ->where('date', '>=', $start_date)
            ->where('date', '<=', $end_date)
            ->where('holiday', HolidayEnum::WORKING)
            ->count();
    }

    // 計算該年度的假別總時數
    public function calculateLeaveHoursInYear(Collection $leave_records, int $type, int $year)
    {
        // 該假別總時數
        $total_hours = $leave_records->where('type', $type)->sum('hours');
        // 檢查假單紀錄，找到有前一年的紀錄，要扣除前一年時數
        $check_past_year = $leave_records->where('type', $type)
            ->where('start_date', '<', $year.$this->LEAVE_PERIOD_DATE[$this->LEAVE_CONFIG_ARRAY[$type]['Period']]['Start_date']);
        if( !$check_past_year->isEmpty() ) {
            $next_year_workdays = $this->getWorkingDaysInCalendar(
                $check_past_year->values()[0]['start_date'],
                ($year-1).$this->LEAVE_PERIOD_DATE[$this->LEAVE_CONFIG_ARRAY[$type]['Period']]['End_date']
            );
            $total_hours -= $next_year_workdays * LeaveMinimumEnum::FULLDAY;
            if ( $check_past_year->values()[0]['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
                $total_hours += LeaveMinimumEnum::HALFDAY;
            }
        }
         // 檢查假單紀錄，找到有下一年的紀錄，要扣除下一年時數
        $check_past_year = $leave_records->where('type', $type)
            ->where('end_date', '>', $year.$this->LEAVE_PERIOD_DATE[$this->LEAVE_CONFIG_ARRAY[$type]['Period']]['End_date']);
        if( !$check_past_year->isEmpty() ) {
            $next_year_workdays = $this->getWorkingDaysInCalendar(
                ($year+1).$this->LEAVE_PERIOD_DATE[$this->LEAVE_CONFIG_ARRAY[$type]['Period']]['Start_date'],
                $check_past_year->values()[0]['end_date']
            );
            $total_hours -= $next_year_workdays * LeaveMinimumEnum::FULLDAY;
            if ( $check_past_year->values()[0]['end_hour'] == LeaveTimeEnum::MORNING ) {
                $total_hours += LeaveMinimumEnum::HALFDAY;
            }
        }

        return $total_hours;
    }

    // 取得User年度的假別總時數
    public function getUserLeavedHoursByTypeAndYear(int $user_id, int $type, int $year)
    {
        $leave_records = $this->getLeaveRecordYearByPeriod($year, $this->LEAVE_CONFIG_ARRAY[$type]['Period'])->where('user_id', $user_id);
        return $this->calculateLeaveHoursInYear($leave_records, $type, $year);
    }

    // 判斷休假時數是否超過假別上限
    public function checkIsOverLimit(int $willLeaveHours, int $type)
    {
        $leaveLimitDays = $this->LEAVE_CONFIG_ARRAY[$type]['Limit'];

        if ( $leaveLimitDays == LeaveLimitEnum::INFINITE ) return false;

        return $willLeaveHours > $leaveLimitDays * LeaveMinimumEnum::FULLDAY;
    }

    public function createLeaveRecords(array $params)
    {
        // 取得整份行事曆
        $calendar = $this->CalendarRepository->getCalendarByDateRange();
        // 本次請假的起始結束日期
        $leave_start_date = date_parse($params['start_date']);
        $leave_end_date = date_parse($params['end_date']);
        // 請假起始結束時間判斷
        if ( strtotime($params['start_date']) > strtotime($params['end_date']) ) {
            throw new CreateLeaveRecordExceptions('起始時間大於結束時間');
        }
        // 請假起始或結束日期碰到假日
        if( $calendar->where('date', $params['start_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY ||
            $calendar->where('date', $params['end_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY 
        ) {
            throw new CreateLeaveRecordExceptions('請假起始或結束日為假日');
        }
        // 請假時間重疊判斷
        if ( !$this->LeaveRecordsRepository->getLeaveRecordConflict(
                $params['start_date'],
                $params['end_date'],
                $params['start_hour'],
                $params['end_hour'],
                $params['user_id']
            )->isEmpty()
        ) {
            throw new CreateLeaveRecordExceptions('請假日期與其他假單重疊');
        }

        // 本次請假時數
        $willLeaveHours = 0;
        $willLeaveHours_pre_year = 0;
        // 請假假別運算年度
        $leavePeriod = $this->LEAVE_CONFIG_ARRAY[$params['type']]['Period'];
        // 取得請假起始年度該假別的總時數
        $leaved_hours_year = $this->getUserLeavedHoursByTypeAndYear($params['user_id'], $params['type'], $leave_start_date['year']);
        // 取得這次請假區間日期
        $leave_date_range = $calendar->where('date', '>=', $params['start_date'])->where('date', '<=', $params['end_date']);
        foreach ($leave_date_range as $rows) {
            // 假日跳過
            if ($rows['holiday'] == HolidayEnum::HOLIDAY) continue;
            // 起始日在下午扣除半天
            if ( $rows['date'] == $params['start_date'] && $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
                $willLeaveHours -= LeaveMinimumEnum::HALFDAY;
            }
            // 結束日在上午扣除半天
            if ( $rows['date'] == $params['end_date'] && $params['end_hour'] == LeaveTimeEnum::MORNING ) {
                $willLeaveHours -= LeaveMinimumEnum::HALFDAY;
            }
            // 這次請假已跨年
            if ( $willLeaveHours_pre_year == 0 && strtotime($rows['date']) > strtotime($leave_start_date['year'].$this->LEAVE_PERIOD_DATE[$leavePeriod]['End_date']) ) {
                // 跨年前總時數結算
                $willLeaveHours_pre_year = $willLeaveHours;
                $willLeaveHours = 0;
                // 取得請假跨年度該假別的總時數
                $leaved_hours_year = $this->getUserLeavedHoursByTypeAndYear($params['user_id'], $params['type'], $leave_start_date['year']);
            }
            $willLeaveHours += LeaveMinimumEnum::FULLDAY;
        }
        // 休假總時數 = 跨年前後總時數相加
        $params['hours'] = $willLeaveHours + $willLeaveHours_pre_year;
        echo $willLeaveHours . ' ' . $willLeaveHours_pre_year . '<br>';

       




        dd('1234');
        //$this->LeaveRecordsRepository->createLeaveRecords($params);

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
