<?php

namespace App\Services;

use App\Repositories\CalendarRepository;

class CalendarService
{
    protected $CalendarRepo;

    public function __construct(
        CalendarRepository $CalendarRepository
    )
	{
        $this->CalendarRepo = $CalendarRepository;
	}

    public function displayCalendarPage($year)
    {
        return [
            'calendarDate' => $this->CalendarRepo->getCalendarByYear($year)->get(),
            'calendarYears' => $this->CalendarRepo->getCalendarDistinctYears()->get()
        ];
    }

    public function updateCalendarByDate($date, $holiday, $comment)
    {
        $this->CalendarRepo->updateCalendarByDate($date, $holiday, $comment);
    }
}
