<?php

namespace App\Http\Resources\Global\Setting;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingGroupResource extends JsonResource
{
    /**
     * Organize collection into nested structure for frontend
     */
    public static function organizeNested($collection)
    {
        return collect($collection)
            ->groupBy(fn ($item) => explode('.', $item->group)[0])
            ->map(function ($groupItems, $groupKey) {
                $groupItems = collect($groupItems);

                $tabs = $groupItems
                    ->filter(fn ($item) => str_contains($item->group, '.'))
                    ->groupBy(fn ($item) => explode('.', $item->group)[1])
                    ->map(function ($tabItems, $tabKey) {
                        return [
                            'label' => $tabKey,
                            'display_label' => resolveTrans('settings_trans.'.$tabKey),
                            'nested' => null,
                            'items' => SettingResource::collection($tabItems),
                        ];
                    })
                    ->values();

                // Items directly under group (no tab)
                $items = $groupItems->filter(fn ($item) => ! str_contains($item->group, '.'));

                return [
                    'label' => $groupKey,
                    'display_label' => resolveTrans('settings_trans.'.$groupKey),
                    'nested' => $tabs->isNotEmpty() ? $tabs : null,
                    'items' => $items->isNotEmpty() ? SettingResource::collection($items) : null,
                ];
            })
            ->values();
    }
}
