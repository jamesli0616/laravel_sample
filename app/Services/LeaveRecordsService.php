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
    protected function distinctType(Collection $record_results, int $user_id, string $cur_date)
    {
        $types = $record_results->map(function($item, $key) {
            return ['type' => $item['type']];
        })->unique('type')->values();
        $leaved_hours = $types->map(function($item, $key) use ($record_results, $cur_date, $user_id){
            return [
                'type' => $item['type'],
                'hours' => $this->getUserLeavedHoursByTypeAndYear($user_id, $item['type'], $this->getPeriodYearDate($cur_date, $item['type']))];
        });
        return $leaved_hours->toArray();
    }

    // 取得指定日期區間的假單紀錄
    protected function getLeaveRecordYearByPeriod(Collection $calculateDateRange)
    {
        return $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
            $calculateDateRange['Start_date'],
            $calculateDateRange['End_date']
        );
    }

    // 取得所有假單
    public function getLeaveRecordsByYear(int $year)
    {
        $cur_date = date("Y-m-d", strtotime($year.'-01-01'));
        $leave_records = $this->getLeaveRecordYearByPeriod($this->getPeriodYearDate($cur_date, LeaveTypesEnum::SIMPLE, true));
        return [
            'leaveCalendar' => $leave_records,
            'leaveCalendarYears' => $this->distinctYears($leave_records),
            'leaveRecordYear' => $year
        ];
    }

    // 取得所有假單 by user_id
    public function getLeaveRecordsByUserID(int $user_id, int $year)
    {
        $cur_date = date("Y-m-d", strtotime($year.'-01-01'));
        $leave_records = $this->getLeaveRecordYearByPeriod($this->getPeriodYearDate($cur_date, LeaveTypesEnum::SIMPLE, true))->where('user_id', $user_id);
        return [
            'leaveCalendar' =>  $leave_records,
            'leaveCalendarYears' => $this->distinctYears($leave_records),
            'leaveHoursList' => $this->distinctType($leave_records, $user_id, $cur_date),
            'leaveRecordYear' => $year
        ];
    }

    // 計算該年度的假別總時數
    public function calculateLeaveHoursInYear(Collection $leave_records, Collection $calculateDateRange, int $type)
    {
        // 該假別總時數
        $total_hours = $leave_records->where('type', $type)->sum('hours');
        // 檢查假單紀錄，找到有前一年的紀錄，要扣除前一年時數
        $check_past_year = $leave_records->where('type', $type)->where('start_date', '<', $calculateDateRange['Start_date']);
        if( !$check_past_year->isEmpty() ) {
            $past_year_workdays = $this->getWorkHoursSeprateByYear(
                $check_past_year->values()[0]['start_date'],
                $calculateDateRange['End_date'],
                $check_past_year->values()[0]['start_hour'],
                LeaveTimeEnum::AFTERNOON,
                $type
            );
            $total_hours -= $past_year_workdays['Pre_Hours'];
        }
         // 檢查假單紀錄，找到有下一年的紀錄，要扣除下一年時數
        $check_past_year = $leave_records->where('type', $type)->where('end_date', '>', $calculateDateRange['End_date']);
        if( !$check_past_year->isEmpty() ) {
            $past_year_workdays = $this->getWorkHoursSeprateByYear(
                $calculateDateRange['Start_date'],
                $check_past_year->values()[0]['end_date'],
                LeaveTimeEnum::MORNING,
                $check_past_year->values()[0]['end_hour'],
                $type
            );
            $total_hours -= $past_year_workdays['Hours'];
        }
        return $total_hours;
    }

    // 取得User年度的假別總時數
    public function getUserLeavedHoursByTypeAndYear(int $user_id, int $type, Collection $calculateDateRange)
    {
        $leave_records = $this->getLeaveRecordYearByPeriod($calculateDateRange)->where('user_id', $user_id);
        return $this->calculateLeaveHoursInYear($leave_records, $calculateDateRange, $type);
    }

    // 判斷休假時數是否超過假別上限
    public function checkIsOverLimit(int $willLeaveHours, int $type)
    {
        $leaveLimitDays = $this->LEAVE_CONFIG_ARRAY[$type]['Limit'];

        if ( $leaveLimitDays == LeaveLimitEnum::INFINITE ) return false;

        return $willLeaveHours > $leaveLimitDays * LeaveMinimumEnum::FULLDAY;
    }

    // 根據計算年度取得計算年度起始結束日
    public function getPeriodYearDate(string $date, int $type, bool $isDefault = false)
    {
        $parse_date = date_parse($date);
        $leavePeriod = $this->LEAVE_CONFIG_ARRAY[$type]['Period'];
        // 預設回傳一般年度計算區間 (+1年~-1年)
        if($isDefault) {
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

    // 根據日期範圍從行事曆取得工作天時數 (跨年度分開成兩種時數)
    public function getWorkHoursSeprateByYear(string $start_date, string $end_date, int $start_hour, int $end_hour, int $type)
    {
        $calendar = $this->CalendarRepository->getCalendarByDateRange();
        // 工作天時數
        $workHours = 0;
        $workHours_pre_year = 0;
        // 運算年度
        $leavePeriod = $this->LEAVE_CONFIG_ARRAY[$type]['Period'];
        // 取得區間日期
        $leave_date_range = $calendar->where('date', '>=', $start_date)->where('date', '<=', $end_date);
        foreach ($leave_date_range as $rows) {
            // 假日跳過
            if ($rows['holiday'] == HolidayEnum::HOLIDAY) continue;
            // 起始日在下午扣除半天
            if ( $rows['date'] == $start_date && $start_hour == LeaveTimeEnum::AFTERNOON ) {
                $workHours -= LeaveMinimumEnum::HALFDAY;
            }
            // 結束日在上午扣除半天
            if ( $rows['date'] == $end_date && $end_hour == LeaveTimeEnum::MORNING ) {
                $workHours -= LeaveMinimumEnum::HALFDAY;
            }
            // 日期範圍已跨年
            if ( $workHours_pre_year == 0 && strtotime($rows['date']) > strtotime($this->getPeriodYearDate($start_date, $type)['End_date']) ) {
                // 跨年時先結算前年度總時數
                $workHours_pre_year = $workHours;
                $workHours = 0;
            }
            $workHours += LeaveMinimumEnum::FULLDAY;
        }
        return new Collection([ "Hours" => $workHours, "Pre_Hours" => $workHours_pre_year]);
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
        $workHours = $this->getWorkHoursSeprateByYear(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $params['type']
        );
        $willLeaveHours_pre_year = $workHours['Pre_Hours'];
        $willLeaveHours = $workHours['Hours'];
        // 取得請假起始結束日年度的計算日期
        $calculateDateRange_start = $this->getPeriodYearDate($params['start_date'], $params['type']);
        $calculateDateRange_end = $this->getPeriodYearDate($params['end_date'], $params['type']);
        // 取得請假起始結束年度該假別的總時數
        $leavedHours = $this->getUserLeavedHoursByTypeAndYear($params['user_id'], $params['type'], $calculateDateRange_start);
        $leavedhours_next_year = $this->getUserLeavedHoursByTypeAndYear($params['user_id'], $params['type'], $calculateDateRange_end);
        // 休假總時數 = 跨年前後總時數相加
        $params['hours'] = $willLeaveHours + $willLeaveHours_pre_year;
        // 本次請假跨年度
        if ($leavedHours_pre_year != $leavedhours) {

        }



        if ( $this->checkIsOverLimit($willLeaveHours + $leaved_hours_year, $params['type']) )
        {
            throw new CreateLeaveRecordExceptions('該假別時數超過上限');
        }



        $this->LeaveRecordsRepository->createLeaveRecords($params);

        return [ 'status' => 0, 'message' => '建立成功'];
    }

    public function updateLeaveRecord(array $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecord($params['leave_id'], $params['valid_status']);

        return [ 'status' => 0, 'message' => '修改假單狀態完成'];
    }
}
