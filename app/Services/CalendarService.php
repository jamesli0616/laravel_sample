<?php

namespace App\Services;

use App\Repositories\CalendarRepository;
use Illuminate\Database\Eloquent\Collection;

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
    protected function distinctYears(Collection $record_results)
    {
        $distinct_years = $record_results->transform( function($item, $key) {
            return ['year' => date_parse($item['date'])['year']];
        });
        return $distinct_years->unique('year')->toArray();
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
    
    public function updateCalendarByDate(array $params)
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
