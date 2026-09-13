<?php

namespace Modules\Showcase\app\Trait;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Modules\Showcase\app\Models\ShowcaseNote;

/**
 * Adds the polymorphic note thread to any model that can be annotated. Both
 * {@see Showcase} and
 * {@see ShowcaseCategory} use it, which is what
 * makes `showcase_notes` a genuinely shared morph target.
 */
trait HasShowcaseNotes
{
    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function notes(): MorphMany
    {
        return $this->morphMany(ShowcaseNote::class, 'notable')->latest('id');
    }

    public function pinnedNotes(): MorphMany
    {
        return $this->notes()->where('is_pinned', true);
    }

    public function latestNote(): MorphOne
    {
        return $this->morphOne(ShowcaseNote::class, 'notable')->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    /**
     * Append a note authored by the current user.
     */
    public function addNote(string $body, ?string $type = null, ?int $authorId = null): ShowcaseNote
    {
        return $this->notes()->create([
            'body' => $body,
            'type' => $type ?? ShowcaseNoteTypeEnum::default(),
            'author_id' => $authorId ?? auth()->id(),
        ]);
    }
}
