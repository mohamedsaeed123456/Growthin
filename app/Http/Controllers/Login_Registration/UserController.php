<?php

namespace App\Http\Controllers\Login_Registration;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Models\Account;
use App\Models\Country;
use App\Notifications\RegisterNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Ichtrojan\Otp\Models\Otp as ModelsOtp;
use App\Http\Controllers\Controller;
use App\Models\Question;

class UserController extends Controller
{
    public function register(RegisterRequest $request){
        $newUser  = $request->validated();
        $checkUser= User::where('email', $newUser['email'])->first();
        if(!$checkUser){
            $user = User::create([
                'email' => $newUser['email'],
                'password' => bcrypt($newUser['password']),
                'password_confirmation' =>bcrypt($newUser['password_confirmation']),
                'role' =>'client',
                'is_suspended' => false,
                'is_deleted' => false,
                'isAnsweredQuestions' => false,
        ]);
            $latestOTP = ModelsOtp::where('identifier', $newUser['email'])->latest()->first();
            $success['token'] = $user->createToken('user',['app:all'])->plainTextToken;
            $success['email'] = $user->email;
            $success['role'] = $user->role;
            $success['success'] = true;
            $user->notify(new RegisterNotification());
            if ($latestOTP) {
                if ($latestOTP->validity) {
                    $success['expired_time'] = $latestOTP->validity;
                } else {
                    $success['expired_time'] = 5;
                }
            } else {
                $success['expired_time'] = 5;
            }
            return response()->json(['user' => $success,'status' =>200]);
        }
        else{
            $latestOTP = ModelsOtp::where('identifier', $newUser['email'])->latest()->first();
            $success['token'] = $checkUser->createToken('user',['app:all'])->plainTextToken;
            $success['email'] = $checkUser->email;
            $success['role'] = $checkUser->role;
            $success['success'] = true;
            $checkUser->notify(new RegisterNotification());
            if ($latestOTP) {
                if ($latestOTP->validity) {
                    $success['expired_time'] = $latestOTP->validity;
                } else {
                    $success['expired_time'] = 5;
                }
            } else {
                $success['expired_time'] = 5;
            }
            return response()->json(['user' => $success,'status' =>200]);
        }

    }

    public function accountRegister(AccountRequest $request){
        $newAccount  = $request->validated();
        $existingCountry = Country::where('code', $newAccount['country_code'])->first();
        if(!$existingCountry){
            $country = Country::firstOrCreate(['code' => $newAccount['country_code'] , 'name' => $newAccount['name']]);
        }
        else{
            $country = $existingCountry;
        }
        $account = Account::create([
            'company_name' => $newAccount['company_name'],
            'company_sector' => $newAccount['company_sector'],
            'user_name' => $newAccount['user_name'],
            'phone' => $newAccount['phone'],
            'gender' => $newAccount['gender'],
            'date' => $newAccount['date'],
            'user_id' => $newAccount['user_id'],
            'country_code' => $newAccount['country_code'],
        ]);
        $country->accounts()->save($account);
        return response()->json(['status' =>200 , 'account' => $account]);
    }
    public function login(Request $request){
        $validator =Validator::make($request->all() ,[
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if($validator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $validator->messages(),
            ]);
        }
        $email = $request->input('email');
        $password = $request->input('password');
        $account = Account::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();
        if(!$account){
            return response()->json([
                'error' => 'البريد الالكتروني او كلمة المرور خطأ',
                'status' => 404,
            ]);
        }
        $user = $account->user;
        if($user->is_deleted && $user->is_suspended ){
            $user->delete();
            return response()->json([
                'error' => 'هذا الحساب تم حذفه',
                'status' => 404,
            ]);
        }
        if($user->is_suspended && !$user->is_deleted){
            return response()->json([
                'error' => 'هذا الحساب معلق',
                'status' => 404,
            ]);
        }
        if($user->is_deleted && !$user->is_suspended ){
            $user->delete();
            return response()->json([
                'error' => 'هذا الحساب لم يعد موجود بعد',
                'status' => 404,
            ]);
        }
        if(!$user || !Hash::check($password, $user->password)){
            return response()->json([
                'error' => 'البريد الالكتروني او كلمة المرور خطأ',
                'status' => 404,
            ]);
        }
        else{
            $token = $account->user->createToken('authToken')->plainTextToken;
            return response()->json([
                'account' => $account,
                'token' => $token,
                'status' => 200,
                'isAnsweredQuestions' => $user->isAnsweredQuestions,
            ]);
        }
    }


    // This is to fetch Users to add Content in  CMS module
    public function fetchUser(Request $request){
        $user = $request->user();
        $Companies = Account::whereHas('user', function ($query) use ($user) {
            $query->where('operation_id', $user->id)->orWhere('manager_id', $user->id);
        })->get();
        if(!$Companies->isEmpty()){
            $allUsers = [];
            $allQuestions = [];
            foreach ($Companies as $company) {
                $users = $company->user_id;
                $allUsers[] = $users;
                foreach ($allUsers as $userId) {
                    $questions = Question::where('user_id', $userId)->get();
                }
                $allQuestions = array_merge($allQuestions, $questions->toArray());
            }
            if(empty($allUsers)){
                return response()->json([
                    'status' => 404,
                    'error' => 'لم يتم تعيين حسابات لك حتي الان',
                ]);
            }
            if (!empty($allQuestions)) {
                $userProfileImages = [];

                foreach ($allQuestions as $question) {
                    $userId = $question['user_id'];
                    $profileImage = $question['profile_image'];

                    $userProfileImages[$userId] = $profileImage;
                }

                if (count($userProfileImages) !== count($Companies)) {
                    return response()->json([
                        'status' => 404,
                        'error' => 'Some users have missing profile images.',
                    ]);
                }

                $accountsWithProfileImages = [];
                foreach ($Companies as $company) {
                    $userId = $company['user_id'];
                    $companyData = $company->toArray();
                    $companyData['profile_image'] = $userProfileImages[$userId];
                    $accountsWithProfileImages[] = $companyData;
                }
                return response()->json([
                    'status' => 200,
                    'accounts_with_profile_images' => $accountsWithProfileImages,
                ]);
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'لم تجب عن الاسئلة بعد',
                ]);
            }
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'This is normal client',
            ]);
        }
    }
}

