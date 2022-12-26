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
            'Limit' => LeaveLimitEnum::PRENTAL, 
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
    protected function getDistinctYears(Collection $leaveRecords)
    {
        $startDateAllYears = $leaveRecords->map(function($item, $key) {
            return ['year' => date_parse($item['start_date'])['year']];
        });
        $endDateAllYears = $leaveRecords->map(function($item, $key) {
            return ['year' => date_parse($item['end_date'])['year']];
        });

        return $startDateAllYears->merge($endDateAllYears)->unique('year')->toArray();
    }

    // 取得所有假別年度總時數
    protected function getDistinctTypeHours(Collection $leaveRecords, int $userId, string $firstDateInYear)
    {
        $allTypes = $leaveRecords->map(function($item, $key) {
            return ['type' => $item['type']];
        })->unique('type')->values();
        $leavedHours = $allTypes->map(function($item, $key) use($userId, $firstDateInYear) {
            return [
                'type' => $item['type'],
                'hours' => $this->getUserLeavedHoursByTypeAndDateRange($userId, $item['type'], $this->getYearHeadTailDate($firstDateInYear, $item['type']))
            ];
        });
        return $leavedHours->toArray();
    }

    // 取得指定日期區間的假單紀錄
    protected function getLeaveRecordsByDateRange(Collection $dateRange)
    {
        return $this->LeaveRecordsRepository->getLeaveRecordsByDateRange(
            $dateRange['start_date'],
            $dateRange['end_date']
        );
    }

    // 取得所有假單
    public function getLeaveRecordsByYear(int $year)
    {
        $firstDateInYear = date("Y-m-d", strtotime($year.'-01-01')); // 預設為該年的1/1
        $leaveRecords = $this->getLeaveRecordsByDateRange($this->getYearHeadTailDate($firstDateInYear, LeaveTypesEnum::SIMPLE, true));
        return [
            'leaveCalendar' => $leaveRecords,
            'leaveCalendarYears' => $this->getDistinctYears($leaveRecords),
            'leaveRecordYear' => $year
        ];
    }

    // 取得所有假單 by user_id
    public function getLeaveRecordsByUserID(int $userId, int $year)
    {
        $firstDateInYear = date("Y-m-d", strtotime($year.'-01-01')); // 預設為該年的1/1
        $leaveRecords = $this->getLeaveRecordsByDateRange($this->getYearHeadTailDate($firstDateInYear, LeaveTypesEnum::SIMPLE, true))->where('user_id', $userId);
        return [
            'leaveCalendar' =>  $leaveRecords,
            'leaveCalendarYears' => $this->getDistinctYears($leaveRecords),
            'leaveHoursList' => $this->getDistinctTypeHours($leaveRecords, $userId, $firstDateInYear),
            'leaveRecordYear' => $year
        ];
    }

    // 以指定日期取得該月起始與結束日
    public function getMonthHeadTailDate(string $date)
    {
        $firstDateInMonth = date('Y-m-01',strtotime($date));
        $lastDateInMonth = date("Y-m-t", strtotime($date));

        return new Collection(['start_date' => $firstDateInMonth, 'end_date' => $lastDateInMonth]);
    }

    // 以指定日期取得假別計算年度起始結束日期
    public function getYearHeadTailDate(string $date, int $type, bool $isDefault = false)
    {
        $parseDate = date_parse($date);
        if($isDefault) { // 預設回傳一般年度計算區間 (+1年~-1年)
            return new Collection(['start_date' => ($parseDate['year']-1).'-01-01', 'end_date' => ($parseDate['year']+1).'-12-31']);
        }
        switch($this->LEAVE_CONFIG_ARRAY[$type]['Period']) {
        case LeavePeriodEnum::SIMPLEYEAR:
            return new Collection(['start_date' => $parseDate['year'].'-01-01', 'end_date' => $parseDate['year'].'-12-31']);
        case LeavePeriodEnum::JAPANYEAR: {
                if($parseDate['month'] > 3) {
                    return new Collection(['start_date' => $parseDate['year'].'-04-01', 'end_date' => ($parseDate['year']+1).'-03-31']);
                } else {
                    return new Collection(['start_date' => ($parseDate['year']-1).'-04-01', 'end_date' => $parseDate['year'].'-03-31']);
                }
            }
        }
    }

    // 以開始與結束時間取得指定區間範圍前中後三個工作天時數
    public function getWorkHoursSeprateByDateRange(string $startDate, string $endDate, int $startHour, int $endHour, Collection $dateRange)
    {
        $calendar = $this->CalendarRepository->getCalendarByDateRange($startDate, $endDate);
        $workHoursBeforeRange = 0;
        $workHoursInRange = 0;
        $workHoursAfterRange = 0;
        foreach($calendar as $rows) {
            if($rows['holiday'] == HolidayEnum::HOLIDAY) continue;
            if($rows['date'] == $startDate && $startHour == LeaveTimeEnum::AFTERNOON) {
                $addHours = LeaveMinimumEnum::HALFDAY;
            }else if($rows['date'] == $endDate && $endHour == LeaveTimeEnum::MORNING) {
                $addHours = LeaveMinimumEnum::HALFDAY;
            }else {
                $addHours = LeaveMinimumEnum::FULLDAY;
            }
            if(strtotime($rows['date']) < strtotime($dateRange['start_date'])) {
                $workHoursBeforeRange += $addHours;
            }else if(strtotime($rows['date']) > strtotime($dateRange['end_date'])) {
                $workHoursAfterRange += $addHours;
            }else {
                $workHoursInRange += $addHours;
            }
        }
        return new Collection([ "hours" => $workHoursInRange, "before_hours" => $workHoursBeforeRange, "after_hours" => $workHoursAfterRange]);
    }

    // 取得User指定區間的假別已修假總時數
    public function getUserLeavedHoursByTypeAndDateRange(int $userId, int $type, Collection $dateRange)
    {
        $leaveRecords = $this->getLeaveRecordsByDateRange($dateRange)->where('user_id', $userId);
        $leavedHours = $leaveRecords->where('type', $type)->sum('hours');
        // 找出日期區間外的假單
        $leaveRecordsOutsideRange = $leaveRecords->where('type', $type)->filter(function($item) use($dateRange) {
            return $item['start_date'] < $dateRange['start_date'] || $item['end_date'] > $dateRange['end_date'];
        });
        // 扣除日期區間外的時數
        foreach($leaveRecordsOutsideRange as $rows) {
            $leavedOutsideRangeHours = $this->getWorkHoursSeprateByDateRange(
                $rows['start_date'],
                $rows['end_date'],
                $rows['start_hour'],
                $rows['end_hour'],
                $dateRange
            );
            $leavedHours -= $leavedOutsideRangeHours['before_hours'] + $leavedOutsideRangeHours['after_hours'];
        }
        return $leavedHours;
    }

    // 判斷生理假是否超過月份上限
    public function checkPeriodLeaveMonthIsOverLimit(int $userId, int $type, int $willLeaveHours, string $date)
    {
        $calculateDateRange = $this->getMonthHeadTailDate($date);
        $leavedHours = $this->getUserLeavedHoursByTypeAndDateRange($userId, $type, $calculateDateRange);

        if($leavedHours + $willLeaveHours > LeaveMinimumEnum::FULLDAY) {
            throw new CreateLeaveRecordExceptions('生理假超過每月1日上限');
        }
    }

    // 判斷生理假合併病假是否超過年度上限
    public function checkPeriodLeaveCombineSickIsOverLimit(int $userId, int $willLeaveHours, string $willLeaveStartDate)
    {
        $calculateDateRange = $this->getYearHeadTailDate($willLeaveStartDate, LeaveTypesEnum::PERIOD);
        $leaveTotalHours = $willLeaveHours + $this->getUserLeavedHoursByTypeAndDateRange($userId, LeaveTypesEnum::PERIOD, $calculateDateRange);
        $leavedLimitHours = $leaveTotalHours - LeaveMinimumEnum::FULLDAY * 3; // 超過3日合併病假
        if($leavedLimitHours > 0) {
            if($this->checkLeaveYearIsOverLimit($userId, LeaveTypesEnum::SICK, $leavedLimitHours, $willLeaveStartDate))
                return true;
        }
        return false;
    }

    // 判斷假別加總時數是否超過年度上限
    public function checkLeaveYearIsOverLimit(int $userId, int $type, int $willLeaveHours, string $willLeaveStartDate)
    {
        $leaveLimitDays = $this->LEAVE_CONFIG_ARRAY[$type]['Limit'];
        if($leaveLimitDays == LeaveLimitEnum::INFINITE) return false;
        $calculateDateRange = $this->getYearHeadTailDate($willLeaveStartDate, $type);
        $leaveTotalHours = $willLeaveHours + $this->getUserLeavedHoursByTypeAndDateRange($userId, $type, $calculateDateRange);
        if($type == LeaveTypesEnum::FAMILYCARE) {
            if($this->checkLeaveYearIsOverLimit($userId, LeaveTypesEnum::SIMPLE, $willLeaveHours, $willLeaveStartDate)) {
                throw new CreateLeaveRecordExceptions('合併事假時數超過上限');
            }
        }
        if($type == LeaveTypesEnum::SIMPLE) {
            // 事假要納入家庭照顧假計算
            $leaveTotalHours += $this->getUserLeavedHoursByTypeAndDateRange($userId, LeaveTypesEnum::FAMILYCARE, $calculateDateRange);
        }
        if($type == LeaveTypesEnum::SICK) {
            $leavedPeriodHours = $this->getUserLeavedHoursByTypeAndDateRange($userId, LeaveTypesEnum::PERIOD, $calculateDateRange);
            if($leavedPeriodHours > LeaveMinimumEnum::FULLDAY * 3) {
                $leaveTotalHours += $leavedPeriodHours - LeaveMinimumEnum::FULLDAY * 3;
            }
        }
        return $leaveTotalHours > $leaveLimitDays * LeaveMinimumEnum::FULLDAY;
    }

    public function createLeaveRecords(array $params)
    {
        if(strtotime($params['start_date']) > strtotime($params['end_date'])) {
            throw new CreateLeaveRecordExceptions('起始時間大於結束時間');
        }
        if(!$this->LeaveRecordsRepository->getLeaveRecordConflict($params)->isEmpty()) {
            throw new CreateLeaveRecordExceptions('請假日期與其他假單重疊');
        }
        $calendar = $this->CalendarRepository->getCalendarByDateRange($params['start_date'], $params['end_date']);
        if($calendar->first()['holiday'] != HolidayEnum::WORKING || $calendar->last()['holiday'] != HolidayEnum::WORKING) {
            throw new CreateLeaveRecordExceptions('請假起始或結束日非工作日');
        }
        $leaveHoursInYear = $this->getWorkHoursSeprateByDateRange(
            $params['start_date'],
            $params['end_date'],
            $params['start_hour'],
            $params['end_hour'],
            $this->getYearHeadTailDate($params['start_date'], $params['type'])
        );
        if($params['type'] == LeaveTypesEnum::PERIOD) {
            $leaveHoursInMonth = $this->getWorkHoursSeprateByDateRange(
                $params['start_date'],
                $params['end_date'],
                $params['start_hour'],
                $params['end_hour'],
                $this->getMonthHeadTailDate($params['start_date'])
            );
            $this->checkPeriodLeaveMonthIsOverLimit($params['user_id'], $params['type'], $leaveHoursInMonth['hours'], $params['start_date']);                                                   // 當月份
            if($leaveHoursInMonth['after_hours'] != 0) $this->checkPeriodLeaveMonthIsOverLimit($params['user_id'], $params['type'], $leaveHoursInMonth['after_hours'], $params['end_date']);    // 下月份
            if($this->checkPeriodLeaveCombineSickIsOverLimit($params['user_id'], $leaveHoursInYear['hours'], $params['start_date']) ||                                                          // 當年度
                ($leaveHoursInYear['after_hours'] != 0 && $this->checkPeriodLeaveCombineSickIsOverLimit($params['user_id'], $leaveHoursInYear['after_hours'], $params['end_date']))             // 下年度
            ) {
                $params['warning'] = '合併病假已超過上限特別標示';
            }
        }
        if($this->checkLeaveYearIsOverLimit($params['user_id'], $params['type'], $leaveHoursInYear['hours'], $params['start_date']) ||                                                  // 當年度
            ($leaveHoursInYear['after_hours'] != 0 && $this->checkLeaveYearIsOverLimit($params['user_id'], $params['type'], $leaveHoursInYear['after_hours'], $params['end_date']))     // 下年度
        ) {
            if($params['type'] == LeaveTypesEnum::TOCOLYSIS || $params['type'] == LeaveTypesEnum::SICK) {
                $params['warning'] = '已超過上限特別標示';
            }else {
                throw new CreateLeaveRecordExceptions('請假時數超過上限');
            }
        }

        $params['hours'] = $leaveHoursInYear->sum();

        $this->LeaveRecordsRepository->createLeaveRecords($params);

        return [ 'status' => 0, 'message' => '建立成功'];
    }

    public function updateLeaveRecord(array $params)
    {
        $this->LeaveRecordsRepository->updateLeaveRecord($params);

        return [ 'status' => 0, 'message' => '修改假單狀態完成'];
    }
}
