<?php

namespace App\Validator;

use Illuminate\Http\Request;
use Validator;
use BenSampo\Enum\Rules\EnumValue;
use App\Enums\HolidayEnum;

class RequestValidator
{
    public function checkRequestYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')+1),
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function checkUploadCSVFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upfile' => 'required|file|mimes:csv'
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function checkUpdateCalendar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'edit_date' => 'required',
            'holiday' => ['required', new EnumValue(HolidayEnum::class, false)],
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }
}
