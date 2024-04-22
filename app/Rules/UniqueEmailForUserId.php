<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UniqueEmailForUserId implements Rule
{
    public function passes($attribute, $value)
    {
        $exists = DB::table('accounts')
                    ->join('users', 'accounts.user_id', '=', 'users.id')
                    ->where('users.email', $value)
                    ->exists();

        return !$exists;
    }

    public function message()
    {
        return 'هذا الايميل مرتبط بحساب موجود (تسجيل الدخول بدلا من ذلك)';
    }
}
