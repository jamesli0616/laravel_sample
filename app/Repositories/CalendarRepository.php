<?php

namespace App\Repositories;

use App\Models\Calendar;
use App\Repositories\Imports\CalendarImport;
use Maatwebsite\Excel\Facades\Excel;
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

    public function importCalendarCSV($csvFiles)
    {
        Excel::import(new CalendarImport, $csvFiles);
    }

    public function getCalendarByYear($year)
    {
        return $this->model->select('*')->where(DB::raw('YEAR(date)'), $year);
    }

    public function getCalendarDistinctYears()
    {
        return $this->model->select(DB::raw('YEAR(date) as years'))->distinct(DB::raw('YEAR(date)'));
    }

    public function updateCommentByDate($date, $comment)
    {
        $this->model->where('date', $date)->update([
            'comment' => $comment
        ]);
    }

    public function updateHolidayByDate($date, $holiday)
    {
        $this->model->where('date', $date)->update([
            'holiday' => $holiday
        ]);
    }
}