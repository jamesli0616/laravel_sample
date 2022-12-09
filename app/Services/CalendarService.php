<?php

namespace App\Services;

use App\Repositories\CalendarRepository;

class CalendarService
{
    protected $CalendarRepository;

    public function __construct(
        CalendarRepository $CalendarRepository
    )
	{
        $this->CalendarRepository = $CalendarRepository;
	}

    public function displayCalendarPage(int $year)
    {
        return [
            'calendarDate' => $this->CalendarRepository->getCalendarByYear($year)->get(),
            'calendarYears' => $this->CalendarRepository->getCalendarDistinctYears()->get()
        ];
    }
    
    public function updateCalendarByDate(string $date, int $holiday, string $comment)
    {
        $this->CalendarRepository->updateCalendarByDate($date, $holiday, $comment);
    }
}
