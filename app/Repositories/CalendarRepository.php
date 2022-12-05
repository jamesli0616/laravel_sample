<?php

namespace App\Repositories;

use App\Models\Calendar;

class CalendarRepository
{
    protected $CalendarRepo;

    public function __construct(
        Calendar $Calendar
    )
    {
        $this->CalendarRepo = $Calendar;
    }

    public function insertCalendar_bulk($records)
    {
        $head = 0;
        foreach($records as $rows)
        {
            if($head == 0)
            {
                $head++;
                continue;
            }
            $columns = explode(",", $rows);
            $calendar = new Calendar();
            $calendar->date = $columns[0];
            $calendar->weekdays = $columns[1];
            $calendar->holiday = $columns[2];
            $calendar->comment = $columns[3];
            $calendar->save();
        }
    }
}