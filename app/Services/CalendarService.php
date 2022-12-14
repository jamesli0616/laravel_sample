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

    // 整理Calendar所有年份
    protected function distinctYears(mixed $record_results)
    {
        $years_array = [];
        foreach($record_results as $rows) {
            if (!in_array(date_parse($rows['date'])['year'], $years_array)) {
                array_push($years_array, date_parse($rows['date'])['year']);
            }
        }
        return $years_array;
    }

    public function displayCalendarPage(int $year)
    {
        return [
            'calendarDate' => $this->CalendarRepository->getCalendarByDateRange(
                $year.'-01-01',
                $year.'-12-31'
            ),
            'calendarYears' => $this->distinctYears($this->CalendarRepository->getCalendarByDateRange())
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
