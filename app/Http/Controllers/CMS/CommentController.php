<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostClientStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class CommentController extends Controller
{
    public function storeComment(Request $request){
        $vaildator = Validator::make($request->all() , [
            'comment_text' => 'required',
            'comment_image' => 'required',
            'post_id' => 'required',
            'recipient_id' => 'required',
        ]);
        if($vaildator->fails()){
            return response()->json([
                'status' => 404,
                'errors' => $vaildator->messages(),
            ]);
        }
        else{
            $user = $request->user();
            $comment = new Comment();
            $comment->comment_text = $request->input('comment_text');
            if ($request->hasFile('comment_image')) {
                $imageName = time().'.'.$request->comment_image->extension();
                $request->comment_image->move(public_path('images'), $imageName);
                $comment->comment_image = $imageName;
            }
            $comment->user_id = $user->id;
            $postId = $request->input('post_id');
            $comment->post_id = $postId;
            $comment->recipient_id = $request->recipient_id;
            $comment->save();
            $post = Post::findOrFail($postId);
            $post->comments()->where('recipient_id', $request->user()->id)->update(['read_status' => 'read']);
            if ($request->user()->role === "client") {
                $PostClientStatus = Post::where('user_id', $user->id)->where('id', $postId)->first();
                if($PostClientStatus){
                    $PostClientStatus->update([
                        'client_status' => 'تعديلات',
                        'operation_status' => 'تعديلات',
                        'account_manager_status' => 'تعديلات',
                    ]);
                }
            }
            return response()->json([
                'status' => 200,
                'message' => 'تمت اضافة التعليق بنجاح',
                'post' => $comment,
            ]);
        }
    }
    public function fetchComment(Request $request , $postId){
        $user = $request->user();
        if($user->role === 'account_manager' || $user->role === 'client'){
            $comments = Comment::where('post_id', $postId)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('recipient_id', $user->id);
                })
                ->get();
        if($comments->isEmpty()){
            return response()->json([
                'status' => 404,
                'error' => 'لا توجد تعليقات حتي الان',
            ]);
        }
        return response()->json([
            'status' => 200,
            'comments' => $comments,
        ]);
        }
        else if($user->role === 'operation'){
            $comments = Comment::where('post_id', $postId)->get();
            if($comments->isEmpty()){
                return response()->json([
                    'status' => 404,
                    'error' => 'لا توجد تعليقات حتي الان',
                ]);
            }
            return response()->json([
                'status' => 200,
                'comments' => $comments,
            ]);
        }
    }
}
