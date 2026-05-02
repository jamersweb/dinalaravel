<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DCodeUsage;
use App\Models\DiscountCode;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSub;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Illuminate\Support\Facades\DB;
use stdClass;

class PaymentsController extends Controller
{
    //
    use ActivitiesTrait;
    use NotificationsTrait;

    function createStripeCustomer($id){
        try{
            $secret_key = config('app.stripe_secret_key');
            \Stripe\Stripe::setApiKey($secret_key);
            $customer = \Stripe\Customer::create([
                'email' => Auth::user()->email,
                'name' => Auth::user()->name
            ]);

            $user = User::find($id);
            $user->stripe_customer = $customer->id;
            $user->update();
            return [
                'status' => true,
                'customer_id' => $customer->id
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()." Line #".$e->getLine()
            ];
        }
    }

    function applyDiscountCode($code,$product,$user,$userId){
        $userLanguage = $this->userSelecetdLanguage($userId);
        $discount = DiscountCode::where('code',$code)->where('status',1)->first();
        if(is_null($discount)){             //check code exists
            $returnData['status'] = false;
            $returnData['msg'] = $userLanguage==='en'?config('responses.invalid_discount_code.en'):config('responses.invalid_discount_code.ar');
            return $returnData;
        }
        if(is_null($discount->valid_products)){     // check products null
            $returnData['status'] = false;
            $returnData['msg'] = $userLanguage==='en'?config('responses.invalid_product_dis_code.en'):config('responses.invalid_product_dis_code.ar');
            return $returnData;
        }
        $forProducts = json_decode($discount->valid_products);
        if(!in_array($product,$forProducts)){       // check applicable products
            $returnData['status'] = false;
            $returnData['msg'] = $userLanguage==='en'?config('responses.invalid_product_dis_code.en'):config('responses.invalid_product_dis_code.ar');
            return $returnData;
        }
        if($discount->valid_till < Carbon::today()){
            $returnData['status'] = false;
            $returnData['msg'] = $userLanguage==='en'?config('responses.invalid_product_dis_code.en'):config('responses.invalid_product_dis_code.ar');
            return $returnData;
        }
        $discountFor = DCodeUsage::where('code_id',$discount->id)->where('user_email',$user)->orWhere('user_id',$userId)->first();
        if($discount->availability === 'specific' && is_null($discountFor)){ // check eligible user
            $returnData['status'] = false;
            $returnData['msg'] = $userLanguage==='en'?config('responses.elig_dis_code.en'):config('responses.elig_dis_code.ar');
            return $returnData;
        }
        if($discountFor->status===1){       // check code is active
            $returnData['status'] = false;
            $returnData['msg'] = $userLanguage==='en'?config('responses.used_dis_code.en'):config('responses.used_dis_code.ar');
            return $returnData;
        }   //all checks passed, discounting amount
        $productAmount = Subscription::where('id',$product)->pluck('price')->first()*100;
        if($discount->type==='percentage')  // by percentage ater converting in cents
        $discountedAmount = $productAmount-($productAmount*($discount->off_by/100));
        else                                // by amount ater converting in cents
        $discountedAmount = $productAmount - ($discount->off_by)*100;

        $returnData['status'] = true;
        $returnData['finalAmount'] = $discountedAmount;
        return $returnData;
        
    }
    
