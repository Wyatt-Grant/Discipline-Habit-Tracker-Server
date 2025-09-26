<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|digits_between:1,2',
            'value' => 'required',
            'target_count' => 'required',
            'max_count' => 'required|gte:target_count',
            'rrule' => 'required|string',
            'start' => 'required',
            'end' => 'nullable|after_or_equal:start',
            'remove_points_on_failure' => 'required|integer',
            'remind' => 'required|integer',
            'remind_time' => 'required_if:remind,1',
            'restrict' => 'required|integer',
            'restrict_before' => 'required|integer',
            'restrict_time' => 'required_if:restrict,1',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => implode(" ", $validator->errors()->all()),
        ], 572));
    }
}
