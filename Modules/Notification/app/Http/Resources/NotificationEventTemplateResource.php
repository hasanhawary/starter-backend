<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Models\Variable;

class NotificationEventTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        $channel = $this->channel?->value;

        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'translated_title' => $this->title,
            'replaced_title' => $this->replaceVariables($this->title),
            'body' => $this->getTranslations('body'),
            'translated_body' => $this->body,
            'replaced_body' => $this->replaceVariables($this->body),
            'channel' => $channel,
            'display_channel' => NotificationChannelEnum::resolve($channel),
            'date_column' => $this->date_column,
            'created_at' => $this->created_at,
        ];
    }

    private function replaceVariables(?string $text): ?string
    {
        if (! $text) {
            return $text;
        }

        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        $ids = array_unique(array_map('intval', $matches[1]));

        if (empty($ids)) {
            return $text;
        }

        $locale = app()->getLocale();
        $variables = Variable::whereIn('id', $ids)->get()->keyBy('id');

        return preg_replace_callback('/\{\{(\d+)\}\}/', function (array $matches) use ($variables, $locale): string {
            $variable = $variables->get((int) $matches[1]);

            return $variable ? '{{'.$variable->getTranslation('name', $locale, false).'}}' : $matches[0];
        }, $text);
    }
}
