<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DCodeUsage;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSub;
use App\Traits\ActivitiesTrait;
use App\Traits\NotificationsTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionsController extends Controller
{
    use NotificationsTrait,ActivitiesTrait;
    public function getSubscriptions(){
        $subscriptions = Subscription::where('status',1)->get();
        return response()->json([
			'status' => true,
			'data' => $subscriptions
		]);
    }

    function getSubscriptionStatus(){
        $subDetail = UserDetail::where('user_id',Auth::id())->first(['subscription','subscription_status']);
        if($subDetail->subscription_status==='active'){
            $subType = Subscription::where('id',$subDetail->subscription)->pluck('access_type')->first();
            return response()->json([
                'status' => true,
                'message' => 'Subscription is Active.',
                'two_way_chat' => $subType==='full_access'?true:false
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Subscription is Expired.',
            'two_way_chat' => false
        ]);
    }

    function allProducts(){
        $products = Subscription::where('status',1)->get();
        foreach ($products as $product) {
            $product->clients = $product->totalClients();
        }
        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    function createProduct(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required',
            'price' => 'required|numeric|min:1|max:1000000',
            'description' => 'required',
            'access_type' => 'required|in:half_access,full_access',
            'image' => 'required|mimes:jpg,png,jpeg|max:10240'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $newId = Subscription::orderBy('id','desc')->pluck('id')->first()+1;
        $imageUrl = $newId."_product_".time().'.'.request()->image->getClientOriginalExtension();
        $request->image->storeAs('product', $imageUrl, config('filesystems.default'));

        $product = new Subscription();
        $product->name = $request->name;
        $product->price = round($request->price*100);
        $product->description = $request->description;
        $product->access_type = $request->access_type;
        $product->image = $imageUrl;
        $product->status = 1;
        $product->save();
        return response()->json([
            'status' => true,
            'message' => 'New Product Created.'
        ]);
    }

    function editProduct(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required|exists:subscriptions,id',
            'price' => 'numeric',
            'access_type' => 'in:half_access,full_access',
            'image' => 'mimes:jpg,png,jpeg|max:10240'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);

        $product = Subscription::find($request->id);
        if(isset($request->name))
        $product->name = $request->name;
        if(isset($request->price))
        $product->price = round($request->price*100);
        if(isset($request->description))
        $product->description = $request->description;
        if(isset($request->access_type))
        $product->access_type = $request->access_type;
        if(isset($request->image)){
            $imageUrl = $request->id."_product_".time().'.'.request()->image->getClientOriginalExtension();
            $request->image->storeAs('product', $imageUrl, config('filesystems.default'));
            $product->image = $imageUrl;
        }

        $product->update();
        return response()->json([
            'status' => true,
            'message' => 'Product Updated.'
        ]);
    }

    function deleteProduct($id){
        $product = Subscription::find($id);
        if(is_null($product))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Product ID.'
        ]);
        $product->status = 0;
        $product->update();
        return response()->json([
            'status' => true,
            'message' => 'Product Deleted.'
        ]);
    }

    function allDiscountCodes(){
        $codes = DiscountCode::orderBy('id','desc')->get();
        return response()->json([
            'status' => true,
            'data' => $codes
        ]);
    }

    function createDiscountCode(Request $request){
        $validate = Validator::make($request->all(),[
            'code' => 'required|string|unique:discount_codes,code|min:5|max:15',
            'type' => 'required|in:percentage,amount',
            'discount' => 'required|numeric',
            'user_emails' => 'nullable|array',
            'products' => 'required|array|min:1',
            'availability' => 'required|in:everyone,specific',
            'valid_till' => 'required|date'
        ],[
            'code.unique' => 'Code Already Exists, Please Choose a Different Code'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        if($request->availability==='specific' && count($request->user_emails)>0){
            try{
                Validator::validate($request->user_emails,['email']);
            } catch(Exception $er){
                return response()->json([
                    'status' => false,
                    'message' => 'Emails must be valid email addresses.'
                ]);
            }
        }
        if($request->type==='percentage' && ($request->discount < 1 || $request->discount > 100))
        return response()->json([
            'status' => false,
            'message' => 'Discount Percentage Must be Between 1 and 100.'
        ]);
        if($request->type==='amount' && ($request->discount < 1 || $request->discount > 1000))
        return response()->json([
            'status' => false,
            'message' => 'Discount Amount Must be Between 1 and 1000.'
        ]);

        $date = Carbon::parse($request->valid_till)->endOfDay();
        if($date<Carbon::today())
        return response()->json([
            'status' => false,
            'message' => 'Validity date is passed'
        ]);

        $newCode = new DiscountCode();
        $newCode->code = $request->code;
        $newCode->type = $request->type;
        $newCode->off_by = $request->discount;
        $newCode->availability = $request->availability;
        $newCode->valid_till = $date;
        $newCode->valid_products = json_encode($request->products);
        $newCode->status = 1;
        $newCode->save();
        if($request->availability==='specific')
        foreach ($request->user_emails as $email) {
            $codeUser = new DCodeUsage();
            $codeUser->code_id = $newCode->id;
            $codeUser->user_email = $email;
            $codeUser->status = 0;
            $codeUser->save();
        }
        return response()->json([
            'status' => true,
            'message' => 'Discount code created'
        ]);
    }

    function disableCode($id){
        $code = DiscountCode::find($id);
        if(is_null($code))
        return response()->json([
            'status' => false,
            'message' => 'Discount Code Not Found'
        ]);
        if($code->status===1)
        $code->status = 0;
        else
        $code->status = 1;
        $code->update();
        return response()->json([
            'status' => true,
            'message' => 'Discount Code Disabled'
        ]);
    }

}
