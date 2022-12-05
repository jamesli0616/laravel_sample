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

    public function getCalendarByYear($year)
    {

    }
}