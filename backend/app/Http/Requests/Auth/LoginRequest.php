<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amway_code' => ['required', 'string', 'max:20'],
            'is_account_holder' => ['required', 'boolean'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amway_code.required' => 'Amway code is required',
            'amway_code.string' => 'Amway code must be text',
            'amway_code.max' => 'Amway code cannot exceed 20 characters',
            'is_account_holder.required' => 'Must specify if account holder or co-holder',
            'is_account_holder.boolean' => 'Account holder field must be true or false',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
        ];
    }
}
