<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class TokenRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'user_name' => 'required',
            'password' => 'required',
            'device_name' => 'required',
            'version' => 'required|in:2', // prevents old version on front end form access until updateing
        ];

        $user = User::where('user_name', request()->user_name)->first();
        if (! $user || ! Hash::check(request()->password, $user->password)) {
            $rules['valid_user'] = 'required|between:500,500';
        }

        return $rules;
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'valid_user' => 'Invalid credentials.',
            'version' => 'App version out of date.',
        ];
    }
}
