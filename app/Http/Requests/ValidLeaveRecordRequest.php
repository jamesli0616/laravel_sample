<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use BenSampo\Enum\Rules\EnumValue;
use App\Enums\LeaveStatusEnum;

class ValidLeaveRecordRequest extends FormRequest
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
            'leave_id' => 'required',
            'valid_status' => ['required', new EnumValue(LeaveStatusEnum::class, false)],
        ];
    }
}
