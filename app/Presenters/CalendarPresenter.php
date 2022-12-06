<?php

namespace App\Presenters;

class CalendarPresenter
{
    public function holiday($holiday)
    {
        switch($holiday)
        {
        case 0:
            return '工作日';
        case 2:
            return '例假日';
        }
    }
}