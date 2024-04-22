<?php

namespace App\Http\Controllers\Login_Registration;
use App\Http\Controllers\Controller;

use App\Http\Requests\EmailVerificationRequest;
use App\Models\Account;
use App\Models\User;
use App\Notifications\RegisterNotification;
use Ichtrojan\Otp\Models\Otp as ModelsOtp;
use Illuminate\Http\Request;
use Ichtrojan\Otp\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    private $otp;
    public function __construct(){
        $this->otp = new Otp;
    }
    public function sendEmailVerification(Request $request){
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized','status' => 404]);
        }
        if ($user) {
            if ($user->otp_resend_count >= 5) {
                $now = Carbon::now();
                $lastResend = Carbon::parse($user->updated_at);
                $minutesSinceLastResend = $now->diffInMinutes($lastResend);
                if ($minutesSinceLastResend < 15) {
                    $remainingMinutes = 15 - $minutesSinceLastResend;
                    return response()->json(['error' => 'لقد وصلت للعدد الاقصي للمحاولات من الممكن المحاولة من جديد بعد 15 دقيقة','status' => 404]);
                }
                $latestOTP = ModelsOtp::where('identifier', $user->email)->latest()->first();
                if ($latestOTP) {
                    $user->otp_resend_count = 0;
                    $user->save();
                    $latestOTP->validity = 5;
                    $latestOTP->save();
                    $request->user()->notify(new RegisterNotification());
                    $success['success'] = true;
                    $success['expired_time'] = $latestOTP->validity;
                    return response()->json(['success' => $success ,'status' => 200]);
                }
            }
            $user->otp_resend_count++;
            $user->save();
            $latestOTP = ModelsOtp::where('identifier', $user->email)->latest()->first();
            if ($latestOTP) {
                $latestOTP->validity = $latestOTP->validity * 2;
                $latestOTP->save();
                $request->user()->notify(new RegisterNotification());
                $success['success'] = true;
                $success['expired_time'] = $latestOTP->validity;
                return response()->json(['success' => $success ,'status' =>200]);
            }
        }
    }
    public function email_verification(EmailVerificationRequest $request){
        $otp2 = $this->otp->validate($request->email , $request->otp);
        if(!$otp2->status){
            return response()->json(['error' => $otp2,'status' =>404]);
        }
        $user = User::where('email', $request->email)->first();
        $user->update(['email_verified_at' => now()]);
        $success['success'] = true;
        return response()->json(['success' => $success ,'status' =>200]);
    }

    // Forget Password
    public function forgetEmail(Request $request){
        $validator =Validator::make($request->all() ,[
            'email' => 'required|email',
        ]);
        if($validator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $validator->messages(),
            ]);
        }
        $email = $request->input('email');
        $account = Account::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();
        if($account){
            $token = $account->user->createToken('authToken')->plainTextToken;
            $latestOTP = ModelsOtp::where('identifier', $email)->latest()->first();
            if ($latestOTP) {
                $latestOTP->validity = 5;
                $latestOTP->save();
                $account->user->notify(new RegisterNotification());
                $success['success'] = true;
                $success['token'] = $token;
                return response()->json(['success' => $success ,'status' =>200]);
            }

        }
        else{
            return response()->json([
                'error' => 'هذا البريد الاكتروني غير مسجل لدينا',
                'status' => 404,
            ]);
        }
    }
    public function forgetEmailVerification(EmailVerificationRequest $request){
        $otp2 = $this->otp->validate($request->email , $request->otp);
        if(!$otp2->status){
            return response()->json(['error' => $otp2,'status' => 404]);
        }
        $user = User::where('email', $request->email)->first();
        $user->update(['email_verified_at' => now()]);
        $success['success'] = true;
        return response()->json(['success' => $success ,'status' =>200]);
    }
    public function forgetSendEmailVerification(Request $request){
        $user = $request->user();
        if ($user) {
            if ($user->otp_resend_count >= 5) {
                $now = Carbon::now();
                $lastResend = Carbon::parse($user->updated_at);
                $minutesSinceLastResend = $now->diffInMinutes($lastResend);
                if ($minutesSinceLastResend < 15) {
                    $remainingMinutes = 15 - $minutesSinceLastResend;
                    return response()->json(['error' => 'لقد وصلت للعدد الاقصي للمحاولات من الممكن المحاولة من جديد بعد 15 دقيقة','status' =>404]);
                }
                $latestOTP = ModelsOtp::where('identifier', $user->email)->latest()->first();
                if ($latestOTP) {
                    $user->otp_resend_count = 0;
                    $user->save();
                    $latestOTP->validity = 5;
                    $latestOTP->save();
                    $request->user()->notify(new RegisterNotification());
                    $success['success'] = true;
                    $success['expired_time'] = $latestOTP->validity;
                    return response()->json(['success' => $success ,'status' =>200]);
                }
            }
            $user->otp_resend_count++;
            $user->save();
            $latestOTP = ModelsOtp::where('identifier', $user->email)->latest()->first();
            if ($latestOTP) {
                $latestOTP->validity = $latestOTP->validity * 2;
                $latestOTP->save();
                $request->user()->notify(new RegisterNotification());
                $success['success'] = true;
                $success['expired_time'] = $latestOTP->validity;
                return response()->json(['success' => $success ,'status' =>200]);
            }
        }
    }
}
