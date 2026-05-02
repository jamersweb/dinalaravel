<?php

namespace Database\Seeders;

use App\Models\AutomatedMessage;
use Illuminate\Database\Seeder;

class AutomatedMessagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sendOn = ['on signup','7th day after signup','one month after signup','on subscribing first program','clients missing workouts'];
        if(AutomatedMessage::count() !== count($sendOn)){
            AutomatedMessage::truncate();
            foreach ($sendOn as $son) {
                $msg = new AutomatedMessage();
                $msg->type = 'text';
                $msg->content = '--write message--';
                $msg->content_ar = '--write message--';
                $msg->send_on = $son;
                $msg->status = 0;
                $msg->save();
            }
        }
    }
}
