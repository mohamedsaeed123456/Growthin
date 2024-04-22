<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
            'company_name' => 'required|min:2|max:100',
            'company_sector' => 'required',
            'user_name' => 'required|min:2|max:40',
            'phone' => 'required|unique:accounts,phone',
            'gender' => 'required',
            'date' => 'required',
            'country_code' => 'required',
            'name' => 'required',
            'user_id' => 'required',
        ];
    }
}