    function paymentFromSavedCard(Request $request){
        $validate = Validator::make($request->all(),[
            'subscription' => 'required|exists:subscriptions,id',
            'card_id' => 'required|string'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $userLanguage = $this->userSelecetdLanguage(Auth::id());
        // check if subscription already active
        $checkSub = UserSub::where('user_id',Auth::id())->orderBy('sub_expire_date','desc')->first(['status','sub_expire_date']);
        if($checkSub && $checkSub->status==='active')
        return response()->json([
            'status' => false,
            'message' => $userLanguage==='en'?config('responses.already_sub.en'):config('responses.already_sub.ar')
        ]);

        $discountCodeUsed = false;
        if(isset($request->discount_code) && $request->discount_code!==null){   // get discounted amount if code applicable
            $discountApplicable = $this->applyDiscountCode($request->discount_code,$request->subscription,Auth::user()->email,Auth::id());
            if($discountApplicable['status']===false)
            return response()->json([
                'status' => false,
                'message' => $discountApplicable['msg']
            ]);
            $amount = $discountApplicable['finalAmount'];
            $discountCodeUsed = true;
        } else {
            $amount = Subscription::where('id',$request->subscription)->pluck('price')->first()*100;
        }
        if($amount<100){    // no stripe payment if amount less than 1 dollar
            $newSub = new UserSub();
            $newSub->sub_id = $request->subscription;
            $newSub->user_id  = Auth::id();
            if($discountCodeUsed){  // if discount code is used then change its status
                $codeId = DiscountCode::where('code',$request->discount_code)->pluck('id')->first();
                $newSub->discount_code = $codeId;
                $newSub->discount_code_status ='applied';
                if(DCodeUsage::where('code_id',$codeId)->where('user_email',Auth::user()->email)->update(['status' => 1,'user_id' => Auth::id()])===0){
                    // if user record is not already there because of a general discount code
                    $newDCUser = new DCodeUsage();
                    $newDCUser->code_id = $codeId;
                    $newDCUser->user_email = Auth::user()->email;
                    $newDCUser->user_id = Auth::id();
                    $newDCUser->status = 1;
                    $newDCUser->save();
                }
            }
            $newSub->sub_id = $request->subscription;
            $newSub->status = 'active';
            $newSub->sub_start_date = Carbon::today();
            $newSub->sub_expire_date = Carbon::today()->addDays(30);
            $newSub->save();
            $detailData = UserDetail::where('user_id',Auth::id())->first();
            $detailData->subscription = $newSub->sub_id;
            $detailData->subscription_status = 'active';
            $detailData->update();

            $notiReciever = User::where('role',2)->pluck('id')->first();
            $notiSource = Auth::id();
            $notiTitle = 'Subscription Purchased!';
            $notiContent = Auth::user()->name.' just subscribed for $0 (using discount code)';
            $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);

            return response()->json([
                'status' => true,
                'message' => $userLanguage==='en'?config('responses.sub_activated.en'):config('responses.sub_activated.ar')
            ]);
        }
        try{    // get payment using stripe from the user stored card
            $customer = Auth::user()->stripe_customer;
            $cardToUse = $request->card_id;
            $secret_key = config('app.stripe_secret_key');
            \Stripe\Stripe::setApiKey($secret_key);
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'customer' => $customer,
                'payment_method' => $cardToUse,
                'off_session' => true,
                'confirm' => true,
                'payment_method_types' => ['card']
            ]);
        } catch (Exception $er){
            return response()->json([
                'status' => false,
                'error' => $er->getMessage(),
                'message' => $userLanguage==='en'?config('responses.pay_failed.en'):config('responses.pay_failed.ar')
            ]);
        }
        if($intent->status==='succeeded'){      // if payment succeeded
            $newPayment = new Payment();
            $newPayment->amount = round($amount/100,2);
            $newPayment->intent = $intent->id;
            $newPayment->card_used = $intent->charges->data[0]->payment_method_details->card->brand.' '.$intent->charges->data[0]->payment_method_details->card->last4;
            $newPayment->stripe_reponse = json_encode($intent);
            $newPayment->status = "success";
            $newPayment->save();    //store payment info

            $newSub = new UserSub();
            $newSub->sub_id = $request->subscription;
            $newSub->user_id = Auth::id();
            $newSub->payment_id = $newPayment->id;
            if($discountCodeUsed){      // update discount status to used
                $codeId = DiscountCode::where('code',$request->discount_code)->pluck('id')->first();
                $newSub->discount_code = $codeId;
                $newSub->discount_code_status ='applied';
                if(DCodeUsage::where('code_id',$codeId)->where('user_email',Auth::user()->email)->update(['status' => 1,'user_id' => Auth::id()])===0){
                    // if user record is not already there because of a general discount code
                    $newDCUser = new DCodeUsage();
                    $newDCUser->code_id = $codeId;
                    $newDCUser->user_email = Auth::user()->email;
                    $newDCUser->user_id = Auth::id();
                    $newDCUser->status = 1;
                    $newDCUser->save();
                }
            }
            $newSub->status = 'active';
            $newSub->sub_start_date = Carbon::today();
            $newSub->sub_expire_date = Carbon::today()->addDays(30);
            $newSub->save();
            $detailData = UserDetail::where('user_id',Auth::id())->first();
            $detailData->subscription = $newSub->sub_id;
            $detailData->subscription_status = 'active';
            $detailData->update();

            $notiReciever = User::where('role',2)->pluck('id')->first();
            $notiSource = Auth::id();
            $notiTitle = 'Subscription Purchased!';
            $notiContent = Auth::user()->name.' just subscribed for $'.$newPayment->amount;
            $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);

