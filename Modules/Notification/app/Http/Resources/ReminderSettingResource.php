<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Enum\ReminderSettingTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingUnitsEnum;

class ReminderSettingResource extends JsonResource
{
    /**
     * Groups a flat collection of reminder rows by channel and returns the nested fields[] structure.
     */
    public static function groupCollection(Collection $reminderSettings): array
    {
        $reminderSettings->loadMissing('verifiableDate');

        return $reminderSettings
            ->groupBy(fn ($item) => $item->channel?->value ?? '')
            ->values()
            ->map(function ($group) {
                $channel = $group->first()->channel?->value;

                return [
                    'channel' => $channel,
                    'display_channel' => NotificationChannelEnum::resolve($channel),
                    'fields' => $group->map(fn ($item) => [
                        'id' => $item->id,
                        'reminder_based_on_column' => $item->reminder_based_on_column,
                        'offset_type' => $item->offset_type?->value,
                        'display_offset_type' => ReminderSettingTypesEnum::resolve($item->offset_type?->value),
                        'offset_value' => $item->offset_value,
                        'offset_unit' => $item->offset_unit?->value,
                        'display_offset_unit' => ReminderSettingUnitsEnum::resolve($item->offset_unit?->value),
                    ])->values(),
                ];
            })
            ->values()
            ->all();
    }

    public function toArray($request): array
    {
        $channel = $this->channel?->value;

        return [
            'id' => $this->id,
            'channel' => $channel,
            'display_channel' => NotificationChannelEnum::resolve($channel),
            'reminder_based_on_column' => $this->whenLoaded('verifiableDate', fn () => new NotificationVerifiableDateResource($this->verifiableDate), ['id' => $this->reminder_based_on_column]),
            'offset_type' => $this->offset_type?->value,
            'display_offset_type' => ReminderSettingTypesEnum::resolve($this->offset_type?->value),
            'offset_value' => $this->offset_value,
            'offset_unit' => $this->offset_unit?->value,
            'display_offset_unit' => ReminderSettingUnitsEnum::resolve($this->offset_unit?->value),
        ];
    }
}
