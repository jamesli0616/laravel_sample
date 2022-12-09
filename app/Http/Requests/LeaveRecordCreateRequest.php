<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use BenSampo\Enum\Rules\EnumValue;
use App\Enums\LeaveRecordEnum;

class LeaveRecordCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'leave_date' => 'required',
            'leave_type' => ['required', new EnumValue(LeaveRecordEnum::class, false)],
            'leave_comment' => 'required',
            'leave_start' => 'required',
            'leave_period' => ['required',
                function($attribute, $value, $fail) {
                    $start_hr = $this['leave_start'];
                    if ($start_hr + $value > 18) {
                        return $fail('請假時間不符規定');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'leave_date' => '請假時間不得空白',
            'leave_comment' => '請假事由不得空白'
        ];
    }
}
