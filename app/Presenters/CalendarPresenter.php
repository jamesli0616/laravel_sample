<?php

namespace App\Presenters;

use App\Enums\HolidayEnum;

class CalendarPresenter
{
    public function holiday($holiday)
    {
        switch($holiday) {
        case HolidayEnum::WORKING:
            return '工作日';
        case HolidayEnum::UNDEFINE:
            return '未定義';
        case HolidayEnum::HOLIDAY:
            return '例假日';
        }
    }
}