<?php

namespace Modules\Showcase\app\Models;

use App\Filters\Global\OrderByFilter;
use App\Helpers\DelimiterParamValue;
use App\Models\BaseModel;
use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\HasDeletedBy;
use App\Trait\Global\LogsActivityOptions;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Enum\ShowcasePriorityEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Scopes\ShowcaseScopes;
use Modules\Showcase\app\Trait\HasShowcaseNotes;
use Modules\Showcase\app\Trait\HasShowcasePins;
use Modules\Showcase\Database\Factories\ShowcaseFactory;
use Spatie\Translatable\HasTranslations;

/**
 * The module's main record. It deliberately carries one of every shape this
 * starter supports: translatable JSON columns, a media column, three enum
 * casts, soft deletes with creator/deleter tracking, an activity log, per-user
 * pins, a polymorphic note thread, and belongsTo/hasMany/belongsToMany
 * relations.
 */
class Showcase extends BaseModel
{
    use CreatedByObserver, HasDeletedBy, HasFactory, HasShowcaseNotes, HasShowcasePins, HasTranslations, LogsActivityOptions, ShowcaseScopes, SoftDeletes;

    public array $translatable = ['name', 'description'];

    public bool $inPermission = true;

    public array $basicOperations = ['create', 'update', 'delete'];

    public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active', 'publish', 'archive', 'pin'];

    /**
     * Uploaded paths and free-form payloads are noise in an audit trail.
     *
     * @var array<int, string>
     */
    public array $logExceptAttributes = ['cover', 'metadata', 'views_count'];

    protected $fillable = [
        'showcase_category_id',
        'reference',
        'name',
        'description',
        'cover',
        'status',
        'priority',
        'visibility',
        'owner_id',
        'rating',
        'views_count',
        'sort_order',
        'metadata',
        'published_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    /**
     * Mirrors the column defaults in the migration, so a freshly created record
     * carries its status before it is re-read — the Resource, the observer and
     * `isEditable()` all rely on it being there.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ShowcaseStatusEnum::Draft->value,
        'priority' => ShowcasePriorityEnum::Medium->value,
        'visibility' => ShowcaseVisibilityEnum::Internal->value,
        'is_active' => true,
        'views_count' => 0,
        'sort_order' => 0,
    ];

    protected $casts = [
        'status' => ShowcaseStatusEnum::class,
        'priority' => ShowcasePriorityEnum::class,
        'visibility' => ShowcaseVisibilityEnum::class,
        'metadata' => 'array',
        'rating' => 'decimal:2',
        'views_count' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function setCoverAttribute($value): void
    {
        $path = Media::replace($this->attributes['cover'] ?? null)->upload($value, 'showcase/covers');
        $this->attributes['cover'] = $path;
    }

    public function cover(): Attribute
    {
        return Attribute::make(get: static fn ($value) => Media::url($value));
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShowcaseCategory::class, 'showcase_category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ShowcaseTag::class, 'showcase_showcase_tag')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryTag(): BelongsToMany
    {
        return $this->tags()->wherePivot('is_primary', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relation synchronization methods
    |--------------------------------------------------------------------------
    */
    /**
     * Replace the record's tag links, marking one of them primary.
     *
     * An empty list leaves the relation untouched: the base Form Request
     * normalizes an empty array to null, so "sent nothing" and "cleared
     * everything" arrive as the same payload and the safer reading wins.
     *
     * @param  array<int, int|string>  $tagIds
     */
    public function syncTags(array $tagIds = [], int|string|null $primaryTagId = null): void
    {
        if ($tagIds === []) {
            return;
        }

        $this->tags()->sync(
            collect($tagIds)
                ->mapWithKeys(fn ($tagId) => [(int) $tagId => ['is_primary' => (int) $tagId === (int) $primaryTagId]])
                ->all()
        );
    }

    /**
     * Open the note thread with the note that came in with the payload, if any.
     */
    public function syncOpeningNote(?string $body = null): void
    {
        if (blank($body)) {
            return;
        }

        $this->addNote($body, ShowcaseNoteTypeEnum::Comment->value);
    }

    /**
     * Record a workflow transition on the record's timeline.
     *
     * The message is stored delimiter-encoded (`key|actor=…|enum_from=…`) so the
     * status labels are translated when the entry is read, not when it is
     * written. Called by the `Tools/Status` strategies, never by a controller.
     */
    public function log(?ShowcaseStatusEnum $fromStatus, ShowcaseStatusEnum $toStatus, ?string $notes = null): ShowcaseNote
    {
        $params = ['actor' => DelimiterParamValue::plain(auth()->user()?->name ?? __('showcase::logs.system'))];

        if ($fromStatus) {
            $params['from'] = DelimiterParamValue::enum($fromStatus);
        }

        $params['to'] = DelimiterParamValue::enum($toStatus);

        return $this->notes()->create([
            'type' => ShowcaseNoteTypeEnum::StatusChange->value,
            'body' => buildDelimiterMessage($fromStatus ? 'status_changed' : 'status_set', $params),
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'author_id' => auth()->id(),
            'is_pinned' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper methods
    |--------------------------------------------------------------------------
    */
    /**
     * Sort keys the listing exposes that no column holds, read by
     * {@see OrderByFilter}.
     *
     * @return array<string, string>
     */
    public function sortableExpressions(): array
    {
        return [
            'remaining_days' => 'DATEDIFF(showcases.expires_at, NOW())',
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this->status?->value, ShowcaseStatusEnum::editableValues(), true);
    }

    /**
     * Days left before the record expires; null when it never does.
     */
    public function remainingDays(): ?int
    {
        return $this->expires_at ? (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */
    protected static function newFactory(): Factory
    {
        return ShowcaseFactory::new();
    }
}
