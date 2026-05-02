<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    function amountPaid(){
        return Payment::where('id',UserSub::where('id',$this->user_sub_id)->pluck('payment_id')->first())->pluck('amount')->first();
    }

    function userName(){
        $name = UserDetail::where('user_id',$this->user_id)->first(['name','Lastname']);
        return $name->name.' '.$name->Lastname;
    }

    function forSub(){
        return Subscription::where('id',UserSub::where('id',$this->user_sub_id)->pluck('sub_id')->first())->pluck('name')->first();
    }

    function daysPassed(){
        $startDate = Carbon::parse(UserSub::where('id',$this->user_sub_id)->pluck('sub_start_date')->first());
        if($this->status==='applied' || $this->status==='rejected')
        return $startDate->diffInDays(Carbon::today());
        else if($this->status==='expired')
        return '> 30';
        else if($this->status==='approved')
        return $startDate->diffInDays($this->created_at);
    }
}
