<?php

namespace App\Imports;

use App\Models\Calendar;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;

class CalendarImport implements ToModel
{
    public function model(array $row)
    {
        return new Calendar([
           'date'       => $row[0],
           'weekdays'   => $row[1], 
           'holiday'    => $row[2],
           'comment'    => $row[3], 
        ]);
    }
}
