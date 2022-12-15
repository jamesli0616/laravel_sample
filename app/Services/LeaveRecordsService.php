<?php

namespace App\Services;

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
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByDataRange(
                $year.'-01-01',
                $year.'-12-31'
            ),
            'leaveCalendarYears' => $this->distinctYears($this->LeaveRecordsRepository->getLeaveRecordsByDataRange()),
            'leaveRecordYear' => $year
        ];
    }

    public function getLeaveRecordsByUserID(int $user_id, int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID(
                $user_id,
                $year.'-01-01',
                $year.'-12-31'
            ),
            'leaveCalendarYears' => $this->distinctYears($this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID($user_id)),
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
        // 行事曆未建立請假日期 (因為行事曆通常為整年份建立，因此只檢查頭尾日期當作判斷[未考慮行事曆日期有跳日狀況])
        $start_date_exists = $this->CalendarRepository->getCalendarByDateRange($params['start_date'], $params['start_date']);
        $end_date_exists = $this->CalendarRepository->getCalendarByDateRange($params['end_date'], $params['end_date']);
        if ( $start_date_exists->isEmpty() || $end_date_exists->isEmpty() ) {
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
        );
        if ( !$isConflict->isEmpty() ) {
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
        $willLeaveDays = $leave_record_date->count();
        // 起始日在下午扣除半天
        if ( $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
            $willLeaveDays -= 0.5;
        }
        // 結束日在上午扣除半天
        if ( $params['end_hour'] == LeaveTimeEnum::MORNING ) {
            $willLeaveDays -= 0.5;
        }
        // 請假起始或結束日期碰到假日
        if( $leave_record_date->first()['holiday'] == HolidayEnum::HOLIDAY || $leave_record_date->last()['holiday'] == HolidayEnum::HOLIDAY ) {
            return [
                'status' => -1,
                'message' => '請假起始或結束日為假日'
            ];
        }
        // 請假範圍扣除假日天數
        foreach($leave_record_date as $rows) {
            if( $rows['holiday'] == HolidayEnum::HOLIDAY ) {
                $willLeaveDays--;
            }
        }
        if( $willLeaveDays <= 0 ) {
            return [
                'status' => -1,
                'message' => '請假時間小於等於0'
            ];
        }
        // 請假假別時數上限判斷
        $leaveLimitDays = 0; // 上限(天)，預設0
        $leavePeriod = LeavePeriodEnum::SIMPLEYEAR; // 運算年度
        $leaveMinimum = LeaveMinimumEnum::HALFDAY; // 最小單位
        switch($params['type']) {
        case LeaveTypesEnum::SICK:
            $leaveLimitDays = LeaveLimitEnum::SICK;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::SIMPLE:
            $leaveLimitDays = LeaveLimitEnum::SIMPLE;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::PERIOD:
            $leaveLimitDays = LeaveLimitEnum::PERIOD;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::FUNERAL:
            $leaveLimitDays = LeaveLimitEnum::INFINITE;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::INJURY:
            $leaveLimitDays = LeaveLimitEnum::INFINITE;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::MATERNITY:
            $leaveLimitDays = LeaveLimitEnum::INFINITE;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::TOCOLYSIS:
            $leaveLimitDays = LeaveLimitEnum::TOCOLYSIS;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::PATERNITY:
            $leaveLimitDays = LeaveLimitEnum::PATERNITY;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::FAMILYCARE:
            $leaveLimitDays = LeaveLimitEnum::FAMILYCARE;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::OFFICIAL:
            $leaveLimitDays = LeaveLimitEnum::INFINITE;
            $leavePeriod = LeavePeriodEnum::SIMPLEYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        case LeaveTypesEnum::SPECIAL:
            $leaveLimitDays = LeaveLimitEnum::SPECIAL;
            $leavePeriod = LeavePeriodEnum::JAPANYEAR;
            $leaveMinimum = LeaveMinimumEnum::HALFDAY;
            break;
        }
        // 休假天數轉為休假總時數
        $params['hours'] = $willLeaveDays * LeaveMinimumEnum::FULLDAY;

        // 本次請假的起始結束日期
        $leave_start_date = date_parse($params['start_date']);
        $leave_end_date = date_parse($params['start_date']);
        if( $leave_end_date['year'] - $leave_start_date['year'] >= 2 ) {
            return [
                'status' => -1,
                'message' => '請假期間超過兩年'
            ];
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
            // 跨年度請假
            if( $leave_start_date['year'] != $leave_end_date['year'] ) {

                // User當年度假別紀錄
                $leave_total_records_previous_year = $this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID(
                    $params['user_id'],
                    $leave_start_date['year'].'-01-01',
                    $leave_start_date['year'].'-12-31',
                );
                // User下年度假別紀錄
                $leave_total_records_next_year = $this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID(
                    $params['user_id'],
                    $leave_end_date['year'].'-01-01',
                    $leave_end_date['year'].'-12-31',
                );
                // 前年度假總時數
                $leaved_hours_previous_year = $leave_total_records_previous_year->where('type', $params['type'])->sum('hours');
                // 結束日期跨年，要扣除對應時數
                $check_past_year = $leave_total_records_previous_year->where('type', $params['type'])->where('end_date', '>', $leave_start_date['year'].'-12-31');
                if( !$check_past_year->isEmpty() ) {
                    // $leaved_hours_previous_year 扣掉跨年時數
                    dd('扣掉跨年時數');
                }
                $leaved_hours_next_year = $leave_total_records_next_year->where('type', $params['type'])->sum('hours');
                // 結束日期跨年，要扣除對應時數
                $check_past_year = $leave_total_records_next_year->where('type', $params['type'])->where('end_date', '>', $leave_end_date['year'].'-12-31');
                if( !$check_past_year->isEmpty() ) {
                    // $leaved_hours_next_year 扣掉跨年時數
                    dd('扣掉跨年時數');
                }

                // 本次請假前年度覆蓋天數
                $calendar_days_previous_year = $this->CalendarRepository->getCalendarByDateRange(
                    $params['start_date'],
                    $leave_start_date['year'].'-12-31'
                )->count();
                if ( $params['start_hour'] == LeaveTimeEnum::AFTERNOON ) {
                    $calendar_days_previous_year -= 0.5;
                }
                // 本次請假下年度覆蓋天數
                $calendar_days_next_year = $this->CalendarRepository->getCalendarByDateRange(
                    $leave_end_date['year'].'-01-01',
                    $params['end_date']
                )->count();
                if ( $params['end_hour'] == LeaveTimeEnum::MORNING ) {
                    $calendar_days_next_year -= 0.5;
                }
                // 家庭照顧假併入事假檢查
                if( $params['type'] == LeaveTypesEnum::FAMILYCARE ) {
                    // 前年度事假總時數
                    $leaved_simple_hours_previous_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::SIMPLE)->sum('hours');
                    // 結束日期跨年，要扣除對應時數
                    $check_past_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::SIMPLE)->where('end_date', '>', $leave_start_date['year'].'-12-31');
                    if( !$check_past_year->isEmpty() ) {
                        // $leaved_simple_hours_previous_year 扣掉跨年時數
                        dd('扣掉跨年時數');
                    }
                    // 下年度事假總時數
                    $leaved_simple_hours_next_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::SIMPLE)->sum('hours');
                    // 結束日期跨年，要扣除對應時數
                    $check_past_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::SIMPLE)->where('end_date', '>', $leave_end_date['year'].'-12-31');
                    if( !$check_past_year->isEmpty() ) {
                        // $leaved_simple_hours_next_year 扣掉跨年時數
                        dd('扣掉跨年時數');
                    }
                    // 前年度家庭照顧假總時數
                    $leaved_familycare_hours_previous_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::FAMILYCARE)->sum('hours');
                    // 結束日期跨年，要扣除對應時數
                    $check_past_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::FAMILYCARE)->where('end_date', '>', $leave_start_date['year'].'-12-31');
                    if( !$check_past_year->isEmpty() ) {
                        // $leaved_familycare_hours_previous_year 扣掉跨年時數
                        dd('扣掉跨年時數');
                    }
                    // 下年度家庭照顧假總時數
                    $leaved_familycare_hours_next_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::FAMILYCARE)->sum('hours');
                    // 結束日期跨年，要扣除對應時數
                    $check_past_year = $leave_total_records_next_year->where('type', LeaveTypesEnum::FAMILYCARE)->where('end_date', '>', $leave_end_date['year'].'-12-31');
                    if( !$check_past_year->isEmpty() ) {
                        // $leaved_familycare_hours_next_year 扣掉跨年時數
                        dd('扣掉跨年時數');
                    }
                    // 前年度或下年度合併事假超過上限
                    if( $leaved_simple_hours_previous_year + $leaved_familycare_hours_previous_year + $calendar_days_previous_year * LeaveMinimumEnum::FULLDAY > LeaveLimitEnum::SIMPLE * LeaveMinimumEnum::FULLDAY ||
                        $leaved_simple_hours_next_year + $leaved_familycare_hours_next_year + $calendar_days_next_year * LeaveMinimumEnum::FULLDAY > LeaveLimitEnum::SIMPLE * LeaveMinimumEnum::FULLDAY ) {
                        return [
                            'status' => -1,
                            'message' => '家庭照顧假合併事假時數超過上限'
                        ];
                    }
                }
                // 前年度或下年度超過上限
                if( $leaved_hours + $calendar_days_previous_year * LeaveMinimumEnum::FULLDAY > $leaveLimitDays * LeaveMinimumEnum::FULLDAY ||
                    $leaved_hours + $calendar_days_next_year * LeaveMinimumEnum::FULLDAY > $leaveLimitDays * LeaveMinimumEnum::FULLDAY ) {
                    //超過上限要標示的假別
                    if( $params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK ) {
                        $params['warning'] = '已超過上限';
                    } else if( $leaveLimitDays == LeaveLimitEnum::INFINITE ) {
                        // 沒有設定上限的假別
                    } else {
                        return [
                            'status' => -1,
                            'message' => '請假時數超過上限'
                        ];
                    }
                }
            // 同年度內請假
            } else {
                // User當年度假別紀錄
                $leave_total_records = $this->LeaveRecordsRepository->getLeaveRecordsByDataRangeAndUserID(
                    $params['user_id'],
                    $leave_start_date['year'].'-01-01',
                    $leave_start_date['year'].'-12-31',
                );
                $leaved_hours = $leave_total_records->where('type', $params['type'])->sum('hours');
                // 結束日期跨年，要扣除對應時數
                $check_past_year = $leave_total_records->where('type', $params['type'])->where('end_date', '>', $leave_start_date['year'].'-12-31');
                if( !$check_past_year->isEmpty() ) {
                    // $leaved_hours 扣掉跨年時數
                    dd('扣掉跨年時數');
                }
                // 生理假 (因無法跨年度僅在此檢查)
                if( $params['type'] == LeaveTypesEnum::PERIOD ) {
                    $last_date_in_month = date("Y-m-t", $start_date);
                    // 當月生理假總時數
                    $leaved_month_hours = $leave_total_records->where('type', $params['type'])
                        ->where('start_date', '>=', $leave_start_date['year'].'-'.$leave_start_date['month'].'-01')
                        ->where('end_date', '<=', $last_date_in_month)
                        ->sum('hours');
                    // 當月超過一天
                    if( $params['hours'] + $leaved_month_hours > LeaveMinimumEnum::FULLDAY ) {
                        return [
                            'status' => -1,
                            'message' => '超過生理假每月上限一日'
                        ];
                    }
                    // 計算整年度生理假天數
                    $leaved_period_hours = $leave_total_records
                        ->where('start_date', '>=', $leave_start_date['year'].'-01-01')
                        ->where('end_date', '<=', $leave_start_date['year'].'-12-31')
                        ->sum('hours');
                    // 包含此次生理假累計總時數
                    $willLeavePeriodHours = $leaved_period_hours + $params['hours'];
                    // 三天以上開始要併入病假判斷
                    $combine_sick_hours =  $willLeavePeriodHours - LeaveMinimumEnum::FULLDAY * 3;
                    if( $combine_sick_hours > 0 ) {
                        $leaved_sick_hours = $leave_total_records->where('type', LeaveTypesEnum::SICK)->sum('hours');
                        // 結束日期跨年，要扣除對應時數
                        $check_past_year = $leave_total_records->where('type', LeaveTypesEnum::SICK)->where('end_date', '>', $leave_start_date['year'].'-12-31');
                        if( !$leaved_records_past_year->isEmpty() ) {
                            // $leaved_sick_hours 扣掉跨年時數
                            dd('扣掉跨年時數');
                        }
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
                    // 結束日期跨年，要扣除對應時數
                    $check_past_year = $leave_total_records->where('type', LeaveTypesEnum::SIMPLE)->where('end_date', '>', $leave_start_date['year'].'-12-31');
                    if( !$check_past_year->isEmpty() ) {
                        // $leaved_simple_hours 扣掉跨年時數
                        dd('扣掉跨年時數');
                    }
                    // 當年度家庭照顧假總時數
                    $leaved_familycare_hours =  $leave_total_records->where('type', LeaveTypesEnum::FAMILYCARE)->sum('hours');
                    // 結束日期跨年，要扣除對應時數
                    $check_past_year = $leave_total_records->where('type', LeaveTypesEnum::FAMILYCARE)->where('end_date', '>', $leave_start_date['year'].'-12-31');
                    if( !$check_past_year->isEmpty() ) {
                        // $leaved_familycare_hours 扣掉跨年時數
                        dd('扣掉跨年時數');
                    }
                    // 合併事假超過上限
                    if( $leaved_simple_hours + $leaved_familycare_hours + $params['hours'] > LeaveLimitEnum::SIMPLE * LeaveMinimumEnum::FULLDAY ) {
                        return [
                            'status' => -1,
                            'message' => '家庭照顧假合併事假時數超過上限'
                        ];
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
                        return [
                            'status' => -1,
                            'message' => '請假時數超過上限'
                        ];
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
