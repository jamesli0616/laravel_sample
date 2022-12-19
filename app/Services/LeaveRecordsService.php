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

    // 根據date取得所有不重複年份
    protected function distinctYears(Collection $record_results)
    {
        $end_years = $record_results->map(function($item, $key) {
            return ['year' => date_parse($item['end_date'])['year']];
        });
        return $record_results->map(function($item, $key) {
            return ['year' => date_parse($item['start_date'])['year']];
        })->merge($end_years)->unique('year')->toArray();
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
            'leaveRecordYear' => $year
        ];
    }

    // 根據日期範圍從行事曆取得工作天數
    public function getWorkingDaysInCalendar(Collection $calendar, string $start_date, string $end_date)
    {
        return $calendar
            ->where('date', '>=', $start_date)
            ->where('date', '<=', $end_date)
            ->where('holiday', HolidayEnum::WORKING)
            ->count();
    }

    // 計算該年度的假別總時數
    public function calculateLeaveHoursInYear(Collection $calendar, Collection $leave_records, int $type, int $year, int $period)
    {
        // 該假別總時數
        $total_hours = $leave_records->where('type', $type)->sum('hours');
        // 檢查假單紀錄，找到有前一年的紀錄，要扣除前一年時數
        $check_past_year = $leave_records->where('type', $type)->where('start_date', '<', $year.$this->LEAVE_PERIOD_DATE[$period]['Start_date']);
        if( !$check_past_year->isEmpty() ) {
            $next_year_workdays = $this->getWorkingDaysInCalendar(
                $calendar,
                $check_past_year->values()[0]['start_date'],
                ($year-1).$this->LEAVE_PERIOD_DATE[$period]['End_date']
            );
            $total_hours -= $next_year_workdays * LeaveMinimumEnum::FULLDAY;
        }
         // 檢查假單紀錄，找到有下一年的紀錄，要扣除下一年時數
        $check_past_year = $leave_records->where('type', $type)->where('end_date', '>', $year.$this->LEAVE_PERIOD_DATE[$period]['End_date']);
        if( !$check_past_year->isEmpty() ) {
            $next_year_workdays = $this->getWorkingDaysInCalendar(
                $calendar,
                ($year+1).$this->LEAVE_PERIOD_DATE[$period]['Start_date'],
                $check_past_year->values()[0]['end_date']
            );
            $total_hours -= $next_year_workdays * LeaveMinimumEnum::FULLDAY;
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
            $calendar->where('date', $params['end_date'])->values()[0]['holiday'] == HolidayEnum::HOLIDAY ) {
            throw new CreateLeaveRecordExceptions('請假起始或結束日為假日');
        }

        // 請假時間重疊判斷
        if ( !$this->LeaveRecordsRepository->getLeaveRecordConflict(
                $params['start_date'],
                $params['end_date'],
                $params['start_hour'],
                $params['end_hour'],
                $params['user_id']
            )->isEmpty() ) {
            throw new CreateLeaveRecordExceptions('請假日期與其他假單重疊');
        }

        // 請假假別時數上限(天)
        $leaveLimitDays = $this->LEAVE_CONFIG_ARRAY[$params['type']]['Limit'];
        // 請假假別運算年度
        $leavePeriod = $this->LEAVE_CONFIG_ARRAY[$params['type']]['Period'];
        // 請假假別時數最小單位
        $leaveMinimum = $this->LEAVE_CONFIG_ARRAY[$params['type']]['Minimum'];

        // 特休與日本年度例外
        if( $leavePeriod == LeavePeriodEnum::JAPANYEAR || $params['type'] == LeaveTypesEnum::SPECIAL) {
            throw new CreateLeaveRecordExceptions('特休假與日本年度例外');
        }

        // 一般年度
        if( $leavePeriod == LeavePeriodEnum::SIMPLEYEAR ) {
            // 是否跨年
            $isPastYear = $leave_start_date['year'] != $leave_end_date['year'];
            // 本次請假天數
            $willLeaveDays = $this->getWorkingDaysInCalendar(
                $calendar,
                $params['start_date'],
                $params['end_date']
            );
            // 請假起始年度假單
            $leave_records_start_year = $this->getLeaveRecordYearByPeriod($leave_start_date['year'], $leavePeriod)->where('user_id', $params['user_id']);
            // 請假起始年度指定假別總時數
            $leaved_hours_start_year = $this->calculateLeaveHoursInYear($calendar, $leave_records_start_year, $params['type'], $leave_start_date['year'], $leavePeriod);
            // 假單跨年度時要另外考慮結束年度
            if( $isPastYear ) {
                // 請假結束年度假單
                $leave_records_end_year = $this->getLeaveRecordYearByPeriod($leave_end_date['year'], $leavePeriod)->where('user_id', $params['user_id']);
                // 請假結束年度指定假別總時數
                $leaved_hours_end_year = $this->calculateLeaveHoursInYear($calendar, $leave_records_end_year, $params['type'], $leave_end_date['year'], $leavePeriod);
                // 前年覆蓋天數
                $past_days_start_year = $this->getWorkingDaysInCalendar(
                    $calendar,
                    $params['start_date'],
                    $leave_start_date['year'].$this->LEAVE_PERIOD_DATE[$leavePeriod]['End_date']
                );
                // 後年覆蓋天數 = 本次請假天數 - 前年覆蓋天數
                $past_days_end_year = $willLeaveDays - $past_days_start_year;
            }
            // 起始日在下午扣除半天
            if ( $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
                $willLeaveDays -= 0.5;
                // 假單跨年度 前年覆蓋天數扣除半天
                if( $isPastYear ) $past_days_start_year -= 0.5;
            }
            // 結束日在上午扣除半天
            if ( $params['end_hour'] == LeaveTimeEnum::MORNING ) {
                $willLeaveDays -= 0.5;
                // 假單跨年度 後年覆蓋天數扣除半天
                if( $isPastYear ) $past_days_end_year -= 0.5;
            }
            // 休假總天數轉為休假總時數
            $params['hours'] = $willLeaveDays * LeaveMinimumEnum::FULLDAY;

            // 假單跨年度前後年分開判斷上限
            if( $isPastYear ) {
                // 請假時數超過上限 (假單跨年，前後年獨立檢查)
                if( $this->checkIsOverLimit($leaved_hours_start_year + $past_days_start_year * LeaveMinimumEnum::FULLDAY, $params['type']) ||
                    $this->checkIsOverLimit($leaved_hours_end_year + $past_days_end_year * LeaveMinimumEnum::FULLDAY, $params['type']) ) {
                    //超過上限要標示的假別
                    if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                        $params['warning'] = '已超過上限';
                    } else {
                        throw new CreateLeaveRecordExceptions('請假時數超過上限');
                    }
                }
            } else {
                // 請假時數超過上限
                if( $this->checkIsOverLimit($leaved_hours_start_year + $params['hours'], $params['type']) ) {
                    //超過上限要標示的假別
                    if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                        $params['warning'] = '已超過上限';
                    } else {
                        throw new CreateLeaveRecordExceptions('請假時數超過上限');
                    }
                }
            }
        }
        
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
