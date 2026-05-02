<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\ActivitiesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentsController extends Controller
{
    use ActivitiesTrait;
    function postComment(Request $request){
        $validator = Validator::make($request->all(),[
            'content' => 'required|string',
            'type' => 'required|in:meal,workout,exercise',
            'target_id' => 'required|integer',
            'rating' => 'nullable|between:1,12'
        ]);
        if($validator->fails())
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->all()[0]
        ]);

        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->content = $request->content;
        $comment->type = $request->type;
        $comment->target_id = $request->target_id;
        if(isset($request->rating))
        $comment->rating = $request->rating;
        $comment->save();
        return response()->json([
            'status' => true,
            'message' => 'Comment Posted.'
        ]);
    }

    function typeWiseComments(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required|in:meal,workout,exercise'
        ]);
        if($validator->fails())
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->all()[0]
        ]);
        $comments = Comment::where('type',$request->type)->get();
        return response()->json([
            'status' => true,
            'data' => $comments
        ]);
    }

    function specificEntityComments(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required|in:meal,workout,exercise',
            'target_id' => 'required|integer'
        ]);
        if($validator->fails())
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->all()[0]
        ]);
        $comments = Comment::where('type',$request->type)->where('target_id',$request->target_id)->get();
        return response()->json([
            'status' => true,
            'data' => $comments
        ]);
    }
}
