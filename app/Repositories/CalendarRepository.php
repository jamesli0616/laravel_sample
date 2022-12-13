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

    // 取得行事曆 by 年份
    public function getCalendarByYear(int $year)
    {
        return $this->model->select('*')->where(DB::raw('YEAR(date)'), $year);
    }

    // 取得所有行事曆年份
    public function getCalendarDistinctYears()
    {
        return $this->model->select(DB::raw('YEAR(date) as years'))->distinct(DB::raw('YEAR(date)'));
    }

    // 取得calendar內假日天數
    public function getHolidaysInCalendar(string $start_date, string $end_date)
    {
        return $this->model->select('*')->whereBetween('date', [$start_date, $end_date])->where('holiday', 2);
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