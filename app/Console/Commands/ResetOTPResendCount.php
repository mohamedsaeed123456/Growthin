<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ResetOTPResendCount extends Command
{
    protected $signature = 'otp:reset';
    protected $description = 'Reset OTP resend count for all users';
    public function handle(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->update(['otp_resend_count' => 0]);
            $this->info('OTP resend count reset successfully for the authenticated user.');
        } else {
            $this->error('No authenticated user found.');
        }
    }
}
