<?php

namespace App\Repositories;

use App\Models\Calendar;
use App\Repositories\Imports\CalendarImport;

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

    public function getCalendarByYear($year)
    {
        return $this->model->select('*')->where(DB::raw('YEAR(date)'), $year);
    }

    public function getCalendarDistinctYears()
    {
        return $this->model->select(DB::raw('YEAR(date) as years'))->distinct(DB::raw('YEAR(date)'));
    }

    public function updateCalendarByDate($date, $holiday, $comment)
    {
        $this->model->where('date', $date)->update([
            'holiday' => $holiday,
            'comment' => $comment
        ]);
    }
}