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

    // 取得calendar指定日期範圍，預設為全撈
    public function getCalendarByDateRange(string $start_date = '1970-01-01', string $end_date = '2038-01-19')
    {
        return $this->model->whereBetween('date', [$start_date, $end_date])->get();
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