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
    
    public function updateCalendarByDate(mixed $params)
    {
        if ( $params['comment'] == null ) {
            $params['comment'] = '';
        }

        $this->CalendarRepository->updateCalendarByDate(
            $params['edit_date'],
            $params['holiday'],
            $params['comment']
        );

        return [
            'status' => 0,
            'message' => '更新行事曆完成'
        ];
    }
}
