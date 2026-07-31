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
        $name = $this->cleanTagValue($request->name);
        $type = $this->cleanTagValue($request->type);
        $category = $this->cleanTagValue($request->category);
        $tagExists = $this->duplicateTagQuery($name, $type, $category)->first();
        if($tagExists)
        return response()->json([
            'status' => false,
            'message' => 'Tag Already Exists.'
        ]);
        $tag = new Tag();
        $tag->name = $name;
        $tag->type = $type;
        $tag->category = $category;
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
        $tags = Tag::where('category',$request->category)->orderBy('type')->orderBy('name')->get();
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
            $name = $this->cleanTagValue($request->name ?? $tag->name);
            $type = $this->cleanTagValue($request->type ?? null);
            $category = $this->cleanTagValue($request->category ?? $tag->category);

            $tagExists = $this->duplicateTagQuery($name, $type, $category)
                ->where('id', '!=', $tag->id)
                ->first();
            if($tagExists)
            return response()->json([
                'status' => false,
                'message' => 'Tag Already Exists.'
            ]);

            $tag->type = $type;
            $tag->name = $name;
            $tag->category = $category;
            
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

    private function cleanTagValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $value === '' ? null : $value;
    }

    private function duplicateTagQuery(?string $name, ?string $type, ?string $category)
    {
        return Tag::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower((string) $name)])
            ->whereRaw('LOWER(TRIM(COALESCE(type, ""))) = ?', [strtolower((string) $type)])
            ->whereRaw('LOWER(TRIM(COALESCE(category, ""))) = ?', [strtolower((string) $category)]);
    }
}
