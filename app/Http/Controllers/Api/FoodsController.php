<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Food;
use App\Models\Tag;
use App\Traits\ActivitiesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class FoodsController extends Controller
{
    use ActivitiesTrait;

    private function applyTagFilter($query, $tags)
    {
        $tagArray = is_array($tags) ? $tags : explode(',', $tags);
        $tagIds = array_values(array_filter(array_map(static function ($tagId) {
            return is_numeric($tagId) ? (int) $tagId : null;
        }, $tagArray)));

        if (empty($tagIds)) {
            return $query;
        }

        return $query->where(function ($q) use ($tagIds) {
            foreach ($tagIds as $tagId) {
                $q->orWhere('tags', 'like', '%['.$tagId.',%')
                    ->orWhere('tags', 'like', '%['.$tagId.']%')
                    ->orWhere('tags', 'like', '%,'.$tagId.',%')
                    ->orWhere('tags', 'like', '%,'.$tagId.']%');
            }
        });
    }

    //
    function createFood(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required|string',
            'serving_size' => 'required|string',
            'calories' => 'required|numeric',
            'protein' => 'required|numeric',
            'carbs' => 'required|numeric',
            'fat' => 'required|numeric',
            'fiber' => 'required|numeric',
            'tags' => 'required|string',
            'image' => 'required|mimes:jpg,png,jpeg,JPG,PNG,JPEG|max:1024',
            'saturated_fat' => 'numeric|nullable',
            'trans_fat' => 'numeric|nullable',
            'polyunsaturated_fat' => 'numeric|nullable',
            'monounsaturated_fat' => 'numeric|nullable',
            'cholestrol' => 'numeric|nullable',
            'sodium' => 'numeric|nullable',
            // 'dietary_fiber' => 'numeric|nullable',
            'total_sugars' => 'numeric|nullable',
            'vitamin_a' => 'numeric|nullable',
            'vitamin_c' => 'numeric|nullable',
            'vitamin_d' => 'numeric|nullable',
            'vitamin_e' => 'numeric|nullable',
            'thiamin' => 'numeric|nullable',
            'riboflavin' => 'numeric|nullable',
            'niacin' => 'numeric|nullable',
            'vitamin_b6' => 'numeric|nullable',
            'vitamin_b12' => 'numeric|nullable',
            'pantothenic_acid' => 'numeric|nullable',
            'calcium' => 'numeric|nullable',
            'iron' => 'numeric|nullable',
            'potassium' => 'numeric|nullable',
            'phosphorus' => 'numeric|nullable',
            'magnesium' => 'numeric|nullable',
            'zinc' => 'numeric|nullable',
            'selenium' => 'numeric|nullable',
            'copper' => 'numeric|nullable',
            'menganese' => 'numeric|nullable',
            'alchohal' => 'numeric|nullable',
            'caffeine' => 'numeric|nullable',
            'language' => 'required|in:en,ar',
		]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $newId = Food::orderBy('id','desc')->pluck('id')->first()+1;
        $url = $newId."_food_".time().'.'.$request->image->getClientOriginalExtension();
        $request->image->storeAs('foods', $url, 'fwd_media');
        $food = new Food();
        $food->name = $request->name;
        $food->serving_size = $request->serving_size;
        $food->calories = $request->calories;
        $food->protein = $request->protein;
        $food->carbs = $request->carbs;
        $food->fat = $request->fat;
        $food->fiber = $request->fiber;
        $food->tags = $request->tags;
        $food->image = $url;
        $food->saturated_fat = $request->saturated_fat;
        $food->trans_fat = $request->trans_fat;
        $food->polyunsaturated_fat = $request->polyunsaturated_fat;
        $food->monounsaturated_fat = $request->monounsaturated_fat;
        $food->cholestrol = $request->cholestrol;
        $food->sodium = $request->sodium;
        // $food->dietary_fiber = $request->dietary_fiber;
        $food->total_sugars = $request->total_sugars;
        $food->vitamin_a = $request->vitamin_a;
        $food->vitamin_c = $request->vitamin_c;
        $food->vitamin_d = $request->vitamin_d;
        $food->vitamin_e = $request->vitamin_e;
        $food->thiamin = $request->thiamin;
        $food->riboflavin = $request->riboflavin;
        $food->niacin = $request->niacin;
        $food->vitamin_b6 = $request->vitamin_b6;
        $food->vitamin_b12 = $request->vitamin_b12;
        $food->pantothenic_acid = $request->pantothenic_acid;
        $food->calcium = $request->calcium;
        $food->iron = $request->iron;
        $food->potassium = $request->potassium;
        $food->phosphorus = $request->phosphorus;
        $food->magnesium = $request->magnesium;
        $food->zinc = $request->zinc;
        $food->selenium = $request->selenium;
        $food->copper = $request->copper;
        $food->menganese = $request->menganese;
        $food->alchohal = $request->alchohal;
        $food->caffeine = $request->caffeine;
        $food->language = $request->language;
        $food->save();

        if($request->language == 'en'){
            //save arabic food
            $food = new Food();
            $food->name = $this->getTranslatedText($request->name, 'ar');
            $food->serving_size = $request->serving_size;
            $food->calories = $request->calories;
            $food->protein = $request->protein;
            $food->carbs = $request->carbs;
            $food->fat = $request->fat;
            $food->fiber = $request->fiber;
            $food->tags = $request->tags;
            $food->image = $url;
            $food->saturated_fat = $request->saturated_fat;
            $food->trans_fat = $request->trans_fat;
            $food->polyunsaturated_fat = $request->polyunsaturated_fat;
            $food->monounsaturated_fat = $request->monounsaturated_fat;
            $food->cholestrol = $request->cholestrol;
            $food->sodium = $request->sodium;
            // $food->dietary_fiber = $request->dietary_fiber;
            $food->total_sugars = $request->total_sugars;
            $food->vitamin_a = $request->vitamin_a;
            $food->vitamin_c = $request->vitamin_c;
            $food->vitamin_d = $request->vitamin_d;
            $food->vitamin_e = $request->vitamin_e;
            $food->thiamin = $request->thiamin;
            $food->riboflavin = $request->riboflavin;
            $food->niacin = $request->niacin;
            $food->vitamin_b6 = $request->vitamin_b6;
            $food->vitamin_b12 = $request->vitamin_b12;
            $food->pantothenic_acid = $request->pantothenic_acid;
            $food->calcium = $request->calcium;
            $food->iron = $request->iron;
            $food->potassium = $request->potassium;
            $food->phosphorus = $request->phosphorus;
            $food->magnesium = $request->magnesium;
            $food->zinc = $request->zinc;
            $food->selenium = $request->selenium;
            $food->copper = $request->copper;
            $food->menganese = $request->menganese;
            $food->alchohal = $request->alchohal;
            $food->caffeine = $request->caffeine;
            $food->language = 'ar';
            $food->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Food Created Successfully'
        ]);
    }

    /**
     * Get foods for mobile app - filtered by user's language
     */
    function getFoodsForMobile(Request $request){
        $userLang = $this->userSelecetdLanguage(Auth::id()) ?? 'en';
        $query = Food::where('language', $userLang)->orderBy('id','desc');

        $tags = $request->get('tags');
        if($tags) {
            $query = $this->applyTagFilter($query, $tags);
        }

        $search = $request->get('search');
        if($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $data = $query->get(['id','name','language','serving_size','calories','protein','carbs','fat','fiber','tags','image']);
        foreach ($data as $fd) {
            if(is_null($fd->tags))
            $fd->tagNames = [];
            else{
                $fd->tags = json_decode($fd->tags);
                $fd->tagNames = Tag::whereIn('id',$fd->tags)->pluck('name')->toArray();
            }
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    function getFoodsList(Request $request){
        $query = Food::orderBy('id','desc');
        
        // Add tag filtering if provided
        $tags = $request->get('tags');
        if($tags) {
            $query = $this->applyTagFilter($query, $tags);
        }
        
        $data = $query->get(['id','name','language','serving_size','calories','protein','carbs','fat','fiber','tags','image']);
        foreach ($data as $fd) {
            if(is_null($fd->tags))
            $fd->tagNames = [];
            else{
                $fd->tags = json_decode($fd->tags);
                $fd->tagNames = Tag::whereIn('id',$fd->tags)->pluck('name')->toArray();
            }
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    function getSpecificFoodsList($query){
        $data = Food::where('name', 'like', '%' . $query . '%')->orderBy('id','desc')->get(['id','name','language','serving_size','calories','protein','carbs','fat','fiber','tags','image']);
        foreach ($data as $fd) {
            if(is_null($fd->tags))
            $fd->tagNames = [];
            else{
                $fd->tags = json_decode($fd->tags);
                $fd->tagNames = Tag::whereIn('id',$fd->tags)->pluck('name')->toArray();
            }
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    function foodDetail($id){
        $food = Food::find($id);
        if($food){
            if(is_null($food->tags))
            $food->tagNames = [];
            else{
                $food->tags = json_decode($food->tags);
                $food->tagNames = Tag::whereIn('id',$food->tags)->pluck('name')->toArray();
            }
            foreach ($food->toArray() as $key => $value) {
                if(is_null($value) || $value === 0)
                unset($food[$key]);
            }
            return response()->json([
                'status' => true,
                'data' => $food
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Invalid Food Id'
        ]);
    }

    function fullFoodDetail($id){
        $food = Food::find($id);
        if(is_null($food))
        return response()->json([
            'status' => false,
            'message' => 'Invalid Food Id'
        ]);
        if(is_null($food->tags))
        $food->tagNames = [];
        else{
            $food->tags = json_decode($food->tags);
            $food->tagNames = Tag::whereIn('id',$food->tags)->pluck('name')->toArray();
        }
        return response()->json([
            'status' => true,
            'data' => $food
        ]);
    }

    function updateFood(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required|numeric|exists:foods,id',
            'name' => 'required|string',
            'serving_size' => 'required|string',
            'calories' => 'required|numeric',
            'protein' => 'required|numeric',
            'carbs' => 'required|numeric',
            'fat' => 'required|numeric',
            'fiber' => 'required|numeric',
            'tags' => 'required|string',
            'image' => 'mimes:jpg,png,jpeg,JPG,PNG,JPEG|max:1024',
            'saturated_fat' => 'numeric|nullable',
            'trans_fat' => 'numeric|nullable',
            'polyunsaturated_fat' => 'numeric|nullable',
            'monounsaturated_fat' => 'numeric|nullable',
            'cholestrol' => 'numeric|nullable',
            'sodium' => 'numeric|nullable',
            // 'dietary_fiber' => 'numeric|nullable',
            'total_sugars' => 'numeric|nullable',
            'vitamin_a' => 'numeric|nullable',
            'vitamin_c' => 'numeric|nullable',
            'vitamin_d' => 'numeric|nullable',
            'vitamin_e' => 'numeric|nullable',
            'thiamin' => 'numeric|nullable',
            'riboflavin' => 'numeric|nullable',
            'niacin' => 'numeric|nullable',
            'vitamin_b6' => 'numeric|nullable',
            'vitamin_b12' => 'numeric|nullable',
            'pantothenic_acid' => 'numeric|nullable',
            'calcium' => 'numeric|nullable',
            'iron' => 'numeric|nullable',
            'potassium' => 'numeric|nullable',
            'phosphorus' => 'numeric|nullable',
            'magnesium' => 'numeric|nullable',
            'zinc' => 'numeric|nullable',
            'selenium' => 'numeric|nullable',
            'copper' => 'numeric|nullable',
            'menganese' => 'numeric|nullable',
            'alchohal' => 'numeric|nullable',
            'caffeine' => 'numeric|nullable',
            'language' => 'required|in:en,ar',
		]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $food = Food::find($request->id);
        $food->name = $request->name;
        $food->language = $request->language;
        $food->serving_size = $request->serving_size;
        $food->calories = $request->calories;
        $food->protein = $request->protein;
        $food->carbs = $request->carbs;
        $food->fat = $request->fat;
        $food->fiber = $request->fiber;
        $food->tags = $request->tags;
        $food->saturated_fat = $request->saturated_fat;
        $food->trans_fat = $request->trans_fat;
        $food->polyunsaturated_fat = $request->polyunsaturated_fat;
        $food->monounsaturated_fat = $request->monounsaturated_fat;
        $food->cholestrol = $request->cholestrol;
        $food->sodium = $request->sodium;
        // $food->dietary_fiber = $request->dietary_fiber;
        $food->total_sugars = $request->total_sugars;
        $food->vitamin_a = $request->vitamin_a;
        $food->vitamin_c = $request->vitamin_c;
        $food->vitamin_d = $request->vitamin_d;
        $food->vitamin_e = $request->vitamin_e;
        $food->thiamin = $request->thiamin;
        $food->riboflavin = $request->riboflavin;
        $food->niacin = $request->niacin;
        $food->vitamin_b6 = $request->vitamin_b6;
        $food->vitamin_b12 = $request->vitamin_b12;
        $food->pantothenic_acid = $request->pantothenic_acid;
        $food->calcium = $request->calcium;
        $food->iron = $request->iron;
        $food->potassium = $request->potassium;
        $food->phosphorus = $request->phosphorus;
        $food->magnesium = $request->magnesium;
        $food->zinc = $request->zinc;
        $food->selenium = $request->selenium;
        $food->copper = $request->copper;
        $food->menganese = $request->menganese;
        $food->alchohal = $request->alchohal;
        $food->caffeine = $request->caffeine;
        if($request->has('image')){
            $url = $request->id."_food_".time().'.'.$request->image->getClientOriginalExtension();
            $request->image->storeAs('foods', $url, 'fwd_media');
            $food->image = $url;
        }
        $food->update();
        return response()->json([
            'status' => true,
            'message' => 'Food Updated Successfully'
        ]);
    }
    function deleteFood(Request $request){
        $validate = Validator::make($request->all(),[
            'ids' => 'required|array',
		]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        Food::destroy($request->ids);
        return response()->json([
            'status' => true,
            'message' => 'Food Deleted Successfully'
        ]);
    }

    public function convertFoodToArabic(){
        try {
            $foods = Food::get();
            foreach($foods as $item){
                if($item->language == 'en'){
                    $food = new Food();
                    $food->name = $this->getTranslatedText($item->name, 'ar');
                    $food->serving_size = $item->serving_size;
                    $food->calories = $item->calories;
                    $food->protein = $item->protein;
                    $food->carbs = $item->carbs;
                    $food->fat = $item->fat;
                    $food->fiber = $item->fiber;
                    $food->tags = $item->tags;
                    $food->image = basename($item->image);
                    $food->saturated_fat = $item->saturated_fat;
                    $food->trans_fat = $item->trans_fat;
                    $food->polyunsaturated_fat = $item->polyunsaturated_fat;
                    $food->monounsaturated_fat = $item->monounsaturated_fat;
                    $food->cholestrol = $item->cholestrol;
                    $food->sodium = $item->sodium;
                    $food->total_sugars = $item->total_sugars;
                    $food->vitamin_a = $item->vitamin_a;
                    $food->vitamin_c = $item->vitamin_c;
                    $food->vitamin_d = $item->vitamin_d;
                    $food->vitamin_e = $item->vitamin_e;
                    $food->thiamin = $item->thiamin;
                    $food->riboflavin = $item->riboflavin;
                    $food->niacin = $item->niacin;
                    $food->vitamin_b6 = $item->vitamin_b6;
                    $food->vitamin_b12 = $item->vitamin_b12;
                    $food->pantothenic_acid = $item->pantothenic_acid;
                    $food->calcium = $item->calcium;
                    $food->iron = $item->iron;
                    $food->potassium = $item->potassium;
                    $food->phosphorus = $item->phosphorus;
                    $food->magnesium = $item->magnesium;
                    $food->zinc = $item->zinc;
                    $food->selenium = $item->selenium;
                    $food->copper = $item->copper;
                    $food->menganese = $item->menganese;
                    $food->alchohal = $item->alchohal;
                    $food->caffeine = $item->caffeine;
                    $food->language = 'ar';
                    $food->save();
                }

            }
            return true;
        } catch(Exception $er){
            Log::info("Error: ".$er->getMessage());
            return $inputText; 
        }
    }

    function getTranslatedText(string $inputText,string $targetLanguage) {
        try {
            $translateBaseUrl = config('app.google_trans_baseUrl');
            $tranlateApiKey = config('app.google_api_key');
            // $inputText = urlencode($inputText);
            $payload = [
                "q"      => $inputText,
                "source" => "en",
                "target" => "ar",
                "format" => "text"
            ];

            $response = Http::post($translateBaseUrl."?key=".$tranlateApiKey,$payload)->json();
            if(isset($response['data']) && isset($response['data']['translations']) && count($response['data']['translations'])>0)
            return $response['data']['translations'][0]['translatedText'];
            else
            return $inputText;
        } catch(Exception $er){
            Log::info("Error Translating: (".$er->getMessage().")");
            return $inputText; 
        }
    }
}
