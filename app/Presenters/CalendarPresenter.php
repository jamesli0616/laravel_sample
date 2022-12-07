<?php

namespace App\Presenters;

use App\Enums\HolidayEnum;

class CalendarPresenter
{
    public function holiday($holiday)
    {
        switch($holiday) {
        case HolidayEnum::WorkingDay:
            return '工作日';
        case HolidayEnum::NotDefine:
            return '未定義';
        case HolidayEnum::Holiday:
            return '例假日';
        }
    }
}