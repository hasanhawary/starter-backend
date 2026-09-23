<?php

namespace Modules\Showcase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Showcase\app\Models\ShowcaseTag;

/**
 * @extends Factory<ShowcaseTag>
 */
class ShowcaseTagFactory extends Factory
{
    protected $model = ShowcaseTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->word();

        return [
            'name' => ['en' => ucfirst($word), 'ar' => 'وسم '.$word],
            'slug' => Str::slug($word.'-'.$this->faker->unique()->randomNumber(4)),
            'color' => $this->faker->hexColor(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
