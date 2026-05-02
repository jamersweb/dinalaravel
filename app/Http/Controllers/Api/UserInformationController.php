<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PosturePicture;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\UserAnswer;
use App\Models\UserBodyStat;
use App\Models\UserDetail;
use App\Traits\ActivitiesTrait;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserInformationController extends Controller
{
    use ActivitiesTrait, ApiResponse;

    public function GetUserData()
    {
        $id = Auth::user()->id;
        $GetUserData = User::select('id', 'name', 'email', 'role')
            ->where('id', $id)
            ->with('userdetails', function ($q) {
                $q->select('user_id', 'picture', 'Lastname', 'phone', 'DOB', 'country', 'gender', 'subscription', 'height');
            })
            ->with('tags')
            ->first();

        if ($GetUserData) {
            // Get tags from user_tags relationship (new system)
            // Tags are automatically generated from consultation form answers
            $userTags = $GetUserData->tags;
            if ($userTags && $userTags->count() > 0) {
                $GetUserData->tags = $userTags->pluck('name')->toArray();
            } else {
                // Fallback to old tags column
                $oldTags = $GetUserData->getOriginal('tags');
                if (is_null($oldTags) || empty($oldTags)) {
                    $GetUserData->tags = [];
                } else {
                    $tagIds = json_decode($oldTags);
                    $GetUserData->tags = Tag::whereIn('id', $tagIds)->pluck('name')->toArray();
                }
            }
        }
        
        return $this->success($GetUserData, 'User data retrieved successfully');
    }

    public function uploaduserprofilepicture(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'image' => 'required|image|max:1024'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }

        $id = Auth::user()->id;
        $name = Auth::user()->name;

        $filename = $id . "_" . "_profile_" . time() . '_' . uniqid() . '.' . request()->image->getClientOriginalExtension();
        $request->image->storeAs('avatar', $filename, 'fwd_media');
        UserDetail::where('user_id', $id)->update(array('picture' => $filename));

        return response()->json([
            'status' => true,
            'message' => 'Profile Picture Uploaded successfully'
        ]);
    }

    public function updateUserProfilePicture(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'image' => 'required|image|max:1024'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $id = Auth::user()->id;
        $name = Auth::user()->name;

        $filename = $id . "_" . "_profile_" . time() . '.' . request()->image->getClientOriginalExtension();
        $request->image->storeAs('avatar', $filename, config('filesystems.default'));

        UserDetail::where('user_id', $id)->update(array('picture' => $filename));
        return response()->json([
            'status' => true,
            'message' => 'Profile Picture Updated Successfully'
        ]);
    }

    public function posturePicture(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'front_picture' => 'required|image|max:10240',
            'back_picture' => 'required|image|max:10240',
            'side_picture' => 'required|image|max:10240',
            'privacy_setting' => 'nullable|in:confidential,approved_for_social',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $id = Auth::user()->id;
        $name = Auth::user()->name;
        $next_upload_date = PosturePicture::where('user_id', $id)->orderBy('created_at', 'DESC')->pluck('next_upload_date')->first();
        if (!empty($next_upload_date)) {
            $uploadDate = $next_upload_date;
            $currentdate = Carbon::now();
            if ($uploadDate > $currentdate) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot upload images before ' . date( "d M y", strtotime($uploadDate))
                ]);
            }
        }
        $frontImage = $id  . "_front_posture_" . time() . '_' . uniqid() . '.' . $request->front_picture->getClientOriginalExtension();
        $request->front_picture->storeAs('posture', $frontImage, 'fwd_media');
        $backImage = $id  . "_back_posture_" . time() . '_' . uniqid() . '.' . $request->back_picture->getClientOriginalExtension();
        $request->back_picture->storeAs('posture', $backImage, 'fwd_media');
        $sideImage = $id  . "_side_posture_" . time() . '_' . uniqid() . '.' . $request->side_picture->getClientOriginalExtension();
        $request->side_picture->storeAs('posture', $sideImage, 'fwd_media');

        $posture = new PosturePicture();
        $posture->user_id = Auth::id();
        $posture->front_picture = $frontImage;
        $posture->back_picture = $backImage;
        $posture->side_picture = $sideImage;
        $posture->privacy_setting = $request->privacy_setting ?? 'confidential';
        $posture->next_upload_date = Carbon::today()->addWeeks(3);
        $posture->save();
        $review = PosturePicture::where('user_id',Auth::id())->count()===1?false:true;
        $this->generateActivityForAdmin('Posture Uploaded.','Just uploaded new posture pictures',6,Auth::id(),'photos',$posture->id);
        return response()->json([
            'status' => true,
            'message' => 'Posture Picture Updated',
            'refernce' => $posture->id,
            'review' => $review
        ]);
    }

    public function getPostureImages()
    {
        $id = Auth::user()->id;
        $allPostureImages = PosturePicture::where('user_id', $id)->get();
        foreach($allPostureImages as $postureImage){
            $postureImage->next_upload_date = date( "d M y", strtotime($postureImage->next_upload_date));
        }
        if ($allPostureImages->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Images Exist'
            ]);
        } else {
            return response()->json([
                'status' => true,
                'allPostureImages' => $allPostureImages
            ]);
        }
    }

    function storeBodyStat(Request $request){
        $validate = Validator::make($request->all(),[
            'weight' => 'required|numeric',
            'weight_unit' => 'required|in:kg,lbs',
            'fat' => 'required|numeric'
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $newStat = new UserBodyStat();
        $newStat->user_id = Auth::id();
        $newStat->weight = $request->weight;
        $newStat->weight_unit = $request->weight_unit;
        $newStat->fat = $request->fat;
        $newStat->save();
        return response()->json([
            'status' => true,
            'message' => 'Body Stat Update Added.'
        ]);
    }

    function getBodyStat(){
        $stats = UserBodyStat::where('user_id',Auth::id())->orderBy('created_at','DESC')->first();
        return response()->json([
            'status' => true,
            'data' => $stats
        ]);
    }

    public function getUserInformation(Request $request){
        $id = Auth::user()->id;
        $userDetail = UserDetail::where('user_id',$id)->first();
        $data['email'] = Auth::user()->email;
        $data['phone'] = $userDetail['phone'];
        $data['DOB'] = $userDetail['DOB'];
        $data['country'] = $userDetail['country'];
        $data['gender'] = $userDetail['gender'];
        $data['height'] = $userDetail['height'];

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function updateUserInformation(Request $request){
        $validate = Validator::make($request->all(),[
            'phone' => 'required',
            'DOB' => 'required',
            'country' => 'required',
            'gender' => 'required',
            'height' => 'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }

        $data['phone'] = $request->phone;
        $data['DOB'] = $request->DOB;
        $data['country'] = $request->country;
        $data['gender'] = $request->gender;
        $data['height'] = $request->height;

        $id = Auth::user()->id;

        UserDetail::where('user_id',$id)->update($data);
        return response()->json([
            'status' => true,
            'message' => 'User Information Successfully Updated'
        ]);
    }

    public function checkQuestions(){
        $answer = UserAnswer::where('user_id',Auth::id())->first();
        if($answer){
            return response()->json([
                'status' => true,
                'message' => 'Question Answered'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Question Not Answered'
            ]);
        }
    }

    function postureUploadDate(){
        $postureUploadDate = PosturePicture::where('user_id',Auth::id())->orderBy('created_at','desc')
        ->pluck('next_upload_date')->first();
        if($postureUploadDate == null || $postureUploadDate == "")
        return response()->json([
            'can_upload' => true
        ]);
        if($postureUploadDate < Carbon::now())
        return response()->json([
            'can_upload' => true
        ]);
        return response()->json([
            'can_upload' => false
        ]);
    }

    function myProfile(){
        $user = User::where('id',Auth::id())->with('userdetails')->first();
        $user->member_since = $user->created_at->format('D d M, Y');
        $user->role = $user->role==2?'Admin':'Trainer';
        unset($user->api_token);
        unset($user->fcm_token);
        unset($user->stripe_customer);
        unset($user->email_verification_code);
        unset($user->userdetails->subscription);
        unset($user->userdetails->subscription_status);
        $user->clients = User::where('role',1)->count();
        if(Auth::user()->role==2)
        $user->trainers = User::where('role',3)->count();
        $user->fullname = $user->userdetails->name.' '.$user->userdetails->Lastname;
        $user->country = $user->userdetails->country;
        $user->phone = $user->userdetails->phone;
        $user->gender = $user->userdetails->gender;
        $user->dob = $user->userdetails->DOB;
        $user->picture = $user->userdetails->picture;
        unset($user->userdetails);

        return response()->json([
            'status' => true,
            'data' => $user
        ]);
    }

    function updateProfile(Request $request){
        $validate = Validator::make($request->all(),[
            'first_name' => 'string',
            'last_name' => 'string',
            'country' => 'string',
            'password' => 'string',
            'email' => 'email',
            'image' => 'mimes:jpg,png,jpeg|nullable|max:5120'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $user = User::find(Auth::id());
        $det = UserDetail::where('user_id',Auth::id())->first();
        if(isset($request->first_name)){
            $user->name = $request->first_name;
            $det->name = $request->first_name;
            $det->Lastname = $request->last_name;
        }
        if(isset($request->email))
        $user->email = $request->email;
        if(isset($request->password))
        $user->password = Hash::make($request->password);
        if(isset($request->country))
        $det->country = $request->country;

        if(isset($request->image) && $request->image!==null){
            $filename = $user->id . "_" . "_profile_" . time() . '.' . request()->image->getClientOriginalExtension();
            $request->image->storeAs('avatar', $filename, config('filesystems.default'));
            $det->picture = $filename;
        }

        $user->update();
        $det->update();
        return response()->json([
            'status' => true,
            'message' => 'Profile Updated.'
        ]);
    }

    function submitReview(Request $request){
        $validate = Validator::make($request->all(),[
            'meal' => 'required|integer|between:0,5',
            'habit' => 'required|integer|between:0,5',
            'workout' => 'required|integer|between:0,5',
            'comments' => 'required|string',
            'reference' => 'required'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $rating['meal'] = $request->meal;
        $rating['habit'] = $request->habit;
        $rating['workout'] = $request->workout;

        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->content = $request->comments;
        $comment->rating = json_encode($rating);
        $comment->type = 'photos';
        $comment->target_id = $request->reference;
        $comment->save();
        return response()->json([
            'status' => true,
            'message' => 'Review Submitted.'
        ]);
    }

    public function myReviews($id){
        $reviews = Comment::where('target_id',$id)->where('type','photos')->get();
        foreach($reviews as $review){
            $review->name = $review->fullName();
            if(!is_null($review->rating)){
                $review->rating = json_decode($review->rating);
            }
        }
        return response()->json([
            'status' => true,
            'data' => $reviews
        ]);
    }
}
