<?php

namespace Database\Factories;

use App\Models\PosturePicture;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PosturePictureFactory extends Factory
{
    protected $model = PosturePicture::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'front_picture' => 'test_front_' . time() . '.jpg',
            'back_picture' => 'test_back_' . time() . '.jpg',
            'side_picture' => 'test_side_' . time() . '.jpg',
            'privacy_setting' => 'confidential',
            'next_upload_date' => Carbon::today()->addWeeks(3),
        ];
    }

    public function approvedForSocial()
    {
        return $this->state(function (array $attributes) {
            return [
                'privacy_setting' => 'approved_for_social',
            ];
        });
    }
}