            return response()->json([
                'status' => true,
                'message' => $userLanguage==='en'?config('responses.sub_activated.en'):config('responses.sub_activated.ar')
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $userLanguage==='en'?config('responses.pay_failed.en'):config('responses.pay_failed.ar')
            ]);
        }
    }

    function checkDisountedAmount(Request $request){
        $validate = Validator::make($request->all(),[
            'subscription' => 'required|exists:subscriptions,id',
            'discount_code' => 'required|string'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $discountApplicable = $this->applyDiscountCode($request->discount_code,$request->subscription,Auth::user()->email,Auth::id());
        if($discountApplicable['status']===false)
        return response()->json([
            'status' => false,
            'message' => $discountApplicable['msg']
        ]);
        $amount = $discountApplicable['finalAmount'];
        return response()->json([
            'status' => true,
            'discounted_amount' => '$'.($amount<100?'0':round($amount/100,2))
        ]);
    }

    function getUserCards(){
        $stripeCustomer = User::where('id',Auth::id())->pluck('stripe_customer')->first();
        if(empty($stripeCustomer))
        return response()->json([
            'status' => true,
            'data' => []
        ]);
        $secret_key = config('app.stripe_secret_key');
        $stripe = new \Stripe\StripeClient($secret_key);
        $cards = $stripe->paymentMethods->all(['customer' => $stripeCustomer, 'type' => 'card']);
        $returnData = [];
        foreach ($cards->data as $card) {
            $temp = new stdClass;
            $temp->brand = $card->card->brand;
            $temp->last4 = $card->card->last4;
            $temp->expires = $card->card->exp_month.'/'.$card->card->exp_year;
            $temp->stripeId = $card->id;
            array_push($returnData,$temp);
        }
        return response()->json([
            'status' => true,
            'data' => $returnData
        ]);
    }

    function startPayment(Request $request){
        $cutomer_id = '';
        $validate = Validator::make($request->all(),[
            'subscription' => 'required|exists:subscriptions,id'
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        // check if subscription already active
        $userLanguage = $this->userSelecetdLanguage(Auth::id());
        $checkSub = UserSub::where('user_id',Auth::id())->orderBy('id','desc')->first(['status','sub_expire_date']);
        if(!is_null($checkSub) && $checkSub->status==='active')
        return response()->json([
            'status' => false,
            'message' => $userLanguage==='en'?config('responses.already_sub.en'):config('responses.already_sub.ar'). Carbon::parse($checkSub->sub_expire_date)->format('d M Y')
        ]);
        $discountCodeUsed = false;
        if(Auth::user()->stripe_customer == null){
            $res = $this->createStripeCustomer(Auth::id()); // create stripe customer if not already
            if($res['status'] == false){
                return response()->json([
                    'status' => false,
                    'message' => $res['message']
                ]);
            } else {
                $cutomer_id = $res['customer_id'];
            }
        } else {
            $cutomer_id = Auth::user()->stripe_customer;
        }
        if(isset($request->discount_code) && $request->discount_code!==null){
            $discountApplicable = $this->applyDiscountCode($request->discount_code,$request->subscription,Auth::user()->email,Auth::id());
            if($discountApplicable['status']===false)
            return response()->json([
                'status' => false,
                'message' => $discountApplicable['msg']
            ]);
            $amount = $discountApplicable['finalAmount'];
            $discountCodeUsed = true;
        } else {
            $amount = (Subscription::where('id',$request->subscription)->pluck('price')->first())*100;
        }
        if($amount<100){
            $newSub = new UserSub();
            $newSub->sub_id = $request->subscription;
            $newSub->user_id  = Auth::id();
            if($discountCodeUsed){
                $codeId = DiscountCode::where('code',$request->discount_code)->pluck('id')->first();
                $newSub->discount_code = $codeId;
                $newSub->discount_code_status ='applied';
                if(DCodeUsage::where('code_id',$codeId)->where('user_email',Auth::user()->email)->update(['status' => 1,'user_id' => Auth::id()])===0){
                    // if user record is not already there because of a general discount code
                    $newDCUser = new DCodeUsage();
                    $newDCUser->code_id = $codeId;
                    $newDCUser->user_email = Auth::user()->email;
                    $newDCUser->user_id = Auth::id();
                    $newDCUser->status = 1;
                    $newDCUser->save();
                }
            }
            $newSub->sub_id = $request->subscription;
            $newSub->status = 'active';
            $newSub->sub_start_date = Carbon::today();
            $newSub->sub_expire_date = Carbon::today()->addDays(30);
            $newSub->save();

            if(Auth::user()->status==='pending')    // first payment
            User::where('id',Auth::id())->update(['status' => 'active']);

            $detailData = UserDetail::where('user_id',Auth::id())->first();
            $detailData->subscription = $newSub->sub_id;
            $detailData->subscription_status = 'active';
            $detailData->update();

            $notiReciever = User::where('role',2)->pluck('id')->first();
            $notiSource = Auth::id();
            $notiTitle = 'Subscription Purchased!';
            $notiContent = Auth::user()->name.' just subscribed for $0 (using discount code)';
            $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);

            return response()->json([
                'status' => true,
                'message' => $userLanguage==='en'?config('responses.sub_activated.en'):config('responses.sub_activated.ar'),
                'stripe_payment' => false
            ]);
        }
        try {
            $secret_key = config('app.stripe_secret_key');
            \Stripe\Stripe::setApiKey($secret_key);
            $paymentIntent = \Stripe\PaymentIntent::create([
                'customer' => $cutomer_id,
                'setup_future_usage' => 'off_session',
                'amount' => $amount,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
            ]);

            $newPayment = new Payment();
            $newPayment->amount = round($amount/100,2);
            $newPayment->intent = $paymentIntent->id;
            $newPayment->stripe_reponse = json_encode($paymentIntent);
            $newPayment->status = "pending";
            $newPayment->save();

            $newSub = new UserSub();
            $newSub->sub_id = $request->subscription;
            $newSub->user_id = Auth::id();
            $newSub->payment_id = $newPayment->id;
            if($discountCodeUsed){
                $codeId = DiscountCode::where('code',$request->discount_code)->pluck('id')->first();
                $newSub->discount_code = $codeId;
                $newSub->discount_code_status ='not_applied';
            }
            $newSub->status = 'awaiting_payment';
            $newSub->save();

            return response()->json([
                'status' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'stripe_payment' => true,
                'payment_id' => $newPayment->id
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()." Line #".$e->getLine()
            ]);
        }
    }

    function getLatestPaymentStatus(Request $request){
        $validate = Validator::make($request->all(),[
            'payment_id' => 'required|exists:payments,id'
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->all()[0]
            ]);
        }
        $payment = Payment::find($request->payment_id);
        if($payment->status!=='pending'){
            return response()->json([
                'status' => false,
                'message' => 'Invalid Payment Id'
            ]);
        }
        $userLanguage = $this->userSelecetdLanguage(Auth::id());
        $secret_key = config('app.stripe_secret_key');
        \Stripe\Stripe::setApiKey($secret_key);

        $paymentIntent = \Stripe\PaymentIntent::retrieve([
            'id' => $payment->intent
        ]);

        if($paymentIntent->status == "requires_payment_method"){
            $payment->stripe_reponse = json_encode($paymentIntent);
            $payment->update();
            return response()->json([
                'status' => false,
                'message' => 'Payment Not Completed Yet!'
            ]);
        } else if($paymentIntent->status == "succeeded") {
            $payment->stripe_reponse = json_encode($paymentIntent);
            $payment->status = 'succeeded';
            $payment->card_used = $paymentIntent->charges->data[0]->payment_method_details->card->brand.' '.$paymentIntent->charges->data[0]->payment_method_details->card->last4;
            $payment->update();
            $userSub = UserSub::where('payment_id',$payment->id)->first();
            $userSub->status = 'active';
            $userSub->sub_start_date = Carbon::today();
            $userSub->sub_expire_date = Carbon::today()->addDays(30);
            if(is_null(!$userSub->discount_code)){
                $userSub->discount_code_status = 'applied';
                if(DCodeUsage::where('code_id',$userSub->discount_code)->where('user_email',Auth::user()->email)->update(['status' => 1,'user_id' => Auth::id()])===0){
                    // if user record is not already there because of a general discount code
                    $newDCUser = new DCodeUsage();
                    $newDCUser->code_id = $userSub->discount_code;
                    $newDCUser->user_email = Auth::user()->email;
                    $newDCUser->user_id = Auth::id();
                    $newDCUser->status = 1;
                    $newDCUser->save();
                }
            }
            $userSub->update();
            $detailData = UserDetail::where('user_id',Auth::id())->first();
            $detailData->subscription = $userSub->sub_id;
            $detailData->subscription_status = 'active';
            $detailData->update();
            
            if(Auth::user()->status==='pending')    // first payment
            User::where('id',Auth::id())->update(['status' => 'active']);

            $notiReciever = User::where('role',2)->pluck('id')->first();
            $notiSource = Auth::id();
            $notiTitle = 'Subscription Purchased!';
            $notiContent = Auth::user()->name.' just subscribed for $'.$payment->amount;
            $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
            return response()->json([
                'status' => true,
                'message' => $userLanguage==='en'?config('responses.pay_sub_success.en'):config('responses.pay_sub_success.ar')
            ]);
        } else {
            $payment->stripe_reponse = json_encode($paymentIntent);
            $payment->status = 'failed';
            $payment->update();
            return response()->json([
                'status' => false,
                'message' => $userLanguage==='en'?config('responses.pay_sub_fail.en'):config('responses.pay_sub_fail.ar')
            ]);
        }
    }

    function renewPreviousSub(){
        $userId = Auth::id();
        $userLanguage = $this->userSelecetdLanguage($userId);

        $lastSub = UserSub::where('user_id',$userId)->orderBy('id','desc')->first();
        if(is_null($lastSub) || $lastSub->status === 'active' || $lastSub->status === 'awaiting_payment')
        return response()->json([
            'status' => false,
            'message' => $userLanguage==='en'?config('responses.last_payment_false.en'):config('responses.last_payment_false.ar')
        ]);
        if($lastSub->status === 'expired' || $lastSub->status === 'refunded') {
            $customer = Auth::user()->stripe_customer;
            if(empty($customer))
            return response()->json([
                'status' => false,
                'message' => $userLanguage==='en'?'No saved payment method. Please subscribe via the app.':'لا توجد طريقة دفع محفوظة. يرجى الاشتراك عبر التطبيق.'
            ]);
            try {
                $secret_key = config('app.stripe_secret_key');
                $subDetail = Subscription::where('id',$lastSub->sub_id)->where('status',1)->first(['price','name']);
                $amount = round($subDetail->price*100);

                $stripe = new \Stripe\StripeClient($secret_key);
                $cards = $stripe->paymentMethods->all(['customer' => $customer, 'type' => 'card']);
                $lastCardId = $cards->data[0]->id;  // payment method id of card

                \Stripe\Stripe::setApiKey($secret_key);
                $intent = \Stripe\PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => 'usd',
                    'customer' => $customer,
                    'payment_method' => $lastCardId,
                    'off_session' => true,
                    'confirm' => true,
                    'payment_method_types' => ['card']
                ]);
                if($intent->status === 'succeeded'){
                    $newPayment = new Payment();
                    $newPayment->amount = $subDetail->price;
                    $newPayment->intent = $intent->id;
                    $newPayment->card_used = $intent->charges->data[0]->payment_method_details->card->brand.' '.$intent->charges->data[0]->payment_method_details->card->last4;
                    $newPayment->stripe_reponse = json_encode($intent);
                    $newPayment->status = "success";
                    $newPayment->save();

                    $newSub = new UserSub();
                    $newSub->sub_id = $lastSub->sub_id;
                    $newSub->user_id = $userId;
                    $newSub->payment_id = $newPayment->id;
                    $newSub->status = 'active';
                    $newSub->sub_start_date = Carbon::today();
                    $newSub->sub_expire_date = Carbon::today()->addDays(30);
                    $newSub->save();

                    $detailData = UserDetail::where('user_id',$userId)->first();
                    $detailData->subscription = $newSub->sub_id;
                    $detailData->subscription_status = 'active';
                    $detailData->update();

                    $notiReciever = User::where('role',2)->pluck('id')->first();
                    $notiTitle = 'Subscription Purchased!';
                    $notiContent = Auth::user()->name.' just subscribed for $'.$newPayment->amount;
                    $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$userId);

                    if($userLanguage==='en')
                    $respMsg = 'You Subscribed '.ucfirst($subDetail->name).' for $'.$subDetail->price.' Successfully From Card '.$newPayment->card_used;
                    else
                    $respMsg = 'لقد اشتركت في '.ucfirst($subDetail->name).' بمبلغ '.$subDetail->price.' دولار بنجاح من البطاقة '.$newPayment->card_used;

                    return response()->json([
                        'status' => true,
                        'message' => $respMsg
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => $userLanguage==='en'?config('responses.pay_wrong.en'):config('responses.pay_wrong.ar')
                    ]);
                }
            } catch(Exception $er){
                return response()->json([
                    'status' => false,
                    'message' => 'Something Went Wrong',
                    'error' => $er->getMessage()
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Something Went Wrong'
            ]);
        }
    }

    function upgradeSubCheck(){
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $currentSub = UserSub::where('user_id',Auth::id())->where('status','active')->orderBy('id','desc')->first();
        if(is_null($currentSub))
        return response()->json([
            'status' => false,
            'message' => $userLang==='en'?config('responses.no_active_sub.en'):config('responses.no_active_sub.ar')
        ]);
        $accessType = Subscription::where('id',$currentSub->sub_id)->first(['access_type','price']);
        if($accessType->access_type === 'full_access')
        return response()->json([
            'status' => false,
            'message' => $userLang==='en'?config('responses.already_full.en'):config('responses.already_full.ar')
        ]);

        $premiumSub = Subscription::where('access_type','full_access')->where('status',1)->orderBy('created_at','desc')->first();
        $reqPrice = $premiumSub->price - $accessType->price;
        $stripeCustomer = Auth::user()->stripe_customer;
        if(empty($stripeCustomer))
        return response()->json([
            'status' => true,
            'data' => [
                'price_for_upgrade' => '$'.$reqPrice,
                'user_cards' => [],
                'sub_name' => $premiumSub->name,
                'description' => $premiumSub->description
            ]
        ]);
        $secret_key = config('app.stripe_secret_key');
        $stripe = new \Stripe\StripeClient($secret_key);
        $cardsStripe = $stripe->paymentMethods->all(['customer' => $stripeCustomer, 'type' => 'card']);
        $cards = [];
        foreach ($cardsStripe->data as $card) {
            $temp = new stdClass;
            $temp->brand = $card->card->brand;
            $temp->last4 = $card->card->last4;
            $temp->expires = $card->card->exp_month.'/'.$card->card->exp_year;
            $temp->stripeId = $card->id;
            array_push($cards,$temp);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'price_for_upgrade' => '$'.$reqPrice,
                'user_cards' => $cards,
                'sub_name' => $premiumSub->name,
                'description' => $premiumSub->description
            ]
        ]);
    }

    function upgradeSubscription(Request $request){
        $validate = Validator::make($request->all(),[
            'card_id' => 'string|nullable'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0],
            'stripe_payment' => false
        ]);
        $userLang = $this->userSelecetdLanguage(Auth::id());
        $currentSub = UserSub::where('user_id',Auth::id())->where('status','active')->orderBy('id','desc')->first();
        if(is_null($currentSub))
        return response()->json([
            'status' => false,
            'message' => $userLang==='en'?config('responses.no_active_sub.en'):config('responses.no_active_sub.ar'),
            'stripe_payment' => false
        ]);
        $accessType = Subscription::where('id',$currentSub->sub_id)->first(['access_type','price']);
        if($accessType->access_type === 'full_access')
        return response()->json([
            'status' => false,
            'message' => $userLang==='en'?config('responses.already_full.en'):config('responses.already_full.ar'),
            'stripe_payment' => false
        ]);
        $premiumSub = Subscription::where('access_type','full_access')->where('status',1)->orderBy('created_at','desc')->first();
        $reqPrice = ($premiumSub->price - $accessType->price)*100;

        if(isset($request->card_id) && !is_null($request->card_id)) {       // upgrading from saved card
            $stripeCust = Auth::user()->stripe_customer;
            if(empty($stripeCust))
            return response()->json([
                'status' => false,
                'message' => $userLang==='en'?config('responses.pay_failed.en'):config('responses.pay_failed.ar'),
                'stripe_payment' => false
            ]);
            try{    // get payment using stripe from the user stored card
                $secret_key = config('app.stripe_secret_key');
                \Stripe\Stripe::setApiKey($secret_key);
                $intent = \Stripe\PaymentIntent::create([
                    'amount' => $reqPrice,
                    'currency' => 'usd',
                    'customer' => $stripeCust,
                    'payment_method' => $request->card_id,
                    'off_session' => true,
                    'confirm' => true,
                    'payment_method_types' => ['card']
                ]);
            } catch (Exception $er){
                return response()->json([
                    'status' => false,
                    'error' => $er->getMessage(),
                    'message' => $userLang==='en'?config('responses.pay_failed.en'):config('responses.pay_failed.ar'),
                    'stripe_payment' => false
                ]);
            }
            if($intent->status==='succeeded'){      // if payment succeeded
                $currentSub->status = 'expired';
                $currentSub->save();
    
                $newPayment = new Payment();
                $newPayment->amount = round($reqPrice/100,2);
                $newPayment->intent = $intent->id;
                $newPayment->card_used = $intent->charges->data[0]->payment_method_details->card->brand.' '.$intent->charges->data[0]->payment_method_details->card->last4;
                $newPayment->stripe_reponse = json_encode($intent);
                $newPayment->status = "success";
                $newPayment->save();    //store payment info
    
                $newSub = new UserSub();
                $newSub->sub_id = $premiumSub->id;
                $newSub->user_id = Auth::id();
                $newSub->payment_id = $newPayment->id;
                $newSub->status = 'active';
                $newSub->sub_start_date = Carbon::today();
                $newSub->sub_expire_date = Carbon::today()->addRealMonth();
                $newSub->save();
    
                $detailData = UserDetail::where('user_id',Auth::id())->first();
                $detailData->subscription = $newSub->sub_id;
                $detailData->subscription_status = 'active';
                $detailData->update();
    
                $notiReciever = User::where('role',2)->pluck('id')->first();
                $notiSource = Auth::id();
                $notiTitle = 'Subscription Upgraded!';
                $notiContent = Auth::user()->name.' just upgraded for $'.$newPayment->amount;
                $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
    
                return response()->json([
                    'status' => true,
                    'message' => $userLang==='en'?config('responses.sub_upgraded.en'):config('responses.sub_upgraded.ar'),
                    'stripe_payment' => false
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => $userLang==='en'?config('responses.pay_failed.en'):config('responses.pay_failed.ar'),
                    'stripe_payment' => false
                ]);
            }
        } else {                                                            // upgrading from new card
            $stripeCustForNew = Auth::user()->stripe_customer;
            if(empty($stripeCustForNew))
            return response()->json([
                'status' => false,
                'message' => $userLang==='en'?config('responses.pay_failed.en'):config('responses.pay_failed.ar'),
                'stripe_payment' => false
            ]);
            try {
                $secret_key = config('app.stripe_secret_key');
                \Stripe\Stripe::setApiKey($secret_key);
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'customer' => $stripeCustForNew,
                    'setup_future_usage' => 'off_session',
                    'amount' => $reqPrice,
                    'currency' => 'usd',
                    'payment_method_types' => ['card'],
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage()." Line #".$e->getLine()
                ]);
            }
            $newPayment = new Payment();
            $newPayment->amount = round($reqPrice/100,2);
            $newPayment->intent = $paymentIntent->id;
            $newPayment->stripe_reponse = json_encode($paymentIntent);
            $newPayment->status = "pending";
            $newPayment->save();

            $newSub = new UserSub();
            $newSub->sub_id = $premiumSub->id;
            $newSub->user_id = Auth::id();
            $newSub->payment_id = $newPayment->id;
            $newSub->status = 'awaiting_payment';
            $newSub->save();

            return response()->json([
                'status' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'stripe_payment' => true,
                'payment_id' => $newPayment->id
            ]);
        }
    }

    function completeUpgrade(Request $request){
        $validate = Validator::make($request->all(),[
            'payment_id' => 'required|exists:payments,id'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $payment = Payment::find($request->payment_id);
        if($payment->status!=='pending'){
            return response()->json([
                'status' => false,
                'message' => 'Invalid Payment Id'
            ]);
        }
        $userLanguage = $this->userSelecetdLanguage(Auth::id());
        $secret_key = config('app.stripe_secret_key');
        \Stripe\Stripe::setApiKey($secret_key);

        $paymentIntent = \Stripe\PaymentIntent::retrieve([
            'id' => $payment->intent
        ]);
        if($paymentIntent->status == "requires_payment_method"){
            $payment->stripe_reponse = json_encode($paymentIntent);
            $payment->update();
            return response()->json([
                'status' => false,
                'message' => 'Payment Not Completed Yet!'
            ]);
        } else if($paymentIntent->status == "succeeded") {

            $currentSub = UserSub::where('user_id',Auth::id())->where('status','active')->orderBy('id','desc')->first();
            $currentSub->status = 'expired';
            $currentSub->save();

            $payment->stripe_reponse = json_encode($paymentIntent);
            $payment->status = 'succeeded';
            $payment->card_used = $paymentIntent->charges->data[0]->payment_method_details->card->brand.' '.$paymentIntent->charges->data[0]->payment_method_details->card->last4;
            $payment->save();

            $userSub = UserSub::where('payment_id',$payment->id)->first();
            $userSub->status = 'active';
            $userSub->sub_start_date = Carbon::today();
            $userSub->sub_expire_date = Carbon::today()->addRealMonth();
            $userSub->update();

            $detailData = UserDetail::where('user_id',Auth::id())->first();
            $detailData->subscription = $userSub->sub_id;
            $detailData->subscription_status = 'active';
            $detailData->update();

            $notiReciever = User::where('role',2)->pluck('id')->first();
            $notiSource = Auth::id();
            $notiTitle = 'Subscription Upgraded!';
            $notiContent = Auth::user()->name.' just upgraded for $'.$payment->amount;
            $this->storeNotification($notiReciever,$notiTitle,null,$notiContent,null,$notiSource);
            return response()->json([
                'status' => true,
                'message' => $userLanguage==='en'?config('responses.pay_sub_success.en'):config('responses.pay_sub_success.ar')
            ]);
        } else {
            $payment->stripe_reponse = json_encode($paymentIntent);
            $payment->status = 'failed';
            $payment->update();
            return response()->json([
                'status' => false,
                'message' => $userLanguage==='en'?config('responses.pay_sub_fail.en'):config('responses.pay_sub_fail.ar')
            ]);
        }
    }
}
