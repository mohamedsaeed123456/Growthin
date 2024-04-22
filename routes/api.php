<?php

use App\Http\Controllers\Login_Registration\EmailVerificationController;
use App\Http\Controllers\Billing_Subscription\PaymentController;
use App\Http\Controllers\Billing_Subscription\QuestionController;
use App\Http\Controllers\CMS\CampaignController;
use App\Http\Controllers\CMS\PostController;
use App\Http\Controllers\Login_Registration\ResetPasswordController;
use App\Http\Controllers\Login_Registration\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register' , [UserController::class , 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('email-verification' , [EmailVerificationController::class , 'email_verification']);
    Route::get('email-verification' , [EmailVerificationController::class , 'sendEmailVerification']);
    Route::post('accounts' , [UserController::class , 'accountRegister']);
    Route::post('forget_email_verification' , [EmailVerificationController::class , 'forgetEmailVerification']);
    Route::get('forget_email_verification' , [EmailVerificationController::class , 'forgetSendEmailVerification']);
    Route::post('reset_password' , [ResetPasswordController::class , 'resetPassword']);
    Route::post('payments/{id}' , [PaymentController::class , 'pay']);
    Route::middleware('api')->group(function () {
        Route::post('questions_continue', [QuestionController::class, 'onBoardingContinue']);
        Route::post('questions_store' , [QuestionController::class , 'onBoardingStore']);
    });
    Route::post('add_content' , [PostController::class , 'store_content']);
    Route::get('users' , [UserController::class , 'fetchUser']);
    Route::get('posts' , [PostController::class , 'fetchPost']);
    Route::post('campaigns' , [CampaignController::class , 'storeCampaign']);
    Route::get('campaigns' , [CampaignController::class , 'fetchCampaign']);
});

Route::post('login' , [UserController::class , 'login']);
Route::post('forget_email' , [EmailVerificationController::class , 'forgetEmail']);

Route::get('content_goals' , [PostController::class , 'fetchContentGoal']);


