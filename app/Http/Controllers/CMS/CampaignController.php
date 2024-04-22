<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function storeCampaign (Request $request){
        $vaildator = Validator::make($request->all() , [
            'name' => 'required',
            'color' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'content_goal' => 'required',
            'summary' => 'required',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $campaign = new Campaign();
            $campaign->name = $request->input('name');
            $campaign->color = $request->input('color');
            $campaign->start_date = Carbon::parse($request->input('start_date'));
            $campaign->end_date = Carbon::parse($request->input('end_date'));
            $campaign->content_goal = $request->input('content_goal');
            $campaign->summary = $request->input('summary');
            $campaign->name = $request->input('name');
            $campaign->save();
            return response()->json([
                'status' => 200,
                'message' => 'تم اضافة الحملة بنجاح',
                'campaign' => $campaign,
            ]);
        }
    }
     // this is fetch all campaigns
    public function fetchCampaign(){
        $campaign = Campaign::all();
        if(!$campaign->isEmpty()){
            return response()->json([
                'status' =>200,
                'campaigns' => $campaign,
            ]);
        }
        else{
            return response()->json([
                'status' => 404,
                'error' =>'لا يوجد حملات اعلانية مفعلة حتي الان'
            ]);
        }

    }
}
