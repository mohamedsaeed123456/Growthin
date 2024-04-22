<?php

namespace App\Http\Controllers\Billing_Subscription;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Payment;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class QuestionController extends Controller
{
    public function onBoardingContinue (Request $request)
    {
        $vaildator = Validator::make($request->all() , [
            'location' => 'required',
            'period' => 'required',
            'description' => 'required',
            'url' => '',
            'social_media_link' => '',
            'positives_question' => 'required',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $user = $request->user();
            $question = Question::where('user_id', $user->id)->first();
            if ($question) {
                return response()->json([
                'status' => 404,
                'message' => 'تمت الاجابة علي هذه الاسئلة',
            ]);
            }
            $questions = Question::create([
                'location' => $request->location,
                'period' => $request->period,
                'description' => $request->description,
                'url' => $request->url?$request->url:null,
                'social_media_link' => $request->social_media_link?$request->social_media_link:null,
                'positives_question' => $request->positives_question,
                'user_id' =>$user->id,
            ]);
            return response()->json([
                'status' => 200,
                'Questions' => $questions,
                'message' => 'اكمل الاجابة علي باقي الاسئلة في الصفحة التالية',
            ]);
        }
    }
    public function onBoardingStore(Request $request){
        $vaildator = Validator::make($request->all() , [
            'goals_question' => 'required',
            'competitors_question' => 'required',
            'advertising_question' => 'required',
            'profile_image' => '',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $user = $request->user();
            $question = Question::where('user_id', $user->id)->first();
            if (!$question) {
                $question = new Question();
            }
            $question->goals_question = $request->goals_question;
            $question->competitors_question = $request->competitors_question;
            $question->advertising_question = $request->advertising_question;
            if ($request->hasFile('profile_image')) {
                $imageName = time().'.'.$request->profile_image->extension();
                $request->profile_image->move(public_path('images'), $imageName);
                $question->profile_image = $imageName;
            }
            $question->user_id = $user->id;
            $question->save();
            $user->isAnsweredQuestions = true;
            $user->save();
            return response()->json([
                'status' => 200,
                'message' => 'تمت الاجابة علي جميع الاسئلة',
                'Questions' => $question,
                'isAnsweredQuestions' => true,
            ]);
        }
    }
}

