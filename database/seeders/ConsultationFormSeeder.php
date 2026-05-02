<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class ConsultationFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Question::count() > 0)
        return;
        $questions = [
            [
                'q_en' => 'Where did you find out about my program?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['Friend','Instagram','Facebook','Tiktok','Google','Youtube','Other'],
                'op_ar' => ['','','','','','',''],
            ],
            [ // tag on answer
                'q_en' => 'Fitness Level:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'tag' => 'ANSWER',
                'q_type' => 'single_choice',
                'op_en' => ['Beginner','Intermediate','Advance'],
                'op_ar' => ['','',''],
            ],
            [
                'q_en' => 'Which program do you want to start with?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'single_choice',
                'op_en' => ['Beginner Level','Intermediate Level','Advanced Level'],
                'op_ar' => ['','',''],
            ],
            [
                'q_en' => 'Please check any of the boxes if you are dealing with any of these below:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'tag' => 'ANSWER',
                'q_type' => 'multiple_choice',
                'op_en' => ['Insulin resistance','Diabetes','PCOS','Endometriosis','Thyroid imbalance','Other hormonal imbalances','High blood pressure','Low blood pressure','IBS irritable bowel syndrome','SIBO Small intestinal bacteria overgrowth','Bloating & other digestive issues','Sciatic nerve pain','Lower back pain','Diastasis recti (separating of the stomach muscles)','Pelvic pain','Pain in other areas of your body'],
                'op_ar' => ['','','','','','','','','','','','','','','',''],
            ],
            [
                'q_en' => 'Would you like to inspire others\nby sharing your progress pictures\n& story on social media?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'single_choice',
                'op_en' => ['Yes','No'],
                'op_ar' => ['',''],
            ],
            [
                'q_en' => 'Gender?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'single_choice',
                'op_en' => ['Male','Female','Other'],
                'op_ar' => ['','',''],
            ],
            [   // tag
                'q_en' => 'Do you feel?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'tag' => 'ANSWER',
                'q_type' => 'multiple_choice',
                'op_en' => ['Constant fatigue','Depression','Anxiety','Low libido','Low motivation'],
                'op_ar' => ['','','','',''],
            ],
            [
                'q_en' => 'Have you been on any medication in the past for a long period of time if so please explain below:',
                'q_ar' => '',
                'note_en' => 'Write "No" if you have not been on any medication in the past',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'Are you taking any medication ? If so please list below:',
                'q_ar' => '',
                'note_en' => 'Write "No" if you are not taking any medicine',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'Do you take any supplements if so please list them below:',
                'q_ar' => '',
                'note_en' => 'Write "No" if you are not taking any supplement',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'Please check the box below that explains your fitness background the most:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['I never exercised before','I use to exercise sometimes but havenʼt in a month or more','I didnʼt exercise in more than a year and more','I exercise 3 days a week','Im very active and exercise almost everyday'],
                'op_ar' => ['','','','',''],
            ],
            [
                'q_en' => 'What kind of exercise do you do:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['I walk everyday','I run or do other forms of cardio like cycling , kayaking , hiking etc','I do resistance training at home with bands or dumbbells','I exercise at the gym using machines & dumbbells','I do classes like circuit training','I do class like body pump etc','Yoga','Pilates'],
                'op_ar' => ['','','','','','','',''],
            ],
            [
                'q_en' => 'What would you like to gain out of the program the most?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['Transform my body','Transform my lifestyle and become fitter','Change my eating habits','Get motivated','Learn how to exercise properly','Join like minded community group','Improve my overall well being & mindset'],
                'op_ar' => ['','','','','','',''],
            ],
            [
                'q_en' => 'What are your fitness goals regarding your body image?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['Maintain my body weight & improve over all muscle/fat ratio','Become lean so you can see more shape of the muscles','Get abs','Burn higher amount of body fat & transform','I donʼt like my muscles showing I just want to loose weight and look like Im healthy and exercise'],
                'op_ar' => ['','','','',''],
            ],
            [
                'q_en' => 'What are your fitness goals?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['Want to become stronger','Want to lift heavier weights','Want a stronger core','Want to exercise pain free','Want to learn how to perform the exercises correctly','Want to have more stamina and endurance','Want to be challenged with different exercises'],
                'op_ar' => ['','','','','','',''],
            ],
            [
                'q_en' => 'What kind of challenges have you been dealing with?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['Nothing much I love being healthy and exercising','I donʼt enjoy exercising','I love eating fast food','I donʼt enjoy cooking','I donʼt have time to prep my meals','I donʼt have time to exercise','I donʼt know how to exercise','I donʼt know how to cook','I havenʼt seen results in the body','I am my worst critique','I suffer with eating disorders','I suffer with self confidence','Im always tired','Im always sore','lack of motivation','My friends/surroundings donʼt share same interest','I donʼt have family support','I never met a good coach I can trust'],
                'op_ar' => ['','','','','','','','','','','','','','','','','',''],
            ],
            [
                'q_en' => 'Please list below everything you eat in a Breakfast please be detailed:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'Please list below everything you eat in a Lunch please be detailed:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'Please list below everything you eat in a Dinner please be detailed:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'What are your favorite foods/meals you enjoy the most?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'What are your biggest challenges when it comes to food?',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'multiple_choice',
                'op_en' => ['Family pressure','Wine/alcohol','Eating fast food everyday','Eating small portions','Eating frequently','Eating large portions','Eating when not hungry','Craving unhealthy foods','Emotional stress eating'],
                'op_ar' => ['','','','','','','','',''],
            ],
            [ //tag on answer
                'q_en' => 'Have you been injured your:',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'tag' => 'ANSWER',
                'q_type' => 'multiple_choice',
                'op_en' => ['Shoulder','Knee','Lower back','Neck','Wrist','Elbow','Chest'],
                'op_ar' => ['','','','','','',''],
            ],
            [
                'q_en' => 'If you are dealing with any specific pain or injury please explain below',
                'q_ar' => '',
                'note_en' => 'Write "No" if you are not dealing with any injury or pain',
                'note_ar' => '',
                'q_type' => 'fill_blank',
                'op_en' => [],
                'op_ar' => [],
            ],
            [
                'q_en' => 'Weight',
                'q_ar' => '',
                'note_en' => '',
                'note_ar' => '',
                'q_type' => 'select',
                'op_en' => [],
                'op_ar' => [],
            ],
        ];
        $order = 1;
        foreach ($questions as $que) {
            $q = new Question();
            $q->question_en = $que['q_en'];
            $q->question_ar = $que['q_ar'];
            $q->note_en = $que['note_en'];
            $q->note_ar = $que['note_ar'];
            $q->type = $que['q_type'];
            $q->options_en = json_encode($que['op_en']);
            $q->options_ar = json_encode($que['op_ar']);
            $q->order = $order;
            if(isset($que['tag']))
            $q->tag = $que['tag'];
            $q->save();
            $order++;
        }
        return;
    }
}
