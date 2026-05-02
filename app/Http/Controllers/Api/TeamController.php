<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    //
    function allTeam(){
        $team = User::where('role','!=',1)->where('role','!=',2)->with('userdetails')->orderBy('id','desc')->get();
        foreach ($team as $t) {
            $t->full_name = $t->fullName();
            $t->role = $t->role==3?'trainer':'manager';
        }
        return response()->json([
            'status' => true,
            'data' => $team
        ]);
    }

    function createTeamMember(Request $request){
        $validate = Validator::make($request->all(),[
            'first_name' => 'required',
            'last_name' => 'required',
            'role' => 'required|in:trainer,manager',
            'email' => 'required|email',
            'password' => 'required',
            'phone' => 'required|numeric',
            'country' => 'required',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'picture' => 'required|image|max:10240',
            'weight_unit' => 'required|in:lbs,kg',
            'distance_unit' => 'required|in:km,miles',
            'body_stat_unit' => 'required|in:cm,inches'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $isEmailExist = User::where('email',$request->email)->first();
        if($isEmailExist){
            return response()->json([
                'status' => false,
                'message' => 'User already exist with this email',
            ]);
        }
        $member = new User();
        $member->name = $request->first_name;
        $member->email = $request->email;
        $member->password = Hash::make($request->password);
        $member->role = $request->role=='trainer'?3:4;
        $member->email_verified_at = Carbon::now();
        $member->email_verification_code = 0000;
        $member->api_token = "vsdc";
        $member->fcm_token = 'vfsdx';
        $member->save();
        $memberdet = new UserDetail();
        $memberdet->user_id = $member->id;
        $memberdet->name = $request->first_name;
        $memberdet->Lastname = $request->last_name;
        $memberdet->phone = $request->phone;
        $memberdet->country = $request->country;
        $memberdet->gender = $request->gender;
        $memberdet->dob = $request->dob;
        $filename = $member->id . "_profile_" . time() . '.' . request()->picture->getClientOriginalExtension();
        $request->picture->storeAs('avatar', $filename, config('filesystems.default'));
        $memberdet->picture = $filename;
        $memberdet->subscription = 0;
        $memberdet->save();
        $setting = new UserSetting();
        $setting->user_id = $member->id;
        $setting->weight_unit = $request->weight_unit;
        $setting->distance_unit = $request->distance_unit;
        $setting->body_measures = $request->body_stat_unit;
        $setting->save();
        return response()->json([
            'status' => true,
            'message' => 'Member Created.'
        ]);
    }

    function removeTeamMember($ids){
        $ids = json_decode($ids);
        if(gettype($ids)!=='array')
        return response()->json([
            'status' => false,
            'message' => 'Array of IDs is required.'
        ]);
        foreach ($ids as $id) {
            $user = User::where('id',$id)->whereIn('role',[3,4])->first();
            if(is_null($user))
            return response()->json([
                'status' => false,
                'message' => $id.' is not a team member.'
            ]);
        }

        $detIds = UserDetail::whereIn('user_id',$ids)->pluck('id')->toArray();
        $setIds = UserSetting::whereIn('user_id',$ids)->pluck('id')->toArray();

        UserDetail::destroy($detIds);
        UserSetting::destroy($setIds);
        User::destroy($ids);
        return response()->json([
            'status' => true,
            'message' => 'Team Member Removed.'
        ]);
    }

    function teamMemberDetail($id){
        $user = User::where('id',$id)->whereIn('role',[3,4])->with('userdetails')->with('userSettings')->first(['id','email','role','created_at']);
        if(is_null($user))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Member ID.'
        ]);
        $user->created_on = $user->created_at->format('D d M, Y');
        $user->assigned_clients = 0;
        $user->role = $user->role==3?'trainer':'manager';
        return response()->json([
            'status' => true,
            'data' => $user
        ]);
    }

    function teamMemberEdit(Request $request){
        $validate = Validator::make($request->all(),[
            'member_id' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'role' => 'required|in:trainer,manager',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'country' => 'required',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'weight_unit' => 'required|in:lbs,kg',
            'distance_unit' => 'required|in:km,miles',
            'body_stat_unit' => 'required|in:cm,inches',
            'picture' => 'image|max:10240',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $member = User::where('id',$request->member_id)->whereIn('role',[3,4])->first();
        if(is_null($member))
        return response()->json([
            'status' => false,
            'message' => 'Invalid ID'
        ]);
        $emailTest = User::where('email',$request->email)->first();
        if($emailTest && $emailTest->id!=$request->member_id){
            return response()->json([
                'status' => false,
                'message' => 'Email is Already Taken.'
            ]);
        }
        $member->name = $request->first_name;
        $member->email = $request->email;
        $member->role = $request->role=='trainer'?3:4;
        $member->update();
        $memberdet = UserDetail::where('user_id',$request->member_id)->first();
        $memberdet->name = $request->first_name;
        $memberdet->Lastname = $request->last_name;
        $memberdet->phone = $request->phone;
        $memberdet->country = $request->country;
        $memberdet->gender = $request->gender;
        if(isset($request->picture)){
            $filename = $request->member_id . "_profile_" . time() . '.' . request()->picture->getClientOriginalExtension();
            $request->picture->storeAs('avatar', $filename, config('filesystems.default'));
            $memberdet->picture = $filename;
        }
        $memberdet->update();
        $setting = UserSetting::where('user_id',$request->member_id)->first();
        $setting->weight_unit = $request->weight_unit;
        $setting->distance_unit = $request->distance_unit;
        $setting->body_measures = $request->body_stat_unit;
        $setting->update();
        return response()->json([
            'status' => true,
            'message' => 'Member Created.'
        ]);
    }
}
