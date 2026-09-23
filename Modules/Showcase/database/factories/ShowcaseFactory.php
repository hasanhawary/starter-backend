<?php

namespace Modules\Showcase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Showcase\app\Enum\ShowcasePriorityEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;

/**
 * @extends Factory<Showcase>
 */
class ShowcaseFactory extends Factory
{
    protected $model = Showcase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->words(3, true);

        return [
            'showcase_category_id' => ShowcaseCategory::factory(),
            'name' => ['en' => ucwords($word), 'ar' => 'عرض '.$word],
            'description' => ['en' => $this->faker->paragraph(), 'ar' => $this->faker->paragraph()],
            'status' => ShowcaseStatusEnum::default(),
            'priority' => ShowcasePriorityEnum::default(),
            'visibility' => ShowcaseVisibilityEnum::default(),
            'rating' => $this->faker->randomFloat(2, 0, 5),
            'views_count' => $this->faker->numberBetween(0, 500),
            'sort_order' => $this->faker->numberBetween(0, 20),
            'metadata' => ['source' => 'factory'],
            'expires_at' => now()->addDays($this->faker->numberBetween(1, 60)),
            'is_active' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ShowcaseStatusEnum::Published->value,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => ShowcaseStatusEnum::Archived->value]);
    }

    public function critical(): static
    {
        return $this->state(fn () => ['priority' => ShowcasePriorityEnum::Critical->value]);
    }

    public function visibility(ShowcaseVisibilityEnum $visibility): static
    {
        return $this->state(fn () => ['visibility' => $visibility->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function ownedBy(int $userId): static
    {
        return $this->state(fn () => ['owner_id' => $userId]);
    }
}
