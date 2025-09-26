<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            'user_name' => 'required|unique:users,user_name',
            'role' => 'required|digits_between:1,2',
            'password' => 'required|string',
            'create_dynamic' => 'required|integer',
            'dynamic_name' => 'required_if:create_dynamic,1|string',
            'dynamic_time_zone' => 'required_if:create_dynamic,1|timezone',
            'dynamic_uuid' => 'required_if:create_dynamic,0',
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
