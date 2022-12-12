<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use BenSampo\Enum\Rules\EnumValue;
use App\Enums\LeaveTypesEnum;

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
            'type' => ['required', new EnumValue(LeaveTypesEnum::class, false)],
            'start_date' => 'required',
            'start_hour' => 'required',
            'end_date' => 'required',
            'end_hour' => 'required',
            'comment' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'comment' => '請假事由空白',
            'start_date' => '未設定起始日期',
            'end_date' => '未設定結束日期',
        ];
    }
}
