<?php

namespace Modules\Showcase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseNote;

/**
 * @extends Factory<ShowcaseNote>
 */
class ShowcaseNoteFactory extends Factory
{
    protected $model = ShowcaseNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notable_id' => Showcase::factory(),
            'notable_type' => Showcase::class,
            'type' => ShowcaseNoteTypeEnum::default(),
            'body' => $this->faker->paragraph(),
            'is_pinned' => false,
        ];
    }

    public function ofType(ShowcaseNoteTypeEnum $type): static
    {
        return $this->state(fn () => ['type' => $type->value]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }
}
