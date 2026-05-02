<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use stdClass;

use function PHPUnit\Framework\isEmpty;

class TagsController extends Controller
{
    public function createTag(Request $request){
        $validate = Validator::make($request->all(),[
            'name' => 'required',
            'category' => 'required|in:meal,exercise,workout,client,program,food'
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $tagExists = Tag::where('name',$request->name)->where('type',$request->type)->where('category',$request->category)->first();
        if($tagExists)
        return response()->json([
            'status' => false,
            'message' => 'Tag Already Exists.'
        ]);
        $tag = new Tag();
        $tag->name =$request->name;
        $tag->type =$request->type;
        $tag->category =$request->category;
        $tag->save();
        return response()->json([
            'status' => true,
            'message' => 'Tag Created.'
        ]);
    }
    public function deleteTag($id){
        $tag = Tag::where('id',$id)->first();
        if($tag){
            $tag->delete();
            return response()->json([
                'status' => true,
                'message' => 'Tag Deleted.'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Tag Not Found.'
            ]);
        }
    }
    public function getTags(Request $request){
        $validate = Validator::make($request->all(),[
            'category' => 'in:meal,exercise,workout,client,program,food',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $cat = $request->category;
        
        // Get all tags for this category, including those with null type (uncategorized)
        $allTags = Tag::where('category',$cat)->get(['id','name','type']);
        
        // Group by type (including null as 'Uncategorized')
        $tagsByType = [];
        foreach ($allTags as $tag) {
            $type = $tag->type ?? 'Uncategorized';
            if (!isset($tagsByType[$type])) {
                $tagsByType[$type] = [];
            }
            $tagsByType[$type][] = [
                'id' => $tag->id,
                'name' => $tag->name
            ];
        }
        
        // Build response array
        $tagsArray = [];
        foreach ($tagsByType as $type => $tags) {
            $temp = new stdClass;
            $temp->tagType = $type === 'Uncategorized' ? null : $type;
            $temp->tagList = $tags;
            array_push($tagsArray,$temp);
        }
        
        return response()->json([
            'status' => true,
            'data' => $tagsArray
        ]);
    }

    function uncategorizedTags(Request $request){
        $validate = Validator::make($request->all(),[
            'category' => 'in:meal,exercise,workout,client,program,food',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $tags = Tag::where('category',$request->category)->get();
        return response()->json([
            'status' => true,
            'data' => $tags
        ]);
    }

    public function updateTag(Request $request){
        $validate = Validator::make($request->all(),[
            'id' => 'required',
            'category' => 'in:meal,exercise,workout,client,program,food',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $tag = Tag::where('id',$request->id)->first();
        if($tag){
            $tag->type =$request->type;
            if(isset($request->name) && !is_null($request->name) && $request->name != '')
            $tag->name =$request->name;
            if(isset($request->category) && !is_null($request->category) && $request->category != '')
            $tag->category =$request->category;
            
            $tag->update();
            return response()->json([
                'status' => true,
                'message' => 'Tag Updated.'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Tag Not Found.'
            ]);
        }
    }
}
