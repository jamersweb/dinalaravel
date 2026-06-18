<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHandle;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Tag;
use App\Traits\ActivitiesTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatsController extends Controller
{
    //
    use ActivitiesTrait;

    function clientListForNewChat(){
        // $alreadyChats = Chat::pluck('user_id')->toArray();
        $clients = User::where('role',1)->where('status','active')->get(['id','name']);
        foreach ($clients as $client) {
            $client->full_name = $client->fullName();
            $client->subscription = $client->subs();
            $client->image = $client->profilePicture();
        }
        return response()->json([
            'status' => true,
            'data' => $clients
        ]);
    }

    function createNewChat($id){
        $user = User::find($id);
        if(is_null($user))
        return response()->json([
            'status' => false,
            'message' => 'User not found.'
        ]);
        if(Chat::where('user_id',$id)->first())
        return response()->json([
            'status' => false,
            'message' => 'Chat Already Exists.'
        ]);
        $chat = new Chat();
        $chat->user_id = $id;
        $chat->save();

        $created = Chat::find($chat->id);
        $created->user_name = $created->userName();
        $created->image = $created->userImage();
        $created->sub = $created->userSub();
        $created->unread = 0;
        return response()->json([
            'status' => true,
            'message' => 'New Chat Created.',
            'chat' => $created
        ]);
    }

    function deleteChat($id){
        $chat = Chat::find($id);
        if($chat){
            $chat->delete();
            return response()->json([
                'status' => true,
                'message' => 'Chat Deleted.',
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Chat Does Not Exist.',
        ]);
    }

    function getAllChats(){
        $chats = Chat::orderBy('updated_at','DESC')->get();
        
        // Fix N+1: Fetch all user tags in one query
        $userIds = $chats->pluck('user_id')->toArray();
        $usersWithTags = User::whereIn('id', $userIds)
            ->with('tags')
            ->get()
            ->keyBy('id');
        
        foreach ($chats as $chat) {
            $lastMessage = $chat->lastMessage();
            $chat->last_message = is_null($lastMessage)?'-':$lastMessage->content;
            $chat->last_type = is_null($lastMessage)?'-':$lastMessage->msg_type;
            $chat->user_name = $chat->userName();
            $chat->image = $chat->userImage();
            $chat->sub = $chat->userSub();
            $chat->unread = $chat->unreadMsgs('user');
            
            // Add user tags to chat (from consultation form answers)
            $user = $usersWithTags->get($chat->user_id);
            try {
                if ($user && $user->tags instanceof \Illuminate\Database\Eloquent\Collection && $user->tags->count() > 0) {
                    $chat->user_tags = $user->tags->pluck('name')->toArray();
                } else {
                    $chat->user_tags = [];
                }
            } catch (\Exception $e) {
                $chat->user_tags = [];
            }
        }
        return response()->json([
            'status' => true,
            'data' => $chats
        ]);
    }

    function chatConvo(){
        $chat = Chat::where('user_id',Auth::id())->first();
        if(is_null($chat)){
            $chat = new Chat();
            $chat->user_id = Auth::id();
            $chat->save();
            $chat = Chat::find($chat->id);
        }
        $adminUserId = User::where('role',2)->pluck('id')->first();
        $admin = $adminUserId
            ? UserDetail::where('user_id',$adminUserId)->first(['name','Lastname','picture'])
            : null;
        $chat->user_name = $admin
            ? trim(($admin->name ?? 'Coach').' '.($admin->Lastname ?? ''))
            : 'Coach';
        $chat->image = $admin ? $admin->picture : null;
        $chat->unread = $chat->unreadMsgs('admin');
        
        // Add user tags to chat response
        $user = User::with('tags')->find(Auth::id());
        try {
            if($user && $user->tags instanceof \Illuminate\Database\Eloquent\Collection && $user->tags->count() > 0) {
                $chat->user_tags = $user->tags->pluck('name')->toArray();
            } else {
                $chat->user_tags = [];
            }
        } catch (\Exception $e) {
            $chat->user_tags = [];
        }
        
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $lastMessage = $chat->lastMessage();
        if($userLang==='en')
        $chat->last_message = is_null($lastMessage)?'-':$lastMessage->content;
        else
        $chat->last_message = is_null($lastMessage)?'-':$lastMessage->content_ar;
        $chat->last_type = is_null($lastMessage)?'-':$lastMessage->msg_type;
        return response()->json([
            'status' => true,
            'data' => $chat
        ]);
    }

    function sendTextMessageAdmin(Request $request){
        $validate = Validator::make($request->all(),[
            'chat_id' => 'required|exists:chats,id',
            'content' => 'required|string',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $temp = $this->getTranslatedText($request->content,'ar'); 
        $msg = new Message();
        $msg->chat_id = $request->chat_id;
        $msg->sender = 'admin';
        $msg->content = $request->content;
        $msg->content_ar = is_null($temp)?$request->content:$temp;  // transalte to arabic for arabic user
        $msg->msg_type = 'text';
        $msg->save();

        $chat = Chat::find($request->chat_id);
        $chat->updated_at = Carbon::now();
        $chat->update();

        $userLang = $this->userSelecetdLanguage($chat->user_id);
        
        $justSent = Message::find($msg->id);
        $justSent->content = $userLang==='en'?$justSent->content:$justSent->content_ar;
        unset($justSent->content_ar);
        $justSent->time = $justSent->created_at->diffForHumans();

        return response()->json([
            'status' => true,
            'message' => 'Message Sent Successfully.',
            'sent_msg' => json_encode($justSent)
        ]); 
    }

    function sendFileMessageAdmin(Request $request){
        $validate = Validator::make($request->all(),[
            'chat_id' => 'required|exists:chats,id',
            'file' => 'required|mimes:png,jpg,jpeg,mp4,mkv,mov,m4v,pdf,docx,doc,txt,mp3,wav',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $fileName = $request->file->getClientOriginalName();
        $extension = $request->file->getClientOriginalExtension();
        
        if($extension==='png' || $extension==='jpg' || $extension==='jpeg')
        $fileType = 'image';
        else if($extension==='mp4' || $extension==='mkv' || $extension==='mov' || $extension==='m4v')
        $fileType = 'video';
        else if($extension==='pdf' || $extension==='docx' || $extension==='xlsx' || $extension==='txt')
        $fileType = 'document';
        else if($extension==='mp3' || $extension==='wav')
        $fileType = 'audio';
        else 
        return response()->json([
            'status' => false,
            'message' => 'File format is not valid. ('.$extension.')'
        ]);

        $fileUrl = $request->chat_id."_message_file_".time().'_'.uniqid().'.'.request()->file->getClientOriginalExtension();
        $request->file->storeAs('messages_files', $fileUrl, 'fwd_media');

        $msg = new Message();
        $msg->chat_id = $request->chat_id;
        $msg->sender = 'admin';
        $msg->content = $fileName;
        $msg->content_ar = $fileName;
        $msg->msg_type = $fileType;
        $msg->file_url = $fileUrl;
        $msg->save();
        
        $chat = Chat::find($request->chat_id);
        $chat->updated_at = Carbon::now();
        $chat->update();

        $justSent = Message::find($msg->id);
        $justSent->time = $justSent->created_at->diffForHumans();

        return response()->json([
            'status' => true,
            'message' => 'Message Sent Successfully.',
            'sent_msg' => json_encode($justSent)
        ]); 
    }

    function chatMessages($id){
        $messages = Message::where('chat_id',$id)->orderBy('created_at','desc')->get();
        Message::where('chat_id',$id)->where('sender','user')->where('status',0)->update(['status' => 1]);
        foreach ($messages as $msg) {
            if(Carbon::today()>$msg->created_at)
            $msg->time = $msg->created_at->format('h:i a, d/m');
            else
            $msg->time = $msg->created_at->format('h:i a');
        }
        return response()->json([
            'status' => true,
            'data' => $messages
        ]);
    }

    function userChatMessages(){
        $chat_id = Chat::where('user_id',Auth::id())->pluck('id')->first();
        if(is_null($chat_id))
        return response()->json([
            'status' => false,
            'message' => 'No Chat Exist.'
        ]);
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $messages = Message::where('chat_id',$chat_id)->orderBy('created_at','desc')->get();
        foreach ($messages as $msg) {
            $msg->content = $userLang==='en'?$msg->content:$msg->content_ar;
            unset($msg->content_ar);
            if(Carbon::today()>$msg->created_at)
            $msg->time = $userLang==='en'?$msg->created_at->format('h:i a, d/m'):$msg->created_at->locale('ar')->translatedFormat('d/m, a h:i');
            else
            $msg->time = $userLang==='en'?$msg->created_at->format('h:i a'):$msg->created_at->locale('ar')->translatedFormat('a h:i');
        }
        Message::where('chat_id',$chat_id)->where('sender','admin')->where('status',0)->update(['status' => 1]);
        return response()->json([
            'status' => true,
            'data' => $messages
        ]);
    }

    function sendTextMessageUser(Request $request){
        $validate = Validator::make($request->all(),[
            'content' => 'required|string',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $currentMsgLang = $this->detectLanguage($request->content);     // check language of new
        if($currentMsgLang==='en'){
            $temp = $this->getTranslatedText($request->content,'ar');   // convert to arabic if originaly english
            $engMsg = $request->content;
            $arbMsg = is_null($temp)?$request->content:$temp;
        } else if($currentMsgLang==='ar') {
            $temp = $this->getTranslatedText($request->content,'en');      // convert to english if originaly arabic
            $engMsg = is_null($temp)?$request->content:$temp;
            $arbMsg = $request->content;
        } else {                                        // store as it is if any other language
            $engMsg = $request->content;
            $arbMsg = $request->content;
        }

        $chat_id = Chat::where('user_id',Auth::id())->pluck('id')->first();
        if(is_null($chat_id)){
            $chat = new Chat();
            $chat->user_id = Auth::id();
            $chat->save();
            $chat_id = $chat->id;
        }
        $msg = new Message();
        $msg->chat_id = $chat_id ;
        $msg->sender = 'user';
        $msg->content = $engMsg;
        $msg->content_ar = $arbMsg;
        $msg->msg_type = 'text';
        $msg->save();

        $chat = Chat::find($chat_id);
        $chat->updated_at = Carbon::now();
        $chat->update();

        $justSent = Message::find($msg->id);
        $justSent->content = $request->content;
        unset($justSent->content_ar);
        if($userLang==='ar'){
            Carbon::setlocale("ar");
            $justSent->time = Carbon::parse($justSent->created_at)->diffForHumans();
        }
        else
        $justSent->time = $justSent->created_at->diffForHumans();

        return response()->json([
            'status' => true,
            'message' => 'Message Sent Successfully.',
            'sent_msg' => json_encode($justSent)
        ]);
    }

    function sendFileMessageUser(Request $request){
        $validate = Validator::make($request->all(),[
            'file' => 'required|mimes:png,jpg,jpeg,mp4,mkv,mov,m4v,pdf,docx,doc,txt,mp3,wav,m4a',
            'type' => 'required|in:audio,video,document,image',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $fileName = $request->file->getClientOriginalName();

        $chat_id = Chat::where('user_id',Auth::id())->pluck('id')->first();
        if(is_null($chat_id)){
            $chat = new Chat();
            $chat->user_id = Auth::id();
            $chat->save();
            $chat_id = $chat->id;
        }
        $fileUrl = $chat_id."_message_file_".time().'_'.uniqid().'.'.request()->file->getClientOriginalExtension();
        $request->file->storeAs('messages_files', $fileUrl, 'fwd_media');

        $msg = new Message();
        $msg->chat_id = $chat_id;
        $msg->sender = 'user';
        $msg->content = $fileName;
        $msg->content_ar = $fileName;
        $msg->msg_type = $request->type;
        $msg->file_url = $fileUrl;
        $msg->save();
        
        $chat = Chat::find($chat_id);
        $chat->updated_at = Carbon::now();
        $chat->update();

        $justSent = Message::find($msg->id);
        $justSent->time = $justSent->created_at->diffForHumans();
        return response()->json([
            'status' => true,
            'message' => 'Message Sent Successfully.',
            'sent_msg' => json_encode($justSent)
        ]); 
    }

    function multipleUsersMessage(Request $request){
        $validate = Validator::make($request->all(),[
            'user_ids' => 'required|array',
            'content' => 'required|string',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        foreach ($request->user_ids as $uId) {
            $user = User::find($uId);
            if($user){
                $chat_id = Chat::where('user_id',$uId)->pluck('id')->first();
                if(is_null($chat_id)){
                    $newChat = new Chat();
                    $newChat->user_id = $uId;
                    $newChat->save();
                    $chat_id = $newChat->id;
                }
                $newMessage = new Message();
                $newMessage->chat_id = $chat_id;
                $newMessage->sender = 'admin';
                $newMessage->content = $request->content;
                $newMessage->msg_type = 'text';
                $newMessage->save();

                $newChat = Chat::find($chat_id);
                $newChat->last_message = substr($newMessage->content,0,30);
                $newChat->last_type = 'text';
                $newChat->update();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Message Sent.'
        ]);
    }

    function archiveChat($id){
        $chat = Chat::find($id);
        if(is_null($chat))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Chat ID.'
        ]);
        $chat->status = 'archived';
        $chat->update();
        return response()->json([
            'status' => true,
            'message' => 'Chat Archived.'
        ]);
    }

    function unArchiveChat($id){
        $chat = Chat::find($id);
        if(is_null($chat))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Chat ID.'
        ]);
        $chat->status = 'active';
        $chat->update();
        return response()->json([
            'status' => true,
            'message' => 'Chat Unarchived.'
        ]);
    }

    function deleteMessage($id){
        $msg = Message::find($id);
        if(is_null($msg))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Message ID.'
        ]);
        $msg->delete();
        return response()->json([
            'status' => true,
            'message' => 'Message Deleted.'
        ]);
    }

    function getTranslatedText(string $inputText,string $targetLanguage) {
        try {
            $languageModel = config('app.google_trans_model');
            $translateBaseUrl = config('app.google_trans_baseUrl');
            $tranlateApiKey = config('app.google_api_key');
            $inputText = urlencode($inputText);

            $response = Http::get($translateBaseUrl."?q=".$inputText."&target=".$targetLanguage."&model=".$languageModel."&key=".$tranlateApiKey);
            if(isset($response['data']) && isset($response['data']['translations']) && count($response['data']['translations'])>0)
            return $response['data']['translations'][0]['translatedText'];
            else
            return null;
        } catch(Exception $er){
            Log::info("Error Translating: (".$er->getMessage().")");
            return null; 
        }
    }

    function detectLanguage(string $inputText){
        try {
            $languageModel = config('app.google_trans_model');
            $translateBaseUrl = config('app.google_trans_baseUrl');
            $tranlateApiKey = config('app.google_api_key');
            $inputText = urlencode(substr($inputText,0,30));    // detection can be done by 30 caharcters

            $response = Http::get($translateBaseUrl."/detect?q=".$inputText."&model=".$languageModel."&key=".$tranlateApiKey);
            if(isset($response['data']) && isset($response['data']['detections']) && count($response['data']['detections'])>0)
            return $response['data']['detections'][0][0]['language'];
            else
            return null;
        } catch(Exception $er){
            Log::info("Error Detecting: (".$er->getMessage().")");
            return null; 
        }
    }
}
