<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\User;
use App\Models\UserDetail;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupChatController extends Controller
{
    use NotificationsTrait,ActivitiesTrait;
    //
    function allGroups(){
        $groupIds = GroupMember::where('user_id',Auth::id())->pluck('group_id')->toArray();
        $groups = Group::whereIn('id',$groupIds)->orderBy('updated_at','desc')->get();
        $userLang = $this->userSelecetdLanguage(Auth::id());
        foreach ($groups as $group) {
            $lastMsg = $group->lastMessage();
            if(Auth::user()->role===2)
            $group->unread = $group->unreadMsgs();
            $group->members = $group->memberCount();
            $group->last_message = is_null($lastMsg)?'':($userLang==='en'?$lastMsg->content:$lastMsg->content_ar);
            $group->last_type = is_null($lastMsg)?'':$lastMsg->msg_type;
        }
        return response()->json([
            'status' => true,
            'data' => $groups
        ]);
    }

    function groupMembers($id){
        $members = GroupMember::where('group_id',$id)->where('role','member')->get(['user_id']);
        foreach ($members as $member) {
            $member->name = $member->userName();
            $member->image = $member->userImage();
        }
        return response()->json([
            'status' => true,
            'data' => $members
        ]);
    }

    function createGroup(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required|string',
            'image' => 'required|mimes:jpg,png,jpeg',
            'msg_access' => 'required|in:admins,members'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $group = new Group();
        $group->name = $request->name;
        $group->msg_access = $request->msg_access;
        $group->save();

        $fileUrl = $group->id."_group_icon_file_".time().'_'.uniqid().'.'.$request->image->getClientOriginalExtension();
        $request->image->storeAs('messages_files', $fileUrl, 'fwd_media');
        $group->image = $fileUrl;
        $group->save();

        $member = new GroupMember();
        $member->group_id = $group->id;
        $member->user_id = Auth::id();
        $member->role = 'admin';
        $member->save();
        return response()->json([
            'status' => true,
            'message' => 'Group Created.'
        ]);
    }

    function addMembers(Request $request){
        $validate = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
            'user_ids' => 'required|array'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $adminName = strtoupper(Auth::user()->name);
        $groupName = strtoupper(Group::where('id',$request->group_id)->pluck('name')->first());
        $notiRecievers = [];
        $notiRecieversAr = [];
        $notiTitle = 'Added to Group!';
        $notiTitleAr = 'تمت إضافتك للمجموعة';
        $notiContent = $adminName.' added you to '.$groupName.' group.';
        $notiContentAr = 'أضافك'.$adminName.' إلى مجموعة '.$groupName;
        foreach ($request->user_ids as $uId) {
            $userExist = User::where('id',$uId)->where('role',1)->first(['fcm_token','id']);
            if(!is_null($userExist)){
                if(is_null(GroupMember::where('group_id',$request->group_id)->where('user_id',$uId)->first())){
                    $member = new GroupMember();
                    $member->group_id = $request->group_id;
                    $member->user_id = $uId;
                    $member->role = 'member';
                    $member->save();

                    $this->storeNotification($userExist->id,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr);
                    if($this->userSelecetdLanguage($uId)==='en')
                    array_push($notiRecievers,$userExist->fcm_token);
                    else
                    array_push($notiRecieversAr,$userExist->fcm_token);
                }
            }
        }
        $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
        $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
        return response()->json([
            'status' => true,
            'message' => 'Group Members Added.'
        ]);
    }

    function removeMembers(Request $request){
        $validate = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
            'user_ids' => 'required|array'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $ids = GroupMember::where('group_id',$request->group_id)->whereIn('user_id',$request->user_ids)->where('role','member')->pluck('id');
        GroupMember::destroy($ids);
        return response()->json([
            'status' => true,
            'message' => 'Group Members Removed.'
        ]);
    }

    function renameGroup(Request $request){
        $validate = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
            'name' => 'required|string'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $group = Group::find($request->group_id);
        $oldName = $group->name;
        $group->name = $request->name;
        $group->update();

        $groupMembers = GroupMember::where('group_id',$request->group_id)->where('role','member')->pluck('user_id')->toArray();

        $notiRecievers = [];
        $notiRecieversAr = [];
        $notiTitle = 'Group Renamed!';
        $notiTitleAr = 'تم تغيير اسم المجموعة';
        $notiContent = Auth::user()->name.' changed group name from '.strtoupper($oldName).' to '.strtoupper($request->name);
        $notiContentAr = strtoupper(Auth::user()->name).' غير اسم المجموعة من '.strtoupper($oldName).' إلى '.strtoupper($request->name);
        foreach ($groupMembers as $gMember) {
            $this->storeNotification($gMember,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr);
            if($this->userSelecetdLanguage($gMember)==='en')
            array_push($notiRecievers,User::where('id',$gMember)->pluck('fcm_token')->first());
            else
            array_push($notiRecieversAr,User::where('id',$gMember)->pluck('fcm_token')->first());
        }
        $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
        $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
        return response()->json([
            'status' => true,
            'message' => 'Group Members Added.'
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Group Renamed.'
        ]);
    }

    function leaveGroup($id){
        $member = GroupMember::where('group_id',$id)->where('user_id',Auth::id())->first();
        if($member){
            $memberName = UserDetail::where('user_id',Auth::id())->first(['name','Lastname']);
            $groupName = Group::where('id',$id)->pluck('name')->first();
            $member->delete();
            
            $groupMembers = GroupMember::where('group_id',$id)->where('role','member')->pluck('user_id')->toArray();
            $notiRecievers = [];
            $notiRecieversAr = [];
            $notiTitle = 'Group Member Left!';
            $notiTitleAr = '!غادر عضو للمجموعة';
            $notiContent = strtoupper($memberName->name).' '.strtoupper($memberName->Lastname).' left '.strtoupper($groupName);
            $notiContentAr = strtoupper($memberName->name).' '.strtoupper($memberName->Lastname).' غادر '.strtoupper($groupName);
            foreach ($groupMembers as $gMember) {
                $this->storeNotification($gMember,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr);
                if($this->userSelecetdLanguage($gMember)==='en')
                array_push($notiRecievers,User::where('id',$gMember)->pluck('fcm_token')->first());
                else
                array_push($notiRecieversAr,User::where('id',$gMember)->pluck('fcm_token')->first());
            }
            $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
            $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
            return response()->json([
                'status' => true,
                'message' => 'Left Group.'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not a Group Member.'
        ]);
    }

    function deleteGroup($id){
        $group = Group::find($id);
        if($group){
            $groupMembers = GroupMember::where('group_id',$id)->where('role','member')->pluck('user_id')->toArray();
            $group->delete();
            $notiTitle = 'Group Deleted!';
            $notiTitleAr = 'تم الغاء المجموعة';
            $notiRecievers = [];
            $notiRecieversAr = [];
            $notiContent = strtoupper(Auth::user()->name).' deleted the group '.strtoupper($group->name);
            $notiContentAr = ' '.strtoupper(Auth::user()->name).' حذف المجموعة '.strtoupper($group->name).' ';
            foreach ($groupMembers as $gMember) {
                $this->storeNotification($gMember,$notiTitle,$notiTitleAr,$notiContent,$notiContentAr);
                if($this->userSelecetdLanguage($gMember)==='en')
                array_push($notiRecievers,User::where('id',$gMember)->pluck('fcm_token')->first());
                else
                array_push($notiRecieversAr,User::where('id',$gMember)->pluck('fcm_token')->first());
            }
            $this->sendFirebaseNotification($notiRecievers,$notiTitle,$notiContent);
            $this->sendFirebaseNotification($notiRecieversAr,$notiTitleAr,$notiContentAr);
            return response()->json([
                'status' => true,
                'message' => 'Group Deleted.'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Group Does Not Exist.'
        ]);
    }

    function sendTextMessage(Request $request){
        $validate = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
            'content' => 'required|string'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $group = Group::find($request->group_id);
        if(Auth::user()->role===1 && $group->msg_access==='admins')
        return response()->json([
            'status' => false,
            'message' => 'Only Admin can Send Message in this Group'
        ]);
        $message = new GroupMessage();
        $message->group_id = $request->group_id;
        $message->from  = Auth::id();
        $currentMsgLang = $this->detectLanguage($request->content);     // check language of new message
        if($currentMsgLang==='en'){
            $engMsg = $request->content;
            $arbMsg = $this->getTranslatedText($request->content,'ar');     // convert to arabic if originaly english
        } else if($currentMsgLang==='ar') {
            $engMsg = $this->getTranslatedText($request->content,'en');     // convert to english if originaly arabic
            $arbMsg = $request->content;
        } else {                                        // store as it is if any other language
            $engMsg = $request->content;
            $arbMsg = $request->content;
        }
        $message->content = $engMsg;
        $message->content_ar = $arbMsg;
        $message->msg_type = 'text';
        $message->save();
        $group->update();
        $justSent = GroupMessage::find($message->id);
        $justSent->time = $justSent->created_at->diffForHumans();
        $justSent->from_name = $justSent->userName();
        return response()->json([
            'status' => true,
            'message' => 'Message Sent.',
            'sent_msg' => json_encode($justSent),
        ]);
    }

    function sendFileMessageAdmin(Request $request){
        $validate = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
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


        $fileUrl = $request->group_id."_group_message_file_".time().'_'.uniqid().'.'.request()->file->getClientOriginalExtension();
        $request->file->storeAs('messages_files', $fileUrl, 'fwd_media');

        $msg = new GroupMessage();
        $msg->group_id = $request->group_id;
        $msg->from = Auth::id();
        $msg->content = $fileName;
        $msg->content_ar = $fileName;
        $msg->msg_type = $fileType;
        $msg->file_url = $fileUrl;
        $msg->save();
        
        $group = Group::find($request->group_id);
        $group->last_message = substr($fileName,0,30);
        $group->last_type = $fileType;
        $group->update();

        $justSent = GroupMessage::find($msg->id);
        $justSent->time = $justSent->created_at->diffForHumans();
        $justSent->from_name = $justSent->userName();

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
            'group_id' => 'required|exists:groups,id',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $group = Group::find($request->group_id);
        if($group->msg_access==='admins')
        return response()->json([
            'status' => false,
            'message' => 'Only Admin can Send Message in this Group'
        ]);

        $fileName = $request->file->getClientOriginalName();
        $fileUrl = $request->group_id."_group_message_file_".time().'_'.uniqid().'.'.request()->file->getClientOriginalExtension();
        $request->file->storeAs('messages_files', $fileUrl, 'fwd_media');

        $msg = new GroupMessage();
        $msg->group_id = $request->group_id;
        $msg->from = Auth::id();
        $msg->content = $fileName;
        $msg->content_ar = $fileName;
        $msg->msg_type = $request->type;
        $msg->file_url = $fileUrl;
        $msg->save();
        
        $group->update();

        $justSent = GroupMessage::find($msg->id);
        $justSent->time = $justSent->created_at->diffForHumans();
        return response()->json([
            'status' => true,
            'message' => 'Message Sent Successfully.',
            'sent_msg' => json_encode($justSent)
        ]); 
    }

    function groupMessages($id){
        $isMember = GroupMember::where('user_id',Auth::id())->where('group_id',$id)->first();
        if(is_null($isMember))
        return response()->json([
            'status' => false,
            'message' => 'Not a Member.'
        ]);
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $messages = GroupMessage::where('group_id',$id)->orderBy('created_at','desc')->limit(100)->get();
        foreach ($messages as $msg) {
            $msg->from_name = $msg->userName();
            if(Carbon::today()>$msg->created_at)
            $msg->time = $msg->created_at->format('h:i a, d/m');
            else
            $msg->time = $msg->created_at->format('h:i a');
            if(Auth::user()->role===1){
                $msg->content = $userLang==='en'?$msg->content:$msg->content_ar;
                unset($msg->content_ar);
            }
        }
        if(Auth::user()->role===2)
        GroupMessage::where('group_id',$id)->where('status',0)->update(['status' => 1]);
        return response()->json([
            'status' => true,
            'data' => $messages,
        ]); 
    }

    function clientListToAdd($id){
        $alreadyMembers = GroupMember::where('group_id',$id)->where('role','member')->pluck('user_id')->toArray();
        $subscribedUserIds = \App\Models\UserDetail::where('subscription_status','active')->pluck('user_id')->toArray();
        $users = User::whereNotIn('id',$alreadyMembers)->where('role',1)->whereIn('id',$subscribedUserIds)->get('id');
        foreach ($users as $user) {
            $user->name = $user->fullName();
            $user->sub = $user->subs();
            $user->image = $user->profilePicture();
        }
        return response()->json([
            'status' => true,
            'data' => $users,
        ]);
    }

    function deleteMessage($id){
        $msg = GroupMessage::find($id);
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

    function addGroupDesc(Request $request){
        $validate = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
            'description' => 'required|string',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $group = Group::find($request->group_id);
        $group->description = $request->description;
        $group->update();
        return response()->json([
            'status' => true,
            'message' => 'Group Description Added.'
        ]);
    }

    function switchGroupLabel($id){
        $group = Group::find($id);
        if(is_null($group))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Group Id.'
        ]);
        if($group->label==='all')
        $group->label = 'my_group';
        else
        $group->label = 'all';
        $group->update();
        return response()->json([
            'status' => true,
            'message' => 'Group Label Updated.'
        ]);
    }
}
