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

    // 取得該日假別狀態 by 日期
    public function getCalendarIsHolidayByDate(string $date)
    {
        return $this->model->select('holiday')->where('date', $date);
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