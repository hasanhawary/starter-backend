<?php

namespace Modules\Notification\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Notification\Tools\Services\Notification\ActiveRelationFilter;
use Spatie\Translatable\HasTranslations;

class NotificationReceiver extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'module',
        'type',
        'relation',
        'name',
        'note',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRelatedUsersIds(Model $model): array
    {
        if (! $this->is_active) {
            return [];
        }

        return match ($this->type) {
            'self' => $model instanceof User ? [$model->getKey()] : [],
            'relation' => $this->resolveRelationUsers($model, $this->relation),
            default => [],
        };
    }

    private function resolveRelationUsers(Model $model, ?string $relationPath): array
    {
        if (! $relationPath) {
            return [];
        }

        $items = collect([$model]);

        // A deactivated assignment (an unassigned team member, a replaced main user)
        // keeps its row, so each hop drops the records that are switched off before
        // walking further.
        foreach (explode('.', $relationPath) as $segment) {
            $items = ActiveRelationFilter::apply(
                $items
                    ->filter()
                    ->flatMap(function ($item) use ($segment) {
                        $value = data_get($item, $segment);

                        if ($value instanceof Collection) {
                            return $value;
                        }

                        if (is_iterable($value)) {
                            return collect($value);
                        }

                        return $value ? [$value] : [];
                    })
                    ->filter()
            );
        }

        return $items
            ->filter(fn ($item) => $item instanceof User || Str::endsWith($item::class, '\\User'))
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
