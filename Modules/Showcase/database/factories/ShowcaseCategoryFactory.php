<?php

namespace Modules\Showcase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Showcase\app\Models\ShowcaseCategory;

/**
 * @extends Factory<ShowcaseCategory>
 */
class ShowcaseCategoryFactory extends Factory
{
    protected $model = ShowcaseCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->words(2, true);

        return [
            'name' => ['en' => ucwords($word), 'ar' => 'تصنيف '.$word],
            'description' => ['en' => $this->faker->sentence(), 'ar' => $this->faker->sentence()],
            'code' => strtoupper($this->faker->unique()->bothify('CAT-###')),
            'parent_id' => null,
            'sort_order' => $this->faker->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * A category nested under another one.
     */
    public function childOf(ShowcaseCategory $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
