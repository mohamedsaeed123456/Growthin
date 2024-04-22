<?php

namespace App\Http\Requests;

use App\Models\Account;
use App\Rules\UniqueEmailForUserId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use League\CommonMark\Normalizer\UniqueSlugNormalizer;

class RegisterRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'email' => ['required','email','min:11','max:320',
                new UniqueEmailForUserId,
            ],
            'password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/|confirmed|min:6',
            'password_confirmation' => 'required|min:6',
        ];
    }
}
