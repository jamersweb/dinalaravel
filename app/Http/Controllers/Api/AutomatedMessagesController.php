<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomatedMessage;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AutomatedMessagesController extends Controller
{
    //
    function customizeMessage(Request $request){
        $vld = Validator::make($request->all(),[
            'msg_id' => 'required',
            'type' => 'required|in:text,audio,video',
            'content' => 'string',
            'content_ar' => 'string',
            'audio' => 'mimes:mp3,wav',
            'audio_ar' => 'mimes:mp3,wav',
            'video' => 'mimes:mp4,mkv,m4v',
            'video_ar' => 'mimes:mp4,mkv,m4v'
        ]);
        if($vld->fails())
        return response()->json([
            'status' => true,
            'message' =>  $vld->errors()->all()[0]
        ]);
        $msg = AutomatedMessage::find($request->msg_id);
        if(is_null($msg))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Id'
        ]);
        if($request->type==='text'){
            if(!isset($request->content) || !isset($request->content_ar))
            return response()->json([
                'status' => false,
                'message' => 'No Text Content Provided.'
            ]);
            $msg->content = $request->content;
            $msg->content_ar = $request->content_ar;
        } else {
            if(isset($request->audio) && isset($request->audio_ar)){
                $fileUrl = "auto_msg_en_".time().'.'.request()->audio->getClientOriginalExtension();
                $request->audio->storeAs('messages_files', $fileUrl, config('filesystems.default'));
                $fileUrlar = "auto_msg_ar_".time().'.'.request()->audio_ar->getClientOriginalExtension();
                $request->audio_ar->storeAs('messages_files', $fileUrlar, config('filesystems.default'));
            } else if(isset($request->video) && isset($request->video_ar)){
                $fileUrl = "auto_msg_en_".time().'.'.request()->video->getClientOriginalExtension();
                $request->video->storeAs('messages_files', $fileUrl, config('filesystems.default'));
                $fileUrlar = "auto_msg_ar_".time().'.'.request()->video_ar->getClientOriginalExtension();
                $request->video_ar->storeAs('messages_files', $fileUrlar, config('filesystems.default'));
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No English or Arabic File Provided.'
                ]);
            }
            $msg->content = $fileUrl;
            $msg->content_ar = $fileUrlar;
        }
        $msg->type = $request->type;
        $msg->update();
        return response()->json([
            'status' => true,
            'message' => 'Auto Message Customized.'
        ]); 
    }

    function switchStatus($id){
        $msg = AutomatedMessage::find($id);
        if(is_null($msg))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Id'
        ]);
        if($msg->status===1)
        $msg->status = 0;
        else 
        $msg->status = 1;
        $msg->update();
        return response()->json([
            'status' => true,
            'message' => 'Status Changed'
        ]);
    }

    function autoMsgs(){
        $msgs = AutomatedMessage::all();
        return response()->json([
            'status' => true,
            'data' => $msgs
        ]);
    }

    static function sendAutoMessage($userId,$type){
        if($type==='signup')
        $msgId = 1;
        else if($type==='day7')
        $msgId = 2;
        else if($type==='day30')
        $msgId = 3;
        else if($type==='firstProg')
        $msgId = 4;
        else if($type==='missingwrk')
        $msgId = 5;
        else
        return "invalid message selected";

        $autoMsg = DB::table('automated_messages')->where('id', $msgId)->first();
        if (is_null($autoMsg)) {
            return "message not found";
        }
        if ($autoMsg->status === 1) {
            $chat = Chat::where('user_id',$userId)->first();
            if(is_null($chat)){
                $chat = new Chat();
                $chat->user_id = $userId;
                $chat->save();
            }
            $sendMsg = new Message();
            $sendMsg->chat_id = $chat->id;
            $sendMsg->sender = 'admin';
            if($autoMsg->type==='text'){
                $sendMsg->content = $autoMsg->content;
                $sendMsg->content_ar = $autoMsg->content_ar;
                $sendMsg->file_url = '';
            } else {
                $sendMsg->content = '';
                $sendMsg->content_ar = '';
                $sendMsg->file_url = $autoMsg->content;
            }
            $sendMsg->msg_type = $autoMsg->type;
            $sendMsg->save();
            $chat->update();
            return "sent";
        }
        return "message turned off";
    }
}
