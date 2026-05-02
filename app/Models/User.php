<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\UserDetail;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    function userdetails() {   
       return $this->hasOne(UserDetail::class,'user_id');
    }

    function userSettings() {   
        return $this->hasOne(UserSetting::class,'user_id');
    }

    function fullName(){
        $name = UserDetail::where('user_id',$this->id)->first(['name','Lastname']);
        if (!$name) {
            return $this->name ?? '';
        }
        return trim(($name->name ?? '') . ' ' . ($name->Lastname ?? ''));
    }

    function profilePicture(){
        return UserDetail::where('user_id',$this->id)->pluck('picture')->first();
    }

    function subs(){
        $subId = UserDetail::where('user_id',$this->id)->pluck('subscription')->first();
        return Subscription::where('id',$subId)->pluck('name')->first();
    }

    function subStatus(){
        return UserDetail::where('user_id',$this->id)->pluck('subscription_status')->first();
    }

    function language(){
        return UserSetting::where('user_id',$this->id)->pluck('language')->first();
    }

    // New relationships
    function consultationForm() {
        return $this->hasOne(ConsultationForm::class, 'user_id');
    }

    function tags() {
        return $this->belongsToMany(Tag::class, 'user_tags', 'user_id', 'tag_id');
    }

    function mealPhotos() {
        return $this->hasMany(MealPhoto::class, 'user_id');
    }

    function habitAssignments() {
        return $this->hasMany(UserHabitAssignment::class, 'user_id');
    }

    function habitCompletions() {
        return $this->hasMany(UserHabitCompletion::class, 'user_id');
    }

    function weightTrackings() {
        return $this->hasMany(ExerciseWeightTracking::class, 'user_id');
    }

    function achievements() {
        return $this->hasMany(UserAchievement::class, 'user_id');
    }

    function exerciseReplacements() {
        return $this->hasMany(UserExerciseReplacement::class, 'user_id');
    }

    function programHistory() {
        return $this->hasMany(ProgramHistory::class, 'user_id');
    }
}