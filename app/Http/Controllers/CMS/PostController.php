<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ContentGoal;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function store_content(Request $request){
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
            $post->client_status = 'جديد';
            $post->operation_status = 'مسودة';
            $post->manager_status = 'مسودة';
            $post->isPublished = false;
            if ($request->hasFile('content_image')) {
                $imageName = time().'.'.$request->content_image->extension();
                $request->content_image->move(public_path('images'), $imageName);
                $post->content_image = $imageName;
            }
            $post->publication_date = $request->input('publication_date');
            $user = $request->user();
            $user_id = $user->id;
            $post->user_id = $user_id;
            $post->campaign_id = $request->input('campaign_id')? $request->input('campaign_id'):null;
            $post->save();
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
            if($user->role === 'operation'){
                $OperationCompanies = Account::where(function ($query) use ($user) {
                    $query->whereHas('user', function ($query) use ($user) {
                        $query->where('role', 'operation')->where('operation_id', $user->account->operation_id);
                    });
                })->get();
                if(!$OperationCompanies->isEmpty()){
                    $posts = Post::where('user_id',$user->id)->get();
                    if($posts->isEmpty()){
                        return response()->json([
                            'status' => 404,
                            'error' => 'لم يوجد اي حسابات خاصة بك تمت اضافة المحتوي لها',
                        ]);
                    }
                    return response()->json([
                        'status' => 200,
                        'posts' => $posts,
                    ]);
                }
                else{
                    return response()->json([
                        'status' => 404,
                        'error' => 'تمهل حتي يتم تعيينك كصانع للمحتوي' ,
                    ]);
                }
            }
            else if($user->role === 'account_manager'){
                $ManagerCompanies = Account::where(function ($query) use ($user) {
                    $query->WhereHas('user', function ($query) use ($user) {
                        $query->where('manager_id' ,$user->id);
                    });
                })->get();
                if(!$ManagerCompanies->isEmpty()){
                    $operationId = $ManagerCompanies->pluck('operation_id')->unique()->toArray();
                    $managerPosts = Post::where('user_id', $user->id)->get();
                    $operationPosts = Post::whereHas('user', function ($query) use ($operationId) {
                        $query->where('id', $operationId);
                    })->get();
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
                    return response()->json([
                        'status' => 200,
                        'posts' => $posts,
                    ]);
                }
                else{
                    return response()->json([
                        'status' => 404,
                        'error' => 'تمهل حتي يتم تعيينك او كمدير للمحتوي' ,
                    ]);
                }
            }
            else if($user->role === 'client'){
                $account = $user->account;
                $posts = Post::where(function ($query) use ($account) {
                    $query->whereHas('user', function ($query) use ($account) {
                        $query->where('role', 'operation')->where('id', $account->operation_id);
                    })->orWhereHas('user', function ($query) use ($account) {
                        $query->where('role', 'account_manager')->where('id', $account->manager_id);
                    });
                })->get();
                if(!$posts->isEmpty()){
                    $allPostsPublished = true;
                    foreach ($posts as $post) {
                        if (!$post->isPublished) {
                            $allPostsPublished = false;
                            break;
                        }
                    }
                    if ($allPostsPublished) {
                        return response()->json([
                            'status' => 200,
                            'posts' => $posts,
                        ]);
                    } else {
                        return response()->json([
                            'status' => 404,
                            'error' => 'لم يتم نشر المحتوي لهذا الحساب بعد',
                        ]);
                    }
                }
                else{
                    return response()->json([
                        'status' => 404,
                        'error' => 'لا يوجد محتوي متاح لك حتي الان',
                    ]);
                }
            }
    }
    // this is fetch content goals for add posts popup
    public function fetchContentGoal(){
        $contentGoal = ContentGoal::all();
        return response()->json([
            'content Goals' => $contentGoal
        ]);
    }
}
