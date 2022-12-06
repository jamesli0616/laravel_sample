<?php

namespace App\Imports;

use App\Models\Calendar;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Concerns\WithUpserts;

HeadingRowFormatter::default('none');

class CalendarImport implements ToModel, WithHeadingRow, WithUpserts
{
    public function model(array $row)
    {
        return new Calendar([
           'date'       => $row['西元日期'],
           'weekdays'   => $row['星期'], 
           'holiday'    => $row['是否放假'],
           'comment'    => $row['備註'], 
        ]);
    }

    public function uniqueBy()
    {
        return 'date';
    }
}
