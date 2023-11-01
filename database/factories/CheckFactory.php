<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'response_status' => rand(200, 204),
            'response_content' => $this->faker->randomHtml(),
            'elapsed_time' => rand(1, 100),
        ];
    }
}
