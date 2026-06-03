<?php

namespace Database\Factories;

use App\Enum\User\UserGenderEnum;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $domain = Str::snake(brandName());
        $countryId = Country::query()->first()?->id ?? Country::factory()->create()->id;

        return [
            'name' => "$firstName $lastName",
            'email' => strtolower(
                "$firstName.$lastName.".$this->faker->unique()->numberBetween(1, 9999)
            )."@{$domain}.com",
            'phone' => '1234567890',
            'password' => 123456,
            'phone_code_id' => $countryId,
            'gender' => $this->faker->randomElement([
                UserGenderEnum::Male->value,
                UserGenderEnum::Female->value,
            ]),
            'is_active' => true,
        ];
    }
}
