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
        $distinct_years = $record_results->transform( function($item, $key) {
            return ['year' => date_parse($item['start_date'])['year']];
        });
        return $distinct_years->unique('year')->toArray();
    }

    public function getLeaveRecordsByYear(int $year)
    {
        $leave_records = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
            $year.'-01-01',
            $year.'-12-31'
        );
        $leave_records_all = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange();
        return [
            'leaveCalendar' => $leave_records,
            'leaveCalendarYears' => $this->distinctYears($leave_records_all),
            'leaveRecordYear' => $year
        ];
    }

    public function getLeaveRecordsByUserID(int $user_id, int $year)
    {
        $leave_records = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
            $year.'-01-01',
            $year.'-12-31'
        )->where('user_id', $user_id);
        $leave_records_all = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange()->where('user_id', $user_id);
        return [
            'leaveCalendar' =>  $leave_records,
            'leaveCalendarYears' => $this->distinctYears($leave_records_all),
            'leaveRecordYear' => $year
        ];
    }

    // 取得該年年初~指定日期的工作天數 (扣除跨年度請假)
    public function getPastYearCalendarWorkdaysFromHead(int $year, string $end_date)
    {
        $year_days = $this->CalendarRepository->getCalendarByDateRange(
            $year.'-01-01',
            $end_date
        );

        return $year_days->where('holiday', HolidayEnum::WORKING)->count();
    }

    // 取得指定日期~該年年末的工作天數 (扣除跨年度請假)
    public function getPastYearCalendarWorkdaysToBottom(int $year, string $start_date)
    {
        $year_days = $this->CalendarRepository->getCalendarByDateRange(
            $start_date,
            $year.'-12-31'
        );

        return $year_days->where('holiday', HolidayEnum::WORKING)->count();
    }
    
    // 計算要排除的不同計算年度假別時數
    public function calculateRedundantLeaveHoursInYear(Collection $leave_records, int $type, int $year)
    {
        // 跨年度要扣除的時數
        $redundant_hours = 0;

        // 起始日期有跨年，扣掉該假前年度時數
        $check_past_year = $leave_records->where('type', $type)->where('start_date', '<', $year.'-01-01');
        if( !$check_past_year->isEmpty() ) {
            $next_year_workdays = $this->getPastYearCalendarWorkdaysToBottom($year - 1, $check_past_year->values()[0]['start_date']);
            $redundant_hours += $next_year_workdays * LeaveMinimumEnum::FULLDAY;
        }

        // 結束日期有跨年，扣掉該假跨年度時數
        $check_past_year = $leave_records->where('type', $type)->where('end_date', '>', $year.'-12-31');
        if( !$check_past_year->isEmpty() ) {
            $next_year_workdays = $this->getPastYearCalendarWorkdaysFromHead($year + 1, $check_past_year->values()[0]['end_date']);
            $redundant_hours += $next_year_workdays * LeaveMinimumEnum::FULLDAY;
        }

        return $redundant_hours;
    }

    // 取得假別天數上限
    public function getLeaveLimitDays(int $type)
    {
        switch($type) 
        {
        case LeaveTypesEnum::SICK:
            return LeaveLimitEnum::SICK;
        case LeaveTypesEnum::SIMPLE:
            return LeaveLimitEnum::SIMPLE;
        case LeaveTypesEnum::PERIOD:
            return LeaveLimitEnum::PERIOD;
        case LeaveTypesEnum::FUNERAL:
            return LeaveLimitEnum::FUNERAL;
        case LeaveTypesEnum::INJURY:
            return LeaveLimitEnum::INJURY;
        case LeaveTypesEnum::MATERNITY:
            return LeaveLimitEnum::MATERNITY;
        case LeaveTypesEnum::TOCOLYSIS:
            return LeaveLimitEnum::TOCOLYSIS;
        case LeaveTypesEnum::PATERNITY:
            return LeaveLimitEnum::PATERNITY;
        case LeaveTypesEnum::FAMILYCARE:
            return LeaveLimitEnum::FAMILYCARE;
        case LeaveTypesEnum::OFFICIAL:
            return LeaveLimitEnum::OFFICIAL;
        case LeaveTypesEnum::SPECIAL:
            return LeaveLimitEnum::SPECIAL;
        }
    }

    // 取得假別計算年度
    public function getLeavePeriod(int $type)
    {
        switch($type) 
        {
        case LeaveTypesEnum::SICK:
        case LeaveTypesEnum::SIMPLE:
        case LeaveTypesEnum::PERIOD:
        case LeaveTypesEnum::FUNERAL:
        case LeaveTypesEnum::INJURY:
        case LeaveTypesEnum::MATERNITY:
        case LeaveTypesEnum::TOCOLYSIS:
        case LeaveTypesEnum::PATERNITY:
        case LeaveTypesEnum::FAMILYCARE:
        case LeaveTypesEnum::OFFICIAL:
            return LeavePeriodEnum::SIMPLEYEAR;
        case LeaveTypesEnum::SPECIAL:
            return LeavePeriodEnum::JAPANYEAR;
        }
    }

    // 取得假別時數最小單位
    public function getLeaveMinimum(int $type)
    {
        switch($type) 
        {
        case LeaveTypesEnum::SICK:
        case LeaveTypesEnum::SIMPLE:
        case LeaveTypesEnum::PERIOD:
        case LeaveTypesEnum::FUNERAL:
        case LeaveTypesEnum::INJURY:
        case LeaveTypesEnum::MATERNITY:
        case LeaveTypesEnum::TOCOLYSIS:
        case LeaveTypesEnum::PATERNITY:
        case LeaveTypesEnum::FAMILYCARE:
        case LeaveTypesEnum::OFFICIAL:
        case LeaveTypesEnum::SPECIAL:
            return LeaveMinimumEnum::HALFDAY;
        }
    }

    public function createLeaveRecords(array $params)
    {
        // 請假起始結束時間判斷
        if ( strtotime($params['start_date']) > strtotime($params['end_date']) ) {
            throw new CreateLeaveRecordExceptions('起始時間大於結束時間');
        }

        // 行事曆未建立請假日期 (因為行事曆通常為整年份建立，因此只檢查頭尾日期當作判斷[未考慮行事曆日期有跳日狀況])
        $start_date_exists = $this->CalendarRepository->getCalendarByDateRange($params['start_date'], $params['start_date']);
        $end_date_exists = $this->CalendarRepository->getCalendarByDateRange($params['end_date'], $params['end_date']);
        if ( $start_date_exists->isEmpty() || $end_date_exists->isEmpty() ) {
            throw new CreateLeaveRecordExceptions('指定日期區間行事曆尚未建立');
        }

        // 請假時間重疊判斷
        $isConflict = $this->LeaveRecordsRepository->getLeaveRecordConflict(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $params['user_id']
        );
        if ( !$isConflict->isEmpty() ) {
            throw new CreateLeaveRecordExceptions('請假日期與其他假單重疊');
        }

        // 請假計算天數
        $leave_record_date = $this->CalendarRepository->getCalendarByDateRange(
            $params['start_date'],
            $params['end_date']
        );
        // 請假起始或結束日期碰到假日
        if( $leave_record_date->first()['holiday'] == HolidayEnum::HOLIDAY || $leave_record_date->last()['holiday'] == HolidayEnum::HOLIDAY ) {
            throw new CreateLeaveRecordExceptions('請假起始或結束日為假日');
        }
        // 取工作日天數
        $willLeaveDays = $leave_record_date->where('holiday', HolidayEnum::WORKING)->count();
        // 起始日在下午扣除半天
        if ( $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
            $willLeaveDays -= 0.5;
        }
        // 結束日在上午扣除半天
        if ( $params['end_hour'] == LeaveTimeEnum::MORNING ) {
            $willLeaveDays -= 0.5;
        }

        // 請假假別時數上限(天)
        $leaveLimitDays = $this->getLeaveLimitDays($params['type']);
        // 請假假別運算年度
        $leavePeriod = $this->getLeavePeriod($params['type']);
        // 請假假別時數最小單位
        $leaveMinimum = $this->getLeaveMinimum($params['type']);
        // 休假天數轉為休假總時數
        $params['hours'] = $willLeaveDays * LeaveMinimumEnum::FULLDAY;

        // 本次請假的起始結束日期
        $leave_start_date = date_parse($params['start_date']);
        $leave_end_date = date_parse($params['end_date']);
        if( $leave_end_date['year'] - $leave_start_date['year'] >= 2 ) {
            throw new CreateLeaveRecordExceptions('請假期間超過兩年');
        }

        // 日本年度(目前僅特休，先列出組合)
        if( $leavePeriod == LeavePeriodEnum::JAPANYEAR ) {
            // 相同年份
            if( $leave_start_date['year'] == $leave_end_date['year'] ) {
                // 同年度
                if( ( $leave_start_date['month'] >= 4 && $leave_end_date['month'] >= 4 ) ||
                    ( $leave_start_date['month'] < 4 && $leave_end_date['month'] < 4 ) ) {
                    
                // 跨日本年度 
                } else {

                }
            } else {
                // 超過兩年
                if( ( $leave_start_date['month'] < 4 && $leave_end_date['month'] >= 4 ) ) {
                    return [
                        'status' => -1,
                        'message' => '請假期間超過兩年'
                    ];
                // 同年度
                } else if( $leave_start_date['month'] >= 4 && $leave_end_date['month'] < 4 ) {

                // 跨日本年度
                } else {

                }
            }
        // 一般年度
        } else {
            // 本筆假單跨越年度
            if( $leave_start_date['year'] != $leave_end_date['year'] ) {
                // User當年度假別紀錄 (包含跨年度假單)
                $leave_total_records_previous_year = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
                    $leave_start_date['year'].'-01-01',
                    $leave_start_date['year'].'-12-31',
                )->where('user_id', $params['user_id']);
                // User下年度假別紀錄 (包含跨年度假單)
                $leave_total_records_next_year = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
                    $leave_end_date['year'].'-01-01',
                    $leave_end_date['year'].'-12-31',
                )->where('user_id', $params['user_id']);

                // 前年度假總時數
                $leaved_hours_previous_year = $leave_total_records_previous_year->where('type', $params['type'])->sum('hours');
                // 扣除跨年時數
                $leaved_hours_previous_year -= $this->calculateRedundantLeaveHoursInYear($leave_total_records_previous_year, $params['type'], $leave_start_date['year']);
                // 下年度假總時數
                $leaved_hours_next_year = $leave_total_records_next_year->where('type', $params['type'])->sum('hours');
                // 扣除跨年時數
                $leaved_hours_next_year -= $this->calculateRedundantLeaveHoursInYear($leave_total_records_next_year, $params['type'], $leave_end_date['year']);

                // 本次請假前年度覆蓋天數
                $calendar_days_previous_year = $leave_record_date
                    ->where('date', '>=', $params['start_date'])
                    ->where('date', '<=',  $leave_start_date['year'].'-12-31')
                    ->where('holiday', HolidayEnum::WORKING)
                    ->count();
                if ( $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
                    $calendar_days_previous_year -= 0.5;
                }
                // 本次請假下年度覆蓋天數
                $calendar_days_next_year = $leave_record_date
                    ->where('date', '>=', $leave_end_date['year'].'-01-01')
                    ->where('date', '<=',  $params['end_date'])
                    ->where('holiday', HolidayEnum::WORKING)
                    ->count();
                if ( $params['end_hour'] == LeaveTimeEnum::MORNING ) {
                    $calendar_days_next_year -= 0.5;
                }
                // 家庭照顧假併入事假檢查
                if( $params['type'] == LeaveTypesEnum::FAMILYCARE ) {

                    // 前年度事假總時數
                    $leaved_simple_hours_previous_year = $leave_total_records_previous_year->where('type', LeaveTypesEnum::SIMPLE)->sum('hours');
                    // 扣除跨年時數
                    $leaved_simple_hours_previous_year -= $this->calculateRedundantLeaveHoursInYear($leave_total_records_previous_year, LeaveTypesEnum::SIMPLE, $leave_start_date['year']);
                    // 下年度事假總時數
                    $leaved_simple_hours_next_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::SIMPLE)->sum('hours');
                    // 扣除跨年時數
                    $leaved_simple_hours_next_year -= $this->calculateRedundantLeaveHoursInYear($leave_total_records_next_year, LeaveTypesEnum::SIMPLE, $leave_end_date['year']);

                    // 前年度家庭照顧假總時數
                    $leaved_familycare_hours_previous_year = $leave_total_records_previous_year->where('type', LeaveTypesEnum::FAMILYCARE)->sum('hours');
                    // 扣除跨年時數
                    $leaved_familycare_hours_previous_year -= $this->calculateRedundantLeaveHoursInYear($leave_total_records_previous_year, LeaveTypesEnum::FAMILYCARE, $leave_start_date['year']);
                    // 下年度家庭照顧假總時數
                    $leaved_familycare_hours_next_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::FAMILYCARE)->sum('hours');
                    // 扣除跨年時數
                    $leaved_familycare_hours_next_year -= $this->calculateRedundantLeaveHoursInYear($leave_total_records_next_year, LeaveTypesEnum::FAMILYCARE, $leave_end_date['year']);

                    // 前年度或下年度合併事假超過上限
                    if( $leaved_simple_hours_previous_year + $leaved_familycare_hours_previous_year + $calendar_days_previous_year * LeaveMinimumEnum::FULLDAY > LeaveLimitEnum::SIMPLE * LeaveMinimumEnum::FULLDAY ||
                        $leaved_simple_hours_next_year + $leaved_familycare_hours_next_year + $calendar_days_next_year * LeaveMinimumEnum::FULLDAY > LeaveLimitEnum::SIMPLE * LeaveMinimumEnum::FULLDAY ) {
                        throw new CreateLeaveRecordExceptions('家庭照顧假合併事假時數超過上限');
                    }
                }
                // 前年度或下年度超過上限
                if( $leaved_hours_previous_year + $calendar_days_previous_year * LeaveMinimumEnum::FULLDAY > $leaveLimitDays * LeaveMinimumEnum::FULLDAY ||
                    $leaved_hours_next_year + $calendar_days_next_year * LeaveMinimumEnum::FULLDAY > $leaveLimitDays * LeaveMinimumEnum::FULLDAY ) {
                    //超過上限要標示的假別
                    if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                        $params['warning'] = '已超過上限';
                    } else if( $leaveLimitDays == LeaveLimitEnum::INFINITE ) {
                        // 沒有設定上限的假別
                    } else {
                        throw new CreateLeaveRecordExceptions('請假時數超過上限');
                    }
                }
            // 本筆假單同年度
            } else {
                // User指定年內所有假單紀錄 (包含跨年度假單)
                $leave_total_records = $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
                    $leave_start_date['year'].'-01-01',
                    $leave_start_date['year'].'-12-31'
                )->where('user_id', $params['user_id']);

                // 該假別的總時數
                $leaved_hours = $leave_total_records->where('type', $params['type'])->sum('hours');
                // 扣除跨年時數
                $leaved_hours -= $this->calculateRedundantLeaveHoursInYear($leave_total_records, $params['type'], $leave_start_date['year']);

                // 生理假 (因無法跨年度僅檢查同年度假單即可)
                if( $params['type'] == LeaveTypesEnum::PERIOD ) {
                    // 當月最後一日
                    $last_date_in_month = date("Y-m-t", $start_date);
                    // 當月生理假總時數
                    $leaved_month_hours = $leave_total_records
                        ->where('type', $params['type'])
                        ->where('start_date', '>=', $leave_start_date['year'].'-'.$leave_start_date['month'].'-01')
                        ->where('end_date', '<=', $last_date_in_month)
                        ->sum('hours');
                    // 當月超過一天
                    if( $params['hours'] + $leaved_month_hours > LeaveMinimumEnum::FULLDAY ) {
                        throw new CreateLeaveRecordExceptions('生理假每月上限一日');
                    }
                    // 計算整年度生理假時數
                    $leaved_period_hours = $leave_total_records
                        ->where('type', $params['type'])
                        ->where('start_date', '>=', $leave_start_date['year'].'-01-01')
                        ->where('end_date', '<=', $leave_start_date['year'].'-12-31')
                        ->sum('hours');
                    // 包含此次生理假累計總時數
                    $willLeavePeriodHours = $leaved_period_hours + $params['hours'];
                    // 三天以上開始要併入病假判斷 (第四天開始計算)
                    $combine_sick_hours =  $willLeavePeriodHours - LeaveMinimumEnum::FULLDAY * 3;
                    if( $combine_sick_hours > 0 ) {
                        $leaved_sick_hours = $leave_total_records->where('type', LeaveTypesEnum::SICK)->sum('hours');
                        // 扣除跨年時數
                        $leaved_sick_hours -= $this->calculateRedundantLeaveHoursInYear($leave_total_records, LeaveTypesEnum::SICK, $leave_start_date['year']);
                        // 合併病假超過上限
                        if( $combine_sick_hours + $leaved_sick_hours > LeaveLimitEnum::SICK * LeaveMinimumEnum::FULLDAY ) {
                            $params['warning'] = '已超過上限';
                        }
                    }
                }
                // 家庭照顧假併入事假檢查
                if( $params['type'] == LeaveTypesEnum::FAMILYCARE ) {
                    // 當年度事假總時數
                    $leaved_simple_hours = $leave_total_records->where('type', LeaveTypesEnum::SIMPLE)->sum('hours');
                    // 扣除跨年時數
                    $leaved_simple_hours -= $this->calculateRedundantLeaveHoursInYear($leave_total_records, LeaveTypesEnum::SIMPLE, $leave_start_date['year']);
                    // 當年度家庭照顧假總時數
                    $leaved_familycare_hours =  $leave_total_records->where('type', LeaveTypesEnum::FAMILYCARE)->sum('hours');
                    // 扣除跨年時數
                    $leaved_familycare_hours -= $this->calculateRedundantLeaveHoursInYear($leave_total_records, LeaveTypesEnum::FAMILYCARE, $leave_start_date['year']);
                    // 合併事假超過上限
                    if( $leaved_simple_hours + $leaved_familycare_hours + $params['hours'] > LeaveLimitEnum::SIMPLE * LeaveMinimumEnum::FULLDAY ) {
                        throw new CreateLeaveRecordExceptions('家庭照顧假合併事假時數超過上限');
                    }
                }
                // 超過上限
                if( $leaved_hours + $params['hours'] > $leaveLimitDays * LeaveMinimumEnum::FULLDAY ) {
                    // 超過上限要標示的假別
                    if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                        $params['warning'] = '已超過上限';
                    } else if( $leaveLimitDays == LeaveLimitEnum::INFINITE ) {
                        // 沒有設定上限的假別
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
