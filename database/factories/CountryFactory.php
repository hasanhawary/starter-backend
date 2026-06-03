<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->country(),
                'ar' => $this->faker->country(),
            ],
            'nationality' => [
                'en' => $this->faker->word(),
                'ar' => $this->faker->word(),
            ],
            'code' => strtoupper($this->faker->unique()->lexify('??')),
            'phone_code' => '+'.$this->faker->numberBetween(1, 999),
            'phone_length' => $this->faker->numberBetween(7, 12),
            'is_active' => true,
        ];
    }
}
