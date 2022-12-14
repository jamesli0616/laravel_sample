<?php

namespace App\Repositories;

use App\Models\Calendar;
use Illuminate\Support\Facades\DB;

class CalendarRepository
{
    protected $model;

    public function __construct(
        Calendar $Calendar
    )
    {
        $this->model = $Calendar;
    }

    // 取得所有calendar
    public function getCalendar()
    {
        return $this->model;
    }

    // 取得行事曆 by 年份
    public function getCalendarByYear(int $year)
    {
        return $this->model->whereBetween('date', [
            $year.'-01-01',
            ($year+1).'-01-01'
        ]);
    }

    // 取得calendar指定日期範圍
    public function getCalendarByDateRange(string $start_date, string $end_date)
    {
        return $this->model->whereBetween('date', [$start_date, $end_date]);
    }

    // 更新行事曆內容
    public function updateCalendarByDate(string $date, int $holiday, string $comment)
    {
        $this->model->where('date', $date)->update([
            'holiday' => $holiday,
            'comment' => $comment 
        ]);
    }
}