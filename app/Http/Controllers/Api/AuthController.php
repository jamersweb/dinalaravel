<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Mail\emailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Models\UserSetting;
use App\Traits\NotificationsTrait;
use App\Traits\ApiResponse;
use App\Mail\ForgotPasswordAdmin;
use App\Mail\forgotPasswordUser;
use App\Mail\signupEmail;
use App\Models\AppUsageCount;
use App\Models\ProgramSub;
use App\Models\Subscription;
use App\Models\UserAnswer;
use App\Models\UserMealPlan;
use App\Models\UserSub;
use App\Traits\ActivitiesTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use stdClass;

class AuthController extends Controller
{
    use NotificationsTrait, ActivitiesTrait, ApiResponse;

    public function signup(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $language = strtolower(trim((string) $request->input('language', 'en')));
        if (str_contains($language, '-')) {
            $language = explode('-', $language)[0] ?: 'en';
        }
        if (str_contains($language, '_')) {
            $language = explode('_', $language)[0] ?: 'en';
        }
        $request->merge(['email' => $email, 'language' => $language]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'fcm_token' => 'required|string',
            'firstname' => 'required|string',
            'password' => 'required|string',
            'lastname' => 'required|string',
            'phone' => 'required|numeric',
            'DOB' => 'required|date',
            'country' => 'required|string',
            'language' => ['required', Language::activeCodeRule()],
            'gender' => 'required|in:male,female,other,ذكر,انثى,آخر'
        ]);
        if ($validate->fails())
            return $this->validationError($validate);

        $exists = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($exists && (int) $exists->role === 1 && is_null($exists->email_verified_at)) {
            $verificationCode = rand(1000, 9999);
            $exists->email_verification_code = $verificationCode;
            $exists->code_expire_time = Carbon::now()->addMinutes(5);
            $exists->fcm_token = $request->fcm_token;
            $exists->save();

            $data = [
                'name' => $exists->name,
                'verification_code' => $verificationCode,
                'email' => $exists->email,
            ];
            try {
                Mail::to($data['email'])->send(new signupEmail($data));
            } catch (\Throwable $e) {
                Log::error('signup verification resend failed', [
                    'email' => $data['email'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to send verification email right now. Please try again in a moment.'
                ], 500);
            }

            return response()->json([
                'status' => true,
                'message' => 'Account already exists but is not verified. We sent a new verification code.'
            ]);
        }

        if ($exists)
            return response()->json([
                'status' => false,
                'message' => (int) $exists->role === 1
                    ? 'Email already registered. Please sign in.'
                    : 'This email is already registered for another account type.'
                // 'message' => 'Email Is Already Taken.'
            ]);
        $user = new User();
        $user->api_token = 'xxxxxxxxxxxxx';
        $user->fcm_token = $request->fcm_token;
        $user->name = $request->firstname;
        $user->email = $email;
        $verificationCode = rand(1000, 9999);
        $user->email_verification_code = $verificationCode;
        $user->code_expire_time = Carbon::now()->addMinutes(5);
        $user->password = Hash::make($request->password);
        $user->role = 1;
        $user->status = "pending";
        $user->save();

        $userDetail = new UserDetail();
        $userDetail->user_id = $user->id;
        $userDetail->name = $request->firstname;
        $userDetail->Lastname = $request->lastname;
        $userDetail->phone = $request->phone;
        $userDetail->DOB = $request->DOB;
        $userDetail->country = $request->country;
        $userDetail->gender = $request->gender;
        $userDetail->subscription = 0;
        $userDetail->save();

        $settings = new UserSetting();
        $settings->user_id = $user->id;
        $settings->language = $request->language;
        $settings->stats_sequence = json_encode([
            ["icon" => "assets/images/SVG/steps_icon.svg", "item" => "steps"],
            ["icon" => "assets/images/SVG/sleep_icon.svg", "item" => "sleep"],
            ["icon" => "assets/images/SVG/body_weight_icon.svg", "item" => "body weight"],
            ["icon" => "assets/images/SVG/body_fat_icon.svg", "item" => "body fat"],
            ["icon" => "assets/images/SVG/caloric_intake_icon.svg", "item" => "caloric intake"],
            ["icon" => "assets/images/SVG/photo_icon.svg", "item" => "photos"],
            ["icon" => "assets/images/SVG/resting_hr_icon.svg", "item" => "resting hr"],
            ["icon" => "assets/images/SVG/blood_pressure_icon.svg", "item" => "blood pressure"],
            ["icon" => "assets/images/SVG/lean_body_icon.svg", "item" => "lean body mass"],
            ["icon" => "assets/images/SVG/caloric_burn_icon.svg", "item" => "caloric burn"]
        ]);
        $settings->save();

        $notiReciever = User::where('role', 2)->pluck('id')->first();
        $notiSource = $user->id;
        $notiTitle = 'New User Created!';
        $notiContent = $email . ' just created account.';
        if ($notiReciever !== null) {
            $this->storeNotification($notiReciever, $notiTitle, null, $notiContent, null, $notiSource);
        }
        $data = [
            'name' => $request->firstname,
            'verification_code' => $verificationCode,
            'email' => $email
        ];
        AutomatedMessagesController::sendAutoMessage($user->id, 'signup');
        try {
            Mail::to($data['email'])->send(new signupEmail($data));
        } catch (\Throwable $e) {
            Log::error('signup welcome email failed', [
                'email' => $data['email'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Account created, but we could not send the verification email right now. Please try again in a moment.'
            ], 500);
        }
        return response()->json([
            'status' => true,
            'message' => $request->language === 'en' ? config('responses.created_email_sent.en') : config('responses.created_email_sent.ar')
            // 'message' => 'Account Created and Verification Email Sent.'
        ]);
    }

    public function emailVerification(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|numeric'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $userToken = User::whereRaw('LOWER(email) = ?', [$email])->where('role', 1)->first(['email_verification_code', 'code_expire_time', 'id']);
        if ($userToken) {
            if ($userToken->email_verification_code == $request->token) {
                if (Carbon::now() > $userToken->code_expire_time)
                    return response()->json([
                        'status' => 0,
                        'message' => $this->userSelecetdLanguage($userToken->id) === 'en' ? config('responses.code_expired.en') : config('responses.code_expired.ar')
                        // 'message' => 'Code Has Been Expired, Please Request a New One.'
                    ]);
                $current_date_time = Carbon::now()->toDateTimeString();
                $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                $user->update(array('email_verified_at' => $current_date_time));
                $token = $user->createToken('MyApp');
                $accessToken = $token->accessToken;
                $token->token->expires_at = Carbon::now()->addWeeks(4);
                $id = $token->token->id;
                DB::table('oauth_access_tokens')->where('id', $id)->update(['expires_at' => $token->token->expires_at]);
                User::whereRaw('LOWER(email) = ?', [$email])->update(array('api_token' => $accessToken));
                User::whereRaw('LOWER(email) = ?', [$email])->update(array('email_verified_at' => $current_date_time));
                $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                return response()->json([
                    'status' => 1,
                    'message' => 'Email Verified and Logged In',
                    'userdata' => $user
                ]);
            } else
                return response()->json([
                    'status' => 0,
                    'message' => $this->userSelecetdLanguage($userToken->id) === 'en' ? config('responses.code_not_match.en') : config('responses.code_not_match.ar')
                    // 'message' => 'Verification Code Does Not Match',
                ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Email Does Not Exist',
            ]);
        }
    }

    public function login(Request $request)
    {
        $language = strtolower(trim((string) $request->input('language', 'en')));
        if (str_contains($language, '-')) {
            $language = explode('-', $language)[0] ?: 'en';
        }
        if (str_contains($language, '_')) {
            $language = explode('_', $language)[0] ?: 'en';
        }
        $request->merge(['language' => $language]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'fcm_token' => 'required',
            'password' => 'required',
            'language' => ['required', Language::activeCodeRule()],
        ]);
        if ($validate->fails())
            return response()->json([
                'status' => 0,
                'message' => $validate->errors()->all()[0]
            ]);
        $email = strtolower(trim($request->email));
        $password = $request->password; // Don't trim password for compatibility with existing hashes

        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('role', 1)->first();

        if (is_null($user)) {
            $existingAccount = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($existingAccount) {
                return response()->json([
                    'status' => 0,
                    'message' => 'This email is already registered, but not as a mobile user account.'
                ]);
            }
            return response()->json([
                'status' => 0,
                'message' => $request->language === 'en' ? config('responses.acount_not_exist.en') : config('responses.acount_not_exist.ar')
                // 'message' => 'Account Does Not Exist.'
            ]);
        }
        if ($user->status === 'deactive')
            return response()->json([
                'status' => 0,
                'message' => $request->language === 'en' ? config('responses.account_deactivate.en') : config('responses.account_deactivate.ar')
                // 'message' => 'Your Account has been Deactivated by Administrator'
            ]);
        if (Auth::attempt(['email' => $user->email, 'password' => $password])) {
            if ($user->email_verified_at == null) {
                $verificationCode = rand(1000, 9999);
                $user->email_verification_code = $verificationCode;
                $user->code_expire_time = Carbon::now()->addMinutes(5);
                $user->update();
                $data = [
                    'name' => $user->name,
                    'verification_code' => $verificationCode,
                    'email' => $email
                ];
                try {
                    Mail::to($email)->send(new emailVerification($data));
                } catch (\Throwable $e) {
                    Log::error('login verification email failed', [
                        'email' => $email,
                        'message' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'status' => 0,
                        'message' => 'Unable to send verification email right now. Please try again later.'
                    ], 500);
                }
                return response()->json([
                    'status' => 2,
                    'message' => $request->language === 'en' ? config('responses.account_verification_sent.en') : config('responses.account_verification_sent.ar')
                    // 'message' => 'Account Verification Email Has Been Sent.',
                ]);
            } else {
                $token = $user->createToken('MyApp');
                $accessToken = $token->accessToken;
                $token->token->expires_at = Carbon::now()->addWeeks(4);
                $id = $token->token->id;
                DB::table('oauth_access_tokens')->where('id', $id)->update(['expires_at' => $token->token->expires_at]);
                User::whereRaw('LOWER(email) = ?', [$email])->update(array('api_token' => $accessToken));
                User::whereRaw('LOWER(email) = ?', [$email])->update(array('fcm_token' => $request->fcm_token));
                Auth::user()->update(array('api_token' => $accessToken));

                $user = User::find(Auth::id());
                $user->fullname = $user->fullName();
                $user->subscription = $user->subs();
                $user->subscription_status = $user->subStatus();
                $question = UserAnswer::where('user_id', Auth::id())->first();
                if (is_null($question))
                    $qAns = false;
                else
                    $qAns = true;

                $settings = UserSetting::where('user_id', Auth::id())->first();
                if (!is_null($settings)) {
                    $settings->language = $request->language;
                    $settings->update();
                }

                $currentDevice = DB::table('oauth_access_tokens')
                    ->where('user_id', $user->id)->orderBy('created_at', 'DESC')->pluck('id')->first();
                $OtherDevices = DB::table('oauth_access_tokens')
                    ->where('user_id', $user->id)->where('id', '!=', $currentDevice)->get();
                foreach ($OtherDevices as $device) {
                    DB::table('oauth_access_tokens')->where('id', $device->id)->delete();
                }

                return response()->json([
                    'status' => 1,
                    'message' => 'Login Successfully',
                    'userdata' =>  $user,
                    'answered' => $qAns
                ]);
            }
        }
        return response()->json([
            'status' => 0,
            'message' => $request->language === 'en' ? config('responses.invalid_password.en') : config('responses.invalid_password.ar')
            // 'message' => 'Invalid Password',
        ]);
    }

    function resendEmail(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);
        $validate = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validate->fails())
            return $this->validationError($validate);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('role', 1)->first();
        if (is_null($user))
            return response()->json([
                'status' => false,
                'message' => 'Invalid Email.'
            ]);
        $verificationCode = rand(1000, 9999);
        $user->email_verification_code = $verificationCode;
        $user->code_expire_time = Carbon::now()->addMinutes(5);
        $user->update();
        $data = [
            'name' => $user->name,
            'verification_code' => $verificationCode,
            'email' => $email
        ];
        try {
            Mail::to($data['email'])->send(new emailVerification($data));
        } catch (\Throwable $e) {
            Log::error('resend verification email failed', [
                'email' => $data['email'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to resend verification email right now. Please try again later.'
            ], 500);
        }
        return response()->json([
            'status' => true,
            'message' => 'Verification Email Sent.',
        ]);
    }

    public function isAuth()
    {
        $data = new stdClass;
        $data->role = Auth::user()->role === 2 ? 'Admin' : (Auth::user()->role === 3 ? 'Trainer' : 'Manager');
        $data->image = UserDetail::where('user_id', Auth::id())->pluck('picture')->first();
        $data->id = Auth::id();
        $data->name = ucfirst(Auth::user()->name);
        return response()->json([
            'status' => true,
            'message' => 'Token is valid',
            'data' => $data
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $existingAccount = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existingAccount && (int) $existingAccount->role !== 1) {
            return response()->json([
                'status' => false,
                'message' => 'This email is registered for an admin or team account, not a mobile user account.'
            ]);
        }

        if ($existingAccount) {
            $verificationCode = rand(1000, 9999);
            $existingAccount->email_verification_code = $verificationCode;
            $existingAccount->code_expire_time = Carbon::now()->addMinutes(5);
            $existingAccount->update();
            $data = [
                'name' => $existingAccount->name,
                'verification_code' => $verificationCode,
                'email' => $existingAccount->email
            ];
            try {
                Mail::to($data['email'])->send(new forgotPasswordUser($data));
            } catch (\Throwable $e) {
                Log::error('forgot password email failed', [
                    'email' => $data['email'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to send reset email right now. Please try again later.'
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => $this->userSelecetdLanguage($existingAccount->id) === 'en' ? config('responses.email_sent.en') : config('responses.email_sent.ar')
                // 'message' => 'Email sent to provided email address'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Email Does Not Exist'
            ]);
        }
    }

    public function forgotPasswordAdmin(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $isEmailExist = User::whereRaw('LOWER(email) = ?', [$email])->where('role', '!=', 1)->first();
        if ($isEmailExist) {
            $verificationCode = rand(1000, 9999);
            $isEmailExist->email_verification_code = $verificationCode;
            $isEmailExist->code_expire_time = Carbon::now()->addMinutes(5);
            $isEmailExist->update();
            $data = [
                'name' => $isEmailExist->name,
                'token' => base64_encode($verificationCode . ',' . $isEmailExist->email),
                'email' => $isEmailExist->email
            ];
            try {
                Mail::to($data['email'])->send(new ForgotPasswordAdmin($data));
            } catch (\Throwable $e) {
                Log::error('admin forgot password email failed', [
                    'email' => $data['email'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unable to send reset email right now. Please try again later.'
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Confirmation email sent'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Email does not Exist'
            ]);
        }
    }


    public function updatePassword(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|numeric',
            'password' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('role', 1)->first();
        if ($user) {
            if ($user->email_verification_code == $request->token) {
                if (Carbon::now() > $user->code_expire_time)
                    return response()->json([
                        'status' => false,
                        'message' => $this->userSelecetdLanguage($user->id) === 'en' ? config('responses.code_expired.en') : config('responses.code_expired.ar')
                        // 'message' => 'Code Has Been Expired, Please Request a New One.'
                    ]);
                $user->password = Hash::make($request->password);
                $user->update();
                return response()->json([
                    'status' => true,
                    'message' => 'Password Updated',
                ]);
            }
            return response()->json([
                'status' => false,
                'message' => $this->userSelecetdLanguage($user->id) === 'en' ? config('responses.code_mismatched.en') : config('responses.code_mismatched.ar')
                // 'message' => 'Code Mismatched',
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Email does not exist',
        ]);
    }


    public function updatePasswordAdmin(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $requestData = base64_decode($request->token);
        $requestDataArray = explode(",", $requestData);
        $verificationCode = $requestDataArray[0];
        $verificationEmail = strtolower(trim((string) $requestDataArray[1]));
        $user = User::whereRaw('LOWER(email) = ?', [$verificationEmail])->first();
        if ($user) {
            if ($user->email_verification_code == $verificationCode) {
                if (Carbon::now() > $user->code_expire_time)
                    return response()->json([
                        'status' => false,
                        'message' => 'The Reset Link is Expired',
                    ]);
                $current_date_time = Carbon::now()->toDateTimeString();
                User::whereRaw('LOWER(email) = ?', [$verificationEmail])->update(array('email_verified_at' => $current_date_time));
                User::whereRaw('LOWER(email) = ?', [$verificationEmail])->update(array('password' => Hash::make($request->password)));
                return response()->json([
                    'status' => true,
                    'message' => 'Password update',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'The Reset Link is Tempered',
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'The Reset Link is Tempered',
            ]);
        }
    }
    public function verifyOTP(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $x = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$x) {
            return response()->json([
                'status' => false,
                'message' => 'Email Does Not Exist'
            ]);
        }
        if ($x->email_verification_code == $request->otp) {
            return response()->json([
                'status' => true,
                'message' => 'OTP verified'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'OTP Does Not Match'
            ]);
        }
    }
    public function logout()
    {
        Auth::user()->token()->revoke();
        Auth::user()->token()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Delete user account and all associated data.
     * Required by Google Play and Apple App Store guidelines.
     */
    public function deleteAccount(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $request->language === 'ar' ? 'كلمة المرور مطلوبة' : 'Password is required',
            ], 422);
        }

        $user = Auth::user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => $request->language === 'ar' ? 'كلمة المرور غير صحيحة' : 'Invalid password',
            ], 422);
        }

        $userId = $user->id;

        DB::beginTransaction();
        try {
            // Revoke and delete all OAuth tokens for this user (refresh tokens first due to FK)
            $tokenIds = DB::table('oauth_access_tokens')->where('user_id', $userId)->pluck('id');
            if (Schema::hasTable('oauth_refresh_tokens')) {
                DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $tokenIds)->delete();
            }
            DB::table('oauth_access_tokens')->where('user_id', $userId)->delete();

            // Delete from tables that don't have FK cascade
            if (Schema::hasTable('app_usage_counts')) {
                DB::table('app_usage_counts')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->where('reciever', $userId)->delete();
            }

            // Delete user (cascades to user_details, user_settings, user_answers, chats, etc.)
            User::where('id', $userId)->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $request->language === 'ar' ? 'تم حذف الحساب بنجاح' : 'Account deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $request->language === 'ar' ? 'حدث خطأ أثناء حذف الحساب' : 'An error occurred while deleting your account',
            ], 500);
        }
    }
    /**
     * Sync subscription from RevenueCat when app has isPro but backend has not received webhook.
     * Call when user has RevenueCat entitlement but subscribe-program returns 402.
     */
    public function syncRevenueCatSubscription()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $fullAccessSub = Subscription::where('access_type', 'full_access')
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $fullAccessSub) {
            return response()->json(['status' => false, 'message' => 'No subscription plan found'], 500);
        }

        UserSub::where('user_id', $userId)->where('status', 'active')->update(['status' => 'replaced']);

        $userSub = new UserSub;
        $userSub->user_id = $userId;
        $userSub->sub_id = $fullAccessSub->id;
        $userSub->payment_id = null;
        $userSub->status = 'active';
        $userSub->sub_start_date = now();
        $userSub->sub_expire_date = now()->addYear();
        $userSub->save();

        UserDetail::where('user_id', $userId)->update([
            'subscription' => $fullAccessSub->id,
            'subscription_status' => 'active',
        ]);

        return response()->json(['status' => true, 'message' => 'Subscription synced']);
    }

    function isTokenValid()
    {
        return response()->json([
            'status' => true,
            'message' => 'User Token is Valid'
        ]);
    }

    function adminLogin(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validate->fails())
            return $this->validationError($validate);

        $exists = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (is_null($exists))
            return response()->json([
                'status' => false,
                'message' => 'Email Does Not Exist.'
            ]);
        else if ($exists->role == 1)
            return response()->json([
                'status' => false,
                'message' => 'Invalid Email.'
            ]);
        else {
            if (!(Auth::attempt(['email' => $exists->email, 'password' => $request->password])))
                return response()->json([
                    'status' => false,
                    'message' => 'Incorrect Password.'
                ]);
            $user = Auth::user();
            $token = $user->createToken('MyApp');
            $accessToken = $token->accessToken;
            $token->token->expires_at = Carbon::now()->addWeeks(4);
            $id = $token->token->id;
            DB::table('oauth_access_tokens')->where('id', $id)->update(['expires_at' => $token->token->expires_at]);


            User::whereRaw('LOWER(email) = ?', [$email])->update(array('api_token' => $accessToken));
            Auth::user()->update(array('api_token' => $accessToken));
            // logout other devices
            // $currentDevice = DB::table('oauth_access_tokens')->where('user_id',$user->id)->orderBy('created_at','DESC')->pluck('id')->first();
            // DB::table('oauth_access_tokens')->where('user_id',$user->id)->where('id','!=',$currentDevice)->delete();
            return response()->json([
                'status' => true,
                'message' => 'Loggedin Successfully',
                'login_token' => $accessToken,
            ]);
        }
    }

    function appStartupChecks()
    {
        $userId = Auth::id();
        AppUsageCount::create(['user_id' => $userId]);

        $subStatus = UserDetail::where('user_id', $userId)->pluck('subscription_status')->first();
        $queAns = UserAnswer::where('user_id', $userId)->count();
        $proSubs = ProgramSub::where('user_id', $userId)->where('status', '!=', 'completed')->count();
        $subAccess = Subscription::where('id', UserSub::where('user_id', $userId)->where('status', 'active')->orderBy('id', 'desc')->pluck('sub_id')->first())->pluck('access_type')->first();
        $tutorial = UserSetting::where('user_id', $userId)->pluck('show_tutorial')->first();
        // Check for ACTIVE meal plans, not just any meal plans
        $mealPlan = UserMealPlan::where('user_id', $userId)->where('status', 'active')->count();

        \Log::info('app-start-checks', [
            'user_id' => $userId,
            'subscription_status_raw' => $subStatus,
            'user_answers_count' => $queAns,
            'program_subs_count' => $proSubs,
            'meal_plan_count' => $mealPlan,
        ]);

        $returnData = new stdClass;
        $returnData->subscription_active = $subStatus === 'active' ? true : false;
        $returnData->que_answered = $queAns > 0 ? true : false;
        $returnData->program_subscribed = $proSubs === 0 ? false : true;
        $returnData->meal_plan_selected = $mealPlan > 0 ? true : false;
        $returnData->full_access = $subAccess === 'full_access' ? true : false;
        $returnData->show_tutorial = $tutorial === 1 ? true : false;

        return response()->json([
            'status' => true,
            'data' => $returnData
        ]);
    }
}
