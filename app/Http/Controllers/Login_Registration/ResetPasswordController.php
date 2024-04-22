<?php

namespace App\Http\Controllers\Login_Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request){
        $vaildator = Validator::make($request->all() , [
            'new_password' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/|confirmed|min:6',
            'new_password_confirmation' => 'required|min:6',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $newPassword = $request->input('new_password');
            $newPasswordConfirmation = $request->input('new_password_confirmation');
            $user = $request->user();
            $user->update([
                'password' => bcrypt($newPassword),
                'password_confirmation' => bcrypt($newPasswordConfirmation),
            ]);
            $user->save();
            return response()->json([
                'status' => 200,
                'message' => 'تم تغيير كلمة المرور بنجاح',
                'newUser' => $user,
            ]);
        }
    }
}
