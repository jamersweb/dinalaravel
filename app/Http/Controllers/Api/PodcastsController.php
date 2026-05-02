<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PodcastsController extends Controller
{
    function createPodcast(Request $request){ 
        $validate = Validator::make($request->all(),[
            'name' => 'required',
            'description' => 'required',
            'file' => 'required|mimes:mp3',
            'app_store_url' => 'nullable|url', // URL to podcast on App Store/iTunes/Spotify
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $newId = Podcast::orderBy('id','desc')->pluck('id')->first()+1;
        $file = $newId  . "_podcast_" . time() . '_' . uniqid() . '.' . $request->file->getClientOriginalExtension();
        $request->file->storeAs('podcast', $file, 'fwd_media');

        $podcast = new Podcast();
        $podcast->name = $request->name;
        $podcast->description = $request->description;
        $podcast->file = $file;
        if(isset($request->app_store_url)){
            $podcast->app_store_url = $request->app_store_url;
        }
        $podcast->save();
        return response()->json([
            'status' => true,
            'message' => 'Podcast added successfully',
        ]);
    }
    function getAllPodcasts(Request $request){
        if(isset($request->page)){
            $currentPage = $request->page;
        }else{
            $currentPage = 1;
        }
        $perPage = 10;
        $offset = ($currentPage-1)*$perPage;
        if($request->filter === 'new'){
            $podcasts = Podcast::where('created_at','>',Carbon::today()->subweek())->offset($offset)->limit($perPage)->get();
        }else{
            $podcasts = Podcast::offset($offset)->limit($perPage)->get();
        }
        foreach($podcasts as $item){
            $item->time = $item->created_at->format('h:i A');
            $item->date = $item->created_at->format('D d M, Y');
        }
        if($currentPage==1)
        $prevPage = null;
        else
        $prevPage = $currentPage-1;

        if($podcasts->count()<$perPage)
        $nextPage = null;
        else
        $nextPage = $currentPage+1;
        return response()->json([
            'status' => true,
            'data' => $podcasts,
            'next' => $nextPage,
            'prev' => $prevPage
        ]);
    }
    function deletePodcast(Request $request){ 
        $validate = Validator::make($request->all(),[
            'ids' => 'required|array',
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        foreach($request->ids as $id){
            Podcast::where('id',$id)->delete();
        }
        return response()->json([
            'status' => true,
            'message' => 'Podcast Deleted Successfully'
        ]);
    }
    function updatePodcast(Request $request){ 
        $validate = Validator::make($request->all(),[
            'id' => 'required',
            'file' => 'mimes:mp3',
            'app_store_url' => 'nullable|url', // URL to podcast on App Store/iTunes/Spotify
        ]);
        if($validate->fails())
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0]
        ]);
        $podcast = Podcast::where('id',$request->id)->first();
        if($podcast){
            if(isset($request->name)){
                $podcast->name = $request->name;
            }
            if(isset($request->description)){
                $podcast->description = $request->description;
            }
            if(isset($request->file)){
                $file = $request->id  . "_podcast_" . time() . '_' . uniqid() . '.' . $request->file->getClientOriginalExtension();
                $request->file->storeAs('podcast', $file, 'fwd_media');
                $podcast->file = $file;
            }
            if(isset($request->app_store_url)){
                $podcast->app_store_url = $request->app_store_url;
            }
            $podcast->update();
            return response()->json([
                'status' => true,
                'message' => 'Podcast Update Successfully'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Podcast not Found'
            ]);
        }
    }
}
