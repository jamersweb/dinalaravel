<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition()
    {
        $types = ['multiple_choice', 'single_choice', 'fill_blank'];
        $type = $this->faker->randomElement($types);

        $optionsEn = [];
        $optionsAr = [];

        if ($type !== 'fill_blank') {
            $optionCount = $this->faker->numberBetween(2, 5);
            for ($i = 0; $i < $optionCount; $i++) {
                $optionsEn[] = $this->faker->word();
                $optionsAr[] = $this->faker->word();
            }
        }

        return [
            'question_en' => $this->faker->sentence() . '?',
            'question_ar' => $this->faker->sentence() . '؟',
            'note_en' => $this->faker->optional()->sentence(),
            'note_ar' => $this->faker->optional()->sentence(),
            'type' => $type,
            'tag' => $this->faker->randomElement(['ANSWER', 'NO']),
            'options_en' => json_encode($optionsEn),
            'options_ar' => json_encode($optionsAr),
            'order' => $this->faker->numberBetween(1, 100),
            'status' => 1,
        ];
    }

    public function multipleChoice()
    {
        return $this->state(function (array $attributes) {
            $options = ['Option 1', 'Option 2', 'Option 3', 'Option 4'];
            return [
                'type' => 'multiple_choice',
                'options_en' => json_encode($options),
                'options_ar' => json_encode($options),
            ];
        });
    }

    public function singleChoice()
    {
        return $this->state(function (array $attributes) {
            $options = ['Yes', 'No', 'Maybe'];
            return [
                'type' => 'single_choice',
                'options_en' => json_encode($options),
                'options_ar' => json_encode($options),
            ];
        });
    }

    public function fillBlank()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'fill_blank',
                'options_en' => json_encode([]),
                'options_ar' => json_encode([]),
            ];
        });
    }

    public function withTag()
    {
        return $this->state(function (array $attributes) {
            return [
                'tag' => 'ANSWER',
            ];
        });
    }
}

