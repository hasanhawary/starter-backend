<?php

namespace Modules\Showcase\app\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Trait\HasShowcaseNotes;
use Modules\Showcase\Database\Factories\ShowcaseNoteFactory;

/**
 * A note written against any model using
 * {@see HasShowcaseNotes} — the morph side of the
 * module's relations.
 */
class ShowcaseNote extends BaseModel
{
    use HasFactory, SoftDeletes;

    /** Notes are managed through their parent record, so they get no permissions of their own. */
    public bool $inPermission = false;

    protected $fillable = [
        'notable_id',
        'notable_type',
        'type',
        'body',
        'from_status',
        'to_status',
        'author_id',
        'is_pinned',
    ];

    protected $casts = [
        'type' => ShowcaseNoteTypeEnum::class,
        'from_status' => ShowcaseStatusEnum::class,
        'to_status' => ShowcaseStatusEnum::class,
        'is_pinned' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function scopeOfType(Builder $query, string|array $type): Builder
    {
        return $query->whereIn('type', resolveArray($type));
    }

    /**
     * The transition entries only — the record's workflow trail.
     */
    public function scopeTransitions(Builder $query): Builder
    {
        return $query->where('type', ShowcaseNoteTypeEnum::StatusChange->value);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */
    protected static function newFactory(): Factory
    {
        return ShowcaseNoteFactory::new();
    }
}
