<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserSetting;
use App\Models\UserTag;
use App\Models\ConsultationForm;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionsController extends Controller
{
    //
    function deleteAllQuestions(){
        Question::truncate();
        return response()->json([
            'status' => true,
            'message' => 'Questions Deleted.'
        ]);
    }

    function getQuetions(){
        $lang = UserSetting::where('user_id',Auth::id())->pluck('language')->first();
        $ques = Question::orderBy('order','asc')->where('status',1)->get();
        foreach ($ques as $que) {
            if($lang==='en'){
                $que->question = $que->question_en;
                $que->options = $que->options_en;
                $que->note = $que->note_en;
            } else {
                $que->question = $que->question_ar;
                $que->options = $que->options_ar;
                $que->note = $que->note_ar;
            }
            unset($que->question_en);
            unset($que->options_en);
            unset($que->note_en);
            unset($que->question_ar);
            unset($que->options_ar);
            unset($que->note_ar);
            unset($que->tag);
            unset($que->status);
        }
        return response()->json([
            'status' => true,
            'data' => $ques
        ]);
    }

    function submitAnswers(Request $request){
        $validator = Validator::make($request->all(),[
            'data' => 'required|array',
        ]);
        if($validator->fails())
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->all()[0]
        ]);
        $userId = Auth::id();
        if(UserAnswer::where('user_id',$userId)->count()>0)
        return response()->json([
            'status' => false,
            'message' => 'Questions Already Answered.'
        ]);
        $totalQues = Question::where('status',1)->count();
        $totalAns = count($request->data);
        if($totalAns!==$totalQues)
        return response()->json([
            'status' => false,
            'message' => 'Number of Answers are not Equal to Questions.'
        ]);
        try{
            $tagsArray = [];
            foreach ($request->data as $q) {
                $que = Question::where('id',$q['qId'])->where('status',1)->first(['tag','type','options_en']);
                if(is_null($que)){
                    UserAnswer::where('user_id',$userId)->delete(); // delete currently saved answers if any question id is wrong
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Question ID.'
                    ]);
                }
                $ans = new UserAnswer();
                $ans->question_id = $q['qId'];
                $ans->user_id = $userId;
                if($que->type==='fill_blank'){      // if question type is fill_blank
                    $ans->answer = $q['answer'][0];      // string at 0 index contains answer
                    $ans->answer_type = 'single';
                } else if($que->type==='single_choice'){
                    $ans->answer_type = 'single';       // if question type is single_choice
                    $ans->answer = $que->options_en[(int)$q['answer'][0]];      // string at 0 index contains option index selected by user
                } else if($que->type==='multiple_choice'){
                    $ans->answer_type = 'multiple';       // if question type is multiple_choice, then answer array contains selected string indexes of options
                    $tempArray = [];     // to store answers
                    foreach ($q['answer'] as $ansIndex) {
                        array_push($tempArray,$que->options_en[(int)$ansIndex]);    // get the answer at index
                    }
                    $ans->answer = json_encode(array_unique($tempArray));
                } else {    // type select
                    $ans->answer = $q['answer'][0];      // string at 0 index contains answer
                    $ans->answer_type = 'single';
                }
                $ans->save();

                if($que->tag === 'ANSWER'){             // if question tag is enabled
                    if($ans->answer_type==='single'){       // if answer type is single
                        if(!empty($ans->answer) && $ans->answer !== null && $ans->answer !== ''){
                            $tag = Tag::where('name',$ans->answer)->first();
                            if($tag){                                 // if tag exist, put its id in different array
                                if(!in_array($tag->id,$tagsArray))
                                array_push($tagsArray,$tag->id);
                            } else {                                // if tag does not exist, create one and put its id in seperate array
                                $tag = new Tag();
                                $tag->name = $ans->answer;
                                $tag->type = 'consultation';
                                $tag->category = 'client';
                                $tag->save();
                                array_push($tagsArray,$tag->id);
                            }
                        }
                    } else {        // if answer type is multiple
                        foreach (json_decode($ans->answer) as $answ) {      // repeat above tag adding procedure for each answer
                            if(!($answ==='' || $answ===null)){
                                $tag = Tag::where('name',$answ)->first();
                                if($tag){
                                    if(!in_array($tag->id,$tagsArray))
                                    array_push($tagsArray,$tag->id);
                                } else {
                                    $tag = new Tag();
                                    $tag->name = $answ;
                                    $tag->type = 'consultation';
                                    $tag->category = 'client';
                                    $tag->save();
                                    array_push($tagsArray,$tag->id);
                                }
                            }
                        }
                    }
                }
            }
            
            // Update user tags using new user_tags pivot table
            $user = User::find($userId);
            if ($user && !empty($tagsArray)) {
                // Sync tags to user_tags pivot table (new system)
                $user->tags()->sync($tagsArray);
                
                // Also update old tags column for backward compatibility
                User::where('id',$userId)->update(['tags' => json_encode($tagsArray)]);
            }
            
            // Create or update ConsultationForm to mark consultation as completed
            $consultation = ConsultationForm::where('user_id', $userId)->first();
            if ($consultation) {
                // Update existing consultation
                $consultation->completed_at = now();
                $consultation->save();
            } else {
                // Create new consultation entry
                ConsultationForm::create([
                    'user_id' => $userId,
                    'completed_at' => now(),
                ]);
            }
        } catch(Exception $er){
            UserAnswer::where('user_id',$userId)->delete();     // delete currently saved answers if anything goes wrong
            return response()->json([
                'status' => false,
                'message' => 'Something Went Wrong.',
                'error' => $er->getMessage().'------Line : '.$er->getLine().'---------File : '.$er->getFile()
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Answers Added.'
        ]);
    }

    function consultationForm(){
        $ques = Question::orderBy('order','asc')->where('status',1)->get();
        return response()->json([
            'status' => true,
            'data' => $ques
        ]);
    }

    function deleteQuestion($id){
        $que = Question::find($id);
        if($que){
            $que->status = 0;
            $que->update();
            Question::where('order','>',$que->order)->decrement('order');
            return response()->json([
                'status' => true,
                'message' => 'Question Deleted.'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Question Not Found.'
        ]);
    }

    function createQuestion(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required|in:multiple_choice,single_choice,fill_blank',
            'tag' => 'required|in:ANSWER,NO',
            'question_en' => 'required|string',
            'question_ar' => 'required|string',
            'note_en' => 'string|nullable',
            'note_ar' => 'string|nullable',
            'options' => 'array|nullable'
        ]);
        if($validator->fails())
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->all()[0]
        ]);
        if($request->type !== 'fill_blank' && (!isset($request->options) || $request->options==null || count($request->options)<2 || count($request->options)>30))
        return response()->json([
            'status' => false,
            'message' => 'Options must be between 2 and 30.'
        ]);

        $newQue = new Question();
        $newQue->question_en = $request->question_en;
        $newQue->question_ar = $request->question_ar;
        $newQue->note_en = $request->note_en;
        $newQue->note_ar = $request->note_ar;
        $newQue->type = $request->type;
        $newQue->tag = $request->tag;
        $newQue->order = 1+Question::where('status',1)->orderBy('order','desc')->pluck('order')->first();
        $newQue->type = $request->type;
        if($request->type === 'fill_blank'){
            $newQue->options_en = '[]';
            $newQue->options_ar = '[]';
        } else {
            $enOpt = [];
            $arOpt = [];
            foreach ($request->options as $opt) {
                if(isset($opt['en']) && isset($opt['ar'])){
                    array_push($enOpt,$opt['en']);
                    array_push($arOpt,$opt['ar']);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Options are not valid.'
                    ]);
                }
            }
            $newQue->options_en = json_encode($enOpt);
            $newQue->options_ar = json_encode($arOpt);
        }
        $newQue->save();
        return response()->json([
            'status' => true,
            'message' => 'New Question Created.'
        ]);
    }

    function updateQuestion(Request $request){
        $validator = Validator::make($request->all(),[
            'qId' => 'required|numeric',
            'type' => 'required|in:multiple_choice,single_choice,fill_blank,select',
            'tag' => 'required|in:ANSWER,NO',
            'question_en' => 'required|string',
            'question_ar' => 'required|string',
            'note_en' => 'string|nullable',
            'note_ar' => 'string|nullable',
            'options' => 'array|nullable',
            'order' => 'required|numeric'
        ]);
        if($validator->fails())
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->all()[0]
        ]);
        $que = Question::where('id',$request->qId)->where('status',1)->first();
        if(is_null($que))
        return response()->json([
            'status' => false,
            'message' => 'Question does not exist.'
        ]);
        if(($request->type === 'multiple_choice' || $request->type === 'single_choice')  && (!isset($request->options) || $request->options==null || count($request->options)<2 || count($request->options)>30))
        return response()->json([
            'status' => false,
            'message' => 'Options is required and between 2 and 30.'
        ]);

        $que->question_en = $request->question_en;
        $que->question_ar = $request->question_ar;
        $que->note_en = $request->note_en;
        $que->note_ar = $request->note_ar;
        $que->type = $request->type;
        $que->tag = $request->tag;
        if($request->type === 'fill_blank' || $request->type === 'select'){
            $que->options_en = '[]';
            $que->options_ar = '[]';
        } else {
            $enOpt = [];
            $arOpt = [];
            foreach ($request->options as $opt) {
                if(isset($opt['en']) && isset($opt['ar'])){
                    array_push($enOpt,$opt['en']);
                    array_push($arOpt,$opt['ar']);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Options are not valid.'
                    ]);
                }
            }
            $que->options_en = json_encode($enOpt);
            $que->options_ar = json_encode($arOpt);
        }
        if($request->order!==$que->order){  // if order updated
            $maxOrder = Question::orderBy('order','desc')->pluck('order')->first();     //current maximum order
            if($request->order>0 && $request->order<=$maxOrder){    // incoming order is in range
                Question::where('order',$request->order)->update(['order' => $que->order]);     // swap the order with existing question's order
                $que->order = $request->order;
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Question Order.'
                ]);
            }
        }
        $que->update();
        return response()->json([
            'status' => true,
            'message' => 'Questions Updated.'
        ]);
    }
}
