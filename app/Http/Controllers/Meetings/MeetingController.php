<?php

namespace App\Http\Controllers\Meetings;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Meeting;
use App\Models\MeetingSlots;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    public function storeMeeting(Request $request){
        $vaildator = Validator::make($request->all() , [
            'meeting_type' => 'required',
            'meeting_title' => 'required',
            'meeting_date' => 'required',
            'meeting_plan' => 'required',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $user = $request->user();
            $meeting = new Meeting();
            $meeting->meeting_type = $request->input('meeting_type');
            $meeting->meeting_title = $request->input('meeting_title');
            $meeting_date = $request->meeting_date;
            list($date, $timeRange) = explode(' from ', $meeting_date);
            list($startTime, $endTime) = explode(' to ', $timeRange);
            $startTime = date('Y-m-d H:i', strtotime("$date $startTime"));
            $endTime = date('Y-m-d H:i', strtotime("$date $endTime"));
            $meeting->meeting_start_date = $startTime;
            $meeting->meeting_end_date = $endTime;
            $meeting->meeting_plan = $request->input('meeting_plan');
            if ($request->hasFile('meeting_image')) {
                $uploadedFile = $request->file('meeting_image');
                $imageName = time().'.'.$uploadedFile->getClientOriginalExtension();
                $image = Image::make($uploadedFile)->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->encode('jpg', 80);
                $image->save(public_path('images/' . $imageName));
                $meeting->meeting_image = $imageName;
            }
            $meeting->creator_id = $user->id;
            $meeting->user_id = $request->input('user_id');
            $meeting->save();
            return response()->json([
                'status' =>200 ,
                'meetings' => $meeting
            ]);
        }
    }
    public function fetchMeeting(Request $request){
        $user = $request->user();
        if($user->role === 'client'){
            $currentDateTime = Carbon::now();


            //////   latestUpcomingMeetingForClient
            $latestUpcomingMeetingForClient = Meeting::where(function ($query) use ($user) {
                $query->where('creator_id', $user->id)
                        ->orWhere('creator_id', $user->account->manager_id);
            })
            ->whereNotNull('meeting_start_date')
            ->whereNotNull('meeting_end_date')
            ->where(function ($query) use ($currentDateTime) {
                $query->where('meeting_start_date', '>', $currentDateTime->toDateTimeString());
            })
            ->orderBy('meeting_start_date', 'asc')
            ->first();

            //////   AllMeetingRequestsClient

            $AllMeetingRequestsClient = Meeting::where(function ($query) use ($user) {
                $query->where('creator_id', $user->account->manager_id);
            })
            ->orderBy('meeting_start_date', 'asc')
            ->get();



            //////  AllUpcomingMeetingForClient
            $AllUpcomingMeetingForClient = Meeting::where(function ($query) use ($user) {
                $query->where('creator_id', $user->id)
                        ->orWhere('creator_id', $user->account->manager_id);
            })
            ->whereNotNull('meeting_start_date')
            ->whereNotNull('meeting_end_date')
            ->where(function ($query) use ($currentDateTime) {
                $query->where('meeting_start_date', '>', $currentDateTime->toDateTimeString());
            })
            ->orderBy('meeting_start_date', 'asc')
            ->get();


            //////  historyMeetingForClient

            $historyMeetingForClient = Meeting::where(function ($query) use ($user) {
                $query->where('creator_id', $user->id)
                        ->orWhere('creator_id', $user->account->manager_id);
            })
            ->whereNotNull('meeting_start_date')
            ->whereNotNull('meeting_end_date')
            ->where('meeting_end_date', '<', $currentDateTime->toDateTimeString()) // Compare meeting end date with the current date and time
            ->orderBy('meeting_end_date', 'asc')
            ->get();

            if (($latestUpcomingMeetingForClient ?? null) === null && $AllMeetingRequestsClient->isEmpty() && $AllUpcomingMeetingForClient->isEmpty() && $historyMeetingForClient->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'error' => 'لا توجد اجتماعات بعد',
                ]);
            }
            else{
                return response()->json([
                    'status' =>200,
                    'latestUpcomingMeeting' => $latestUpcomingMeetingForClient,
                    'AllMeetingRequests' => $AllMeetingRequestsClient,
                    'AllUpcomingMeeting' => $AllUpcomingMeetingForClient,
                    'historyMeeting' => $historyMeetingForClient,
                ]);
            }
        }
        else if($user->role === 'account_manager'){
            $currentDateTime = Carbon::now();
            $ManagerCompanies = Account::where(function ($query) use ($user) {
                $query->WhereHas('user', function ($query) use ($user) {
                    $query->where('manager_id' ,$user->id);
                });
            })->get();

            ///  latestUpcomingMeetings
            $latestUpcomingMeetings = Meeting::where(function ($query) use ($user, $ManagerCompanies) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('user', function ($query) use ($ManagerCompanies) {
                        $query->orWhereIn('creator_id', $ManagerCompanies->pluck('user_id')->toArray());
                    });
            })
            ->whereNotNull('meeting_start_date')
            ->whereNotNull('meeting_end_date')
            ->where('meeting_start_date', '>', $currentDateTime->toDateTimeString())
            ->orderBy('meeting_start_date', 'asc')
            ->first();
            if($latestUpcomingMeetings != null){
                $id = $latestUpcomingMeetings->user_id?$latestUpcomingMeetings->user_id:$latestUpcomingMeetings->creator_id;
                $Companies = Account::where('user_id' ,$id)->first();
                $userId = $Companies['user_id'];
                $userName = $Companies['user_name'];
                $UserId = $Companies['user_id']?$Companies['user_id']:$Companies['creator_id'];
                $companyName = $Companies['company_name'];
                $userNames[$userId] = $userName;
                $companyNames[$userId] = $companyName;
                $UserIds[$userId] = $UserId;
                $meetingData = $latestUpcomingMeetings;
                $meetingData['user_name'] = $userNames[$id];
                $meetingData['company_name'] = $companyNames[$userId];
                $meetingData['UserId'] = $UserIds[$userId];
                $latestUpcomingMeetingsWithData= $meetingData;
            }
            else{
                $latestUpcomingMeetingsWithData = null;
            }
            ///  latestpastMeetings

            $latestpastMeetings = Meeting::where(function ($query) use ($user, $ManagerCompanies) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('user', function ($query) use ($ManagerCompanies) {
                        $query->orWhereIn('creator_id', $ManagerCompanies->pluck('user_id')->toArray());
                    });
            })
            ->whereNotNull('meeting_start_date')
            ->whereNotNull('meeting_end_date')
            ->where('meeting_end_date', '<', $currentDateTime->toDateTimeString())
            ->orderBy('meeting_end_date', 'desc')
            ->first();
            if($latestpastMeetings != null){
                $id = $latestpastMeetings->user_id?$latestpastMeetings->user_id:$latestpastMeetings->creator_id;
                $Companies = Account::where('user_id' ,$id)->first();
                $userId = $Companies['user_id'];
                $userName = $Companies['user_name'];
                $UserId = $Companies['user_id']?$Companies['user_id']:$Companies['creator_id'];
                $companyName = $Companies['company_name'];
                $userNames[$userId] = $userName;
                $companyNames[$userId] = $companyName;
                $UserIds[$userId] = $UserId;
                $meetingData = $latestpastMeetings;
                $meetingData['user_name'] = $userNames[$id];
                $meetingData['company_name'] = $companyNames[$userId];
                $meetingData['UserId'] = $UserIds[$userId];
                $latestpastMeetingsWithData= $meetingData;
            }
            else{
                $latestpastMeetingsWithData = null;
            }

            // All UpComingMeeting

            $AllUpcomingMeeting =  Meeting::where(function ($query) use ($user, $ManagerCompanies) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('user', function ($query) use ($ManagerCompanies) {
                        $query->orWhereIn('creator_id', $ManagerCompanies->pluck('user_id')->toArray());
                });
            })
            ->where('meeting_start_date', '>', $currentDateTime->toDateTimeString())
            ->orderBy('meeting_start_date', 'asc')
            ->get();
            if(!$AllUpcomingMeeting->isEmpty()){
                $meetingIds = [];
                $allAccounts = [];
                foreach($AllUpcomingMeeting as $meeting){
                    $meetingIds[] = $meeting->user_id?$meeting->user_id:$meeting->creator_id;;
                }
                foreach ($meetingIds as $id) {
                    $Companies = Account::where('user_id' ,$id)->get();
                    $allAccounts = array_merge($allAccounts, $Companies->toArray());
                }
                $UserIds = [];
                $companyNames = [];
                $userNames = [];
                foreach ($allAccounts as $account) {
                    $userId = $account['user_id'];
                    $userName = $account['user_name'];
                    $UserId = $account['user_id']?$account['user_id']:$account['creator_id'];
                    $companyName = $account['company_name'];
                    $userNames[$userId] = $userName;
                    $companyNames[$userId] = $companyName;
                    $UserIds[$userId] = $UserId;
                }
                $AllUpcomingMeetingWithData = [];
                foreach ($AllUpcomingMeeting as $meeting) {
                    $userId = $meeting->user_id?$meeting->user_id:$meeting->creator_id;
                    $meetingData = $meeting->toArray();
                    $meetingData['user_name'] = $userNames[$userId];
                    $meetingData['company_name'] = $companyNames[$userId];
                    $meetingData['UserId'] = $UserIds[$userId];
                    $AllUpcomingMeetingWithData[] = $meetingData;
                }
            }
            else{
                $AllUpcomingMeetingWithData = null;
            }

            //historyMeetings
            $historyMeetings = Meeting::where(function ($query) use ($user, $ManagerCompanies) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('user', function ($query) use ($ManagerCompanies) {
                        $query->orWhereIn('creator_id', $ManagerCompanies->pluck('user_id')->toArray());
                });
            })
            ->where('meeting_end_date', '<', $currentDateTime->toDateTimeString())
            ->orderBy('meeting_end_date', 'asc')
            ->get();
            if(!$historyMeetings->isEmpty()){
                $meetingIds = [];
                $allAccounts = [];
                foreach($historyMeetings as $meeting){
                    $meetingIds[] = $meeting->user_id?$meeting->user_id:$meeting->creator_id;;
                }
                foreach ($meetingIds as $id) {
                    $Companies = Account::where('user_id' ,$id)->get();
                    $allAccounts = array_merge($allAccounts, $Companies->toArray());
                }
                $UserIds = [];
                $companyNames = [];
                $userNames = [];
                foreach ($allAccounts as $account) {
                    $userId = $account['user_id'];
                    $userName = $account['user_name'];
                    $UserId = $account['user_id']?$account['user_id']:$account['creator_id'];
                    $companyName = $account['company_name'];
                    $userNames[$userId] = $userName;
                    $companyNames[$userId] = $companyName;
                    $UserIds[$userId] = $UserId;
                }
                $historyMeetingsWithData = [];
                foreach ($historyMeetings as $meeting) {
                    $userId = $meeting->user_id?$meeting->user_id:$meeting->creator_id;
                    $meetingData = $meeting->toArray();
                    $meetingData['user_name'] = $userNames[$userId];
                    $meetingData['company_name'] = $companyNames[$userId];
                    $meetingData['UserId'] = $UserIds[$userId];
                    $historyMeetingsWithData[] = $meetingData;
                }
            }
            else{
                $historyMeetingsWithData = null;
            }
            if (($latestUpcomingMeetings ?? null) === null && ($latestpastMeetings ?? null) === null  && $AllUpcomingMeeting->isEmpty() && $historyMeetings->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'error' => 'لا توجد اجتماعات بعد',
                ]);
            }
            else{
                return response()->json([
                    'status' =>200,
                    'latestUpcomingMeetings' => $latestUpcomingMeetingsWithData,
                    'latestpastMeetings' => $latestpastMeetingsWithData,
                    'AllUpcomingMeeting' => $AllUpcomingMeetingWithData,
                    'historyMeetings' => $historyMeetingsWithData,
                ]);
            }
        }
    }
    public function storeMeetingSlots(Request $request){
        $validator = Validator::make($request->all() , [
            'days' => 'required',
            'slots' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $validator->messages(),
            ]);
        }
        else{
            $user = $request->user();
            $meeting_slots = new MeetingSlots();
            $days = preg_replace('/\s\(.*\)/', '', $request->input('days'));
            $meetingSlotsArray=[];
            foreach ($days as $day) {
                $parseDay = Carbon::parse($day);
                $formattedDay = $parseDay->format('Y/m/d');
                $meetingSlotsArray[] = str_replace('\/','/',json_encode($formattedDay));
            }
            $meeting_slots->days = $meetingSlotsArray;
            $meetingSlots = preg_replace('/\s\(.*\)/', '', $request->input('slots'));
            $newSlots = [];
            foreach ($meetingSlots as $slot) {
                $slotParts = explode(' to ', $slot);
                $startTime = Carbon::parse(explode(' ', $slotParts[0])[1]);
                $endTime = Carbon::parse($slotParts[1]);
                $duration = $startTime->diffInMinutes($endTime);
                if ($duration > 60) {
                    $currentTime = $startTime;
                    while ($currentTime->lt($endTime)) {
                        $currentEndTime = $currentTime->copy()->addMinutes(60);
                        $currentEndTime = min($currentEndTime, $endTime);
                        $newSlot = 'From ' . $currentTime->format('H:i') . ' to ' . $currentEndTime->format('H:i');
                        $newSlots[] = $newSlot;
                        $currentTime->addMinutes(90); // Add 90 minutes (1 hour + 30 minutes)
                    }
                } else {
                    // If the duration is less than or equal to 60 minutes, keep the original slot
                    $newSlots[] = $slot;
                }
            }
            $modifiedSlotsJson = str_replace('\/','/',json_encode($newSlots));
            $meeting_slots->slots = $modifiedSlotsJson;
            $meeting_slots->creator_id = $user->id;
            $meeting_slots->save();
            return response()->json([
                'status' =>200,
                'meeting_slots' => $meeting_slots
            ]);
        }
    }

    public function fetchMeetingSlots(Request $request){
        $user = $request->user();
        if($user->role === 'account_manager'){
            $meetingSlots = MeetingSlots::all();
            if($meetingSlots != null){
                foreach($meetingSlots as $slot) {
                    $daysArray= is_string($slot->days) ? json_decode($slot->days, true) : $slot->days;
                    foreach ($daysArray as $day_str) {
                        $date_obj = Carbon::createFromFormat('Y/m/d', trim($day_str, '"'))->startOfDay();
                        $formattedDay = $date_obj->format('Y-m-d');
                        $slotsArray = is_string($slot->slots) ? json_decode($slot->slots, true) : $slot->slots;
                        $slotsInfo = [];
                        $isBooked = false;
                        foreach ($slotsArray as $slot_str) {
                            preg_match('/From (\d{2}:\d{2}) to (\d{2}:\d{2})/', $slot_str, $matches);
                            $start_time = $matches[1];
                            $end_time = $matches[2];
                                $isBooked = Meeting::where(function ($query) use ($formattedDay, $start_time, $end_time) {
                                    $query->where('meeting_start_date', '>=', $formattedDay . ' ' . $start_time)
                                        ->where('meeting_end_date', '<=', $formattedDay . ' ' . $end_time);
                                })->orWhere(function ($query) use ($formattedDay, $start_time, $end_time) {
                                    $query->where('meeting_start_date', '<=', $formattedDay . ' ' . $start_time)
                                        ->where('meeting_end_date', '>=', $formattedDay . ' ' . $end_time);
                                })->exists();
                                $slotsInfo[] = [
                                    'slot' => $slot_str,
                                    'isBooked' => $isBooked,
                                ];
                        }
                        $formattedDays = $date_obj->format('Y-m-d 00:00:00 \G\M\TO (e)');
                        $formattedDaySlots[] = [
                            'day' => $formattedDays,
                            'slots' => $slotsInfo,
                        ];
                    }
                }
                return response()->json([
                    'status' =>200,
                    'meeting_slots' => $formattedDaySlots,
                ]);
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'لم تضيف مواعيد بعد',
                ]);
            }
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'ليس لديك الصلاحية لهذا',
            ]);
        }
    }

    public function fetchMeetingSlotsClient(Request $request){
        $user = $request->user();
        if($user->role === 'client'){
            $day = preg_replace('/\s\(.*\)/', '', $request->input('day'));
            $cleanTimestamp = preg_replace('/ GMT.*$/', '', $day);
            $parseDay = Carbon::parse($cleanTimestamp);
            $formattedDay = $parseDay->format('Y/m/d');
            $slots = MeetingSlots::where('creator_id', $user->account->manager_id)->get();
            $meetingSlots = $slots->filter(function ($slot) use ($formattedDay) {
                $Days= is_string($slot->days) ? json_decode($slot->days, true) : $slot->days;
                $formattedDays = array_map(function ($day) {
                    return Carbon::createFromFormat('Y/m/d', trim($day, '"'))->format('Y/m/d');
                }, $Days);
                return in_array($formattedDay, $formattedDays);
            });
            $SlotsResult = [];
            foreach($meetingSlots as $slot){
                $SlotsResult[] = $slot->slots;
            }
            $availableSlots = [];
            foreach ($meetingSlots as $slot) {
                // Convert slots to array for easier manipulation
                $slotsArray = json_decode($slot->slots, true);
                // Check if any of the slots are booked
                $isBooked = false;
                foreach ($slotsArray as $slot_str) {
                    // Extract start and end times from the slot string
                    preg_match('/From (\d{2}:\d{2}) to (\d{2}:\d{2})/', $slot_str, $matches);
                    $start_time = $matches[1];
                    $end_time = $matches[2];
                    // Check if any meeting overlaps with the slot
                    $isBooked = Meeting::where('meeting_start_date', '>=', $formattedDay . ' ' . $start_time)
                        ->where('meeting_end_date', '<=', $formattedDay . ' ' . $end_time)
                        ->exists();
                    // If any meeting is booked for the specified slot, break the loop
                    if (!$isBooked) {
                        $availableSlots[] = $slot_str;
                    }
                }
            }
            if(!$meetingSlots->isEmpty()){
                return response()->json([
                    'status' =>200,
                    'slots' => $availableSlots
                ]);
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'لا يوجد مواعيد متاحة لهذا اليوم',
                ]);
            }
        }
    }

    public function updateMeetingLink(Request $request , $id){
        $user = $request->user();
        if($user->role === 'account_manager'){
            $meeting = Meeting::findOrFail($id);
            if($meeting !=null){
                $meeting->meeting_link = $request->input('meeting_link');
                $meeting->save();
                return response()->json([
                    'status' =>200,
                    'meeting_link' => $meeting->meeting_link,
                ]);
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'لا يوجد اجتماع لإنشاء رابط له',
                ]);
            }
        }
    }
    public function updateMeetingSummary(Request $request ,$id){
        $user = $request->user();
        if($user->role === 'account_manager'){
            $meeting = Meeting::findOrFail($id);
            if($meeting !=null){
                $meeting->meeting_summary = $request->input('meeting_summary');
                $meeting->save();
                return response()->json([
                    'status' =>200,
                    'meeting_summary' => $meeting->meeting_summary,
                ]);
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'لا يوجد اجتماع لإنشاء ملخص له',
                ]);
            }
        }
    }
    public function fetchMeetingPlan(Request $request,$id){
        $meeting = Meeting::findOrFail($id);
        if($meeting !=null){

            return response()->json([
                'status' =>200,
                'meeting_plan' => $meeting->meeting_plan,
                'meeting_image' => $meeting->meeting_image,
            ]);
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'لا يوجد اجتماع لإنشاء ملخص له',
            ]);
        }
    }
    public function updateMeetingDate(Request $request , $id){
        $meeting = Meeting::findOrFail($id);
        if($meeting !=null){
            $day = preg_replace('/\s\(.*\)/', '', $request->input('day'));
            $meetingSlot = preg_replace('/\s\(.*\)/', '', $request->input('slot'));
            if($day != "" || $meetingSlot != ""){
                $datePattern = '/\b\w{3} \d{2} \d{4}\b/';
                preg_match($datePattern, $day, $matches);
                $cleanDate = $matches[0] ?? null;
                $parseDay = Carbon::createFromFormat('M d Y', $cleanDate);
                $formattedDay = $parseDay->format('Y/m/d');
                $meetingDay = str_replace('\/','/',$formattedDay);
                $mergedDateTime = $meetingDay . ' ' . $meetingSlot;
                list($date, $timeRange) = explode(' From ', $mergedDateTime);
                list($startTime, $endTime) = explode(' to ', $timeRange);
                $startTime = date('Y-m-d H:i', strtotime("$date $startTime"));
                $endTime = date('Y-m-d H:i', strtotime("$date $endTime"));
                $meeting->meeting_start_date = $startTime;
                $meeting->meeting_end_date = $endTime;
                $meeting->save();
                return response()->json([
                    'status' =>200,
                    'meeting_start_date' => $meeting->meeting_start_date,
                    'meeting_end_date' => $meeting->meeting_end_date,
                ]);
            }
            else{
                $meeting->meeting_start_date = null;
                $meeting->meeting_end_date = null;
                $meeting->save();
                return response()->json([
                    'status' =>200,
                    'meeting_start_date' => $meeting->meeting_start_date,
                    'meeting_end_date' => $meeting->meeting_end_date,
                ]);
            }
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'لا يوجد اجتماع لتعديل موعد له',
            ]);
        }
    }
    public function destroyMeetingDate(Request $request , $id){
        $meeting = Meeting::findOrFail($id);
        if($meeting !=null){
            $meeting->reason_meeting_cancle = $request->input('reason_meeting_cancle');
            $meeting->save();
            $meeting->delete();
            return response()->json([
                'status' =>200,
                'success' => 'تم الغاء الاجتماع بنجاح',
            ]);
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'لا يوجد اجتماع  لالغاءه له',
            ]);
        }
    }
    public function fetchComapnyData(Request $request , $id){


    }

}
