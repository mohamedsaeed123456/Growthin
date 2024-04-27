<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ContentGoal;
use App\Models\Post;
use App\Models\PostVersion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function store_content(Request $request,$id){
        $vaildator = Validator::make($request->all() , [
            'channel' => 'required',
            'campaign' => '',
            'content_goal' => 'required',
            'content_type' => 'required',
            'post_content' => 'required',
            'content_image' => 'required',
            'publication_date' => 'required',
            'campaign_id' => '',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $post = new Post();
            $post->channel = json_encode($request->input('channel'));
            $post->campaign = $request->input('campaign');
            $post->content_goal = $request->input('content_goal');
            $post->content_type = json_encode($request->input('content_type'));
            $post->post_content = $request->input('post_content');
            $post->isPublished = false;
            if ($request->hasFile('content_image')) {
                $imageName = time().'.'.$request->content_image->extension();
                $request->content_image->move(public_path('images'), $imageName);
                $post->content_image = $imageName;
            }
            $date = Carbon::parse( $request->input('publication_date'));
            $formattedDate = $date->toDateTimeString();
            $post->publication_date = $formattedDate;
            $user = $request->user();
            $post->user_id = $id;
            $post->creator_id = $user->id;
            $post->campaign_id = $request->input('campaign_id')? $request->input('campaign_id'):null;
            $post->save();
            $version = new PostVersion([
                'post_id' => $post->id,
                'oldVersion_id' => $post->id,
                'submission_date_time' => $formattedDate,
                'selected' => true,
            ]);
            $version->save();
                return response()->json([
                    'status' => 200,
                    'message' => 'تم اضافة المحتوي بنجاح',
                    'post' => $post,
                ]);
            }
    }

     // This is to fetch Posts for each user in CMS module
    public function fetchPost(Request $request){
            $user = $request->user();
            if($user->role === 'client'){
                $posts = Post::where('user_id' , $user->id)->get();
                if(!$posts->isEmpty()){
                    $allPostsPublished = true;
                    foreach ($posts as $post) {
                        if (!$post->isPublished) {
                            $allPostsPublished = false;
                            break;
                        }
                    }
                    if ($allPostsPublished) {
                        foreach ($posts as $post) {
                            $publicationDate = $post->publication_date;
                            $date = Carbon::parse($publicationDate);
                            $post->publication_date = $date->format('D M d Y H:i:s \G\M\TO');
                        }
                        return response()->json([
                            'status' => 200,
                            'posts' => $posts,
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 404,
                            'error' => 'لم يتم نشر المحتوي لهذا الحساب بعد',
                        ]);
                    }
                }
                else{
                    return response()->json([
                        'status' => 404,
                        'error' => 'لا يوجد محتوي خاص بك بعد',
                    ]);
                }
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'انت مدير محتوي او صانع المحتوي ليس عميل عادي',
                ]);
            }
    }
    // this is fetch content goals for add posts popup
    public function fetchContentGoal(){
        $contentGoal = ContentGoal::all();
        return response()->json([
            'content Goals' => $contentGoal
        ]);
    }
    public function fetchPostUser(Request $request , $id){
        $user = $request->user();
        $posts = Post::where('user_id' , $id)->where('creator_id' , $user->id)->get();
        if(!$posts->isEmpty()){
            foreach ($posts as $post) {
                $publicationDate = $post->publication_date;
                $date = Carbon::parse($publicationDate);
                $post->publication_date = $date->format('D M d Y H:i:s \G\M\TO');
            }
            return response()->json([
                'status' => 200,
                'posts' => $posts,
            ]);
        }
        else {
            return response()->json([
                'status' => 404,
                'error' => 'لا يوجد محتوي خاص بك بعد',
            ]);
        }
    }
    // this is published content to client from account manager only
    public function publishContent(Request $request){
        $user = $request->user();
        $ManagerCompanies = Account::where(function ($query) use ($user) {
            $query->WhereHas('user', function ($query) use ($user) {
                $query->where('manager_id' ,$user->id);
            });
        })->get();
        if(!$ManagerCompanies->isEmpty()){
            $operationId = $ManagerCompanies->pluck('operation_id')->unique()->toArray();
            $managerPosts = Post::where('creator_id', $user->id)->get();
            $operationPosts = Post::where('creator_id', $operationId)->get();
            $posts = [];
            if ($managerPosts !== null) {
                $posts = $managerPosts;
            }
            if ($operationPosts !== null) {
                $posts = $posts->merge($operationPosts);
            }
            if($posts->isEmpty()){
                return response()->json([
                    'status' => 404,
                    'error' => 'لم تضيف محتوي او صانع المحتوي لم يضيف شيء',
                ]);
            }
            foreach ($posts as $post) {
                $allApproved = true;
                if ($post->operation_status !== 'موافق عليه') {
                    $allApproved = false;
                    break;
                }
            }
                if ($allApproved) {
                    foreach ($posts as $post) {
                        $post->isPublished = true;
                        $post->save();
                    }
                    return response()->json([
                        'status' => 404,
                        'message' => 'تم نشر المحتوي للعميل بنجاح',
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'يجب ان يتم الموافقة علي كل المحتوي قبل النشر',
                    ]);
                }
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'تمهل حتي يتم تعيينك  كمدير للمحتوي' ,
            ]);
        }
    }
    // this is approved content from account manager and client also
    public function approveContent(Request $request ,$postId){
        $user = $request->user();
        if($user->role === 'client'){
            $PostClientStatus = Post::where('user_id', $user->id)->where('id', $postId)->first();
            if($PostClientStatus){
                $PostClientStatus->update([
                    'client_status' => 'مقبول',
                    'operation_status' => 'مقبول',
                    'account_manager_status' => 'مقبول',
                    'isApproved' => true,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'تم قبول المحتوي من العميل بنجاح',
                ]);
            }
        }
            else if($user->role === 'account_manager'){
                $account = $user->account;
                $ManagerCompanies = Account::where(function ($query) use ($user) {
                    $query->WhereHas('user', function ($query) use ($user) {
                        $query->where('manager_id' ,$user->id);
                    });
                })->get();
                if(!$ManagerCompanies->isEmpty()){
                    $clients = $ManagerCompanies->pluck('user_id');
                    $PostClientStatuses = Post::whereIn('user_id', $clients)
                    ->where('id', $postId)
                    ->get();
                    if(!$PostClientStatuses->isEmpty()){
                        foreach ($PostClientStatuses as $status) {
                            $status->update([
                                'client_status' => 'جديد',
                                'operation_status' => 'موافق عليه',
                                'account_manager_status' => 'مسودة',
                            ]);
                        }
                        return response()->json([
                            'status' => 200,
                            'message' => 'تمت الموافقة علي هذا المحتوي',
                        ]);
                    }
                    else{
                        return response()->json([
                            'status' => 404,
                            'error' => 'هذا المحتوي غير موجود' ,
                        ]);
                    }
                }
                else{
                    return response()->json([
                        'status' => 404,
                        'error' => 'انت ليس مدير محتوي',
                    ]);
                }
            }
    }
    public function updateContent(Request $request , $id){
        $post = Post::findOrFail($id);
        $vaildator = Validator::make($request->all() , [
            'channel' => 'required',
            'campaign' => '',
            'content_goal' => 'required',
            'content_type' => 'required',
            'post_content' => 'required',
            'content_image' => 'required',
            'publication_date' => 'required',
            'campaign_id' => '',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $oldVersion = $post->replicate();
            $oldVersion->save();
            $post->channel = json_encode($request->input('channel'));
            $post->campaign = $request->input('campaign');
            $post->content_goal = $request->input('content_goal');
            $post->content_type = json_encode($request->input('content_type'));
            $post->post_content = $request->input('post_content');
            if ($request->hasFile('content_image')) {
                $imageName = time().'.'.$request->content_image->extension();
                $request->content_image->move(public_path('images'), $imageName);
                $post->content_image = $imageName;
            }
            $date = Carbon::parse( $request->input('publication_date'));
            $formattedDate = $date->toDateTimeString();
            $post->publication_date = $formattedDate;
            $post->campaign_id = $request->input('campaign_id')? $request->input('campaign_id'):null;
            $post->save();
            $version = new PostVersion([
                'post_id' => $post->id,
                'oldVersion_id' =>$oldVersion->id,
                'submission_date_time' => $formattedDate,
                'selected' => false,
            ]);
            $oldVersion->delete();
            $version->save();
            return response()->json([
                'status' => 200,
                'message' => 'تم تعديل المحتوي بنجاح',
                'post' => $post,
            ]);
        }
    }
    public function fetchContentVersion(Request $request){
        $user = $request->user();
        $posts = Post::where('user_id', $user->id)->get();
        if(!$posts->isEmpty()){
            foreach ($posts as $post) {
                $postVersions = PostVersion::where('post_id', $post->id)->orderBy('id' , 'desc')->get();
            }
            if(!$postVersions->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'postVersions' => $postVersions,
                ]);
            }
            else{
                return response()->json([
                    'status' => 404,
                    'error' => 'لا يوجد اصدارات بعد',
                ]);
            }
        }
        else{
            return response()->json([
                'status' => 404,
                'error' => 'لا يوجد محتوي بعد لهذا العميل',
            ]);
        }

    }


    // this is approved specific version of the content
    public function approveVersion(Request $request , $versionId) {
        $version = PostVersion::findOrFail($versionId);
        $postId = $version->oldVersion_id;
        $post = Post::withTrashed()->find($postId);
        if($post){
            $post->restore();
            $user = $request->user();
            if($user->role === 'client'){
                $PostClientStatus = Post::where('user_id', $user->id)->where('id', $postId)->first();
                if($PostClientStatus){
                    $PostClientStatus->update([
                        'client_status' => 'مقبول',
                        'operation_status' => 'مقبول',
                        'account_manager_status' => 'مقبول',
                        'isApproved' => true,
                    ]);
                }
                    $version->selected = true;
                    $version->save();
                    $oldVersions = PostVersion::where('post_id', $version->post_id)
                    ->where('id', '!=', $versionId)->get();
                    if(!$oldVersions->isEmpty()){
                        foreach ($oldVersions as $oldVersion) {
                            $oldVersionId = $oldVersion->id;
                            $PostClientStatus = Post::where('user_id', $user->id)->where('id', $oldVersionId)->first();
                            if($PostClientStatus){
                                $PostClientStatus->update([
                                    'client_status' => 'جديد',
                                    'operation_status' => 'مسودة',
                                    'account_manager_status' => 'مسودة',
                                    'isApproved' => false,
                                ]);
                            }
                            $selectVersions = PostVersion::where('id', $oldVersionId)->get();
                            foreach ($selectVersions as $selectVersion) {
                                $selectVersion->selected = false;
                                $selectVersion->save();
                            }
                            $post = Post::where('id', $oldVersionId)->delete();
                        }
                        return response()->json([
                            'status' => 200,
                            'message' => 'تم قبول المحتوي من العميل بنجاح',
                        ]);
                    }
            }
        }
        else{
            $post = Post::findOrFail($postId);
            $user = $request->user();
            if($user->role === 'client'){
                $PostClientStatus = Post::where('user_id', $user->id)->where('id', $postId)->first();
                if($PostClientStatus){
                    $PostClientStatus->update([
                        'client_status' => 'مقبول',
                        'operation_status' => 'مقبول',
                        'account_manager_status' => 'مقبول',
                        'isApproved' => true,
                    ]);
                    return response()->json([
                        'status' => 200,
                        'message' => 'تم قبول المحتوي من العميل بنجاح',
                    ]);
                }
            }
        }
        return response()->json([
            'status' => 200,
            'message' => 'تم الموافقة على النسخة بنجاح واستعادة النسخة القديمة',
            'approved_version' => $version,
        ]);
    }

}
