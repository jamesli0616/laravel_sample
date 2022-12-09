<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Facades\DB;

class LeaveDateCreateRule implements InvokableRule
{
   /**
    * Run the validation rule.
    *
    * @param  string  $attribute
    * @param  mixed  $value
    * @param  \Closure  $fail
    * @return void
    */
    public function __invoke($attribute, $value, $fail)
    {
        if ($value == null) {
            return $fail('日期空白');
        }
        $valid = DB::table('calendar')->select('holiday')->where('date', $value)->get();
        if ($valid[0]->holiday == 2) {
            return $fail('日期為假日');
        }
    }
}
