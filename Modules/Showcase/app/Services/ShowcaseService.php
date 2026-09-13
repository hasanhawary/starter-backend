<?php

namespace Modules\Showcase\app\Services;

use App\Services\User\UserService;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Tools\Facades\Notification;
use Modules\Showcase\app\Models\Showcase;

/**
 * The module's single service. The controller opens the transaction and lines
 * up the steps; each method here owns one write and its side effects.
 *
 * Relation writes are not here: `syncTags()` and `syncOpeningNote()` live on
 * {@see Showcase}, the model that owns those relations. Status transitions are
 * not here either: they belong to the strategies under `Tools/Status`, and the
 * activation announcement belongs to the observer that sees the column change.
 *
 * Signature order follows {@see UserService}: the model first where there is
 * one, then the validated payload.
 */
class ShowcaseService
{
    /**
     * Create a showcase record and announce it.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Showcase
    {
        $showcase = Showcase::create($this->attributesFrom($data));

        Notification::send(SystemEventSlugEnum::CreateShowcase->value, $showcase);

        return $showcase;
    }

    /**
     * Apply a change to an existing showcase record and announce it.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Showcase $showcase, array $data): Showcase
    {
        $showcase->update($this->attributesFrom($data));

        Notification::send(SystemEventSlugEnum::UpdateShowcase->value, $showcase->refresh());

        return $showcase;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    /**
     * The record's own columns, with the keys that belong to a relation or to a
     * separate step stripped out.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        return collect($data)->except(['tag_ids', 'primary_tag_id', 'note'])->all();
    }
}
