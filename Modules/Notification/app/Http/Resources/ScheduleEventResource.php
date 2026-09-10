<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\Tools\Services\Notification\VariableResolver;

class ScheduleEventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'translated_title' => $this->getTranslations('title'),
            'body' => $this->body,
            'translated_body' => $this->getTranslations('body'),
            'date_time' => $this->date_time,
            'date' => $this->date_time,
            'type' => $this->type?->value,
            'display_type' => $this->source_type ? resolveTrans(class_basename($this->source_type)) : null,
            'status' => $this->status,
            'source' => $this->whenLoaded('source', fn () => new ScheduleEventSourceResource($this->source)),
            'variables' => $this->when(
                $this->relationLoaded('source')
                && $this->source
                && $this->relationLoaded('notificationEvent')
                && $this->notificationEvent?->relationLoaded('variables'),
                fn () => $this->resolvedVariables()
            ),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolvedVariables(): array
    {
        $resolver = app(VariableResolver::class);

        return $this->notificationEvent->variables
            ->map(fn ($variable): array => [
                'id' => $variable->id,
                'name' => $variable->name,
                'translation_name' => $variable->getTranslations('name'),
                'access_key' => $variable->access_key,
                'type' => $variable->type,
                'model_type' => $variable->model_type,
                'value' => $resolver->resolveValue($variable, $this->source),
            ])
            ->values()
            ->all();
    }
}
