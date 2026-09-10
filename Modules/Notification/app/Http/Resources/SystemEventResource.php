<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\app\Enum\SystemEventModuleEnum;

class SystemEventResource extends JsonResource
{
    public function toArray($request): array
    {
        $eventSlug = $this->event_slug?->value;
        $module = $this->module?->value;
        return [
            'id' => $this->id,
            'name' => $this->name ?? trans('notification::api.'.$eventSlug),
            'translation_name' => $this->getTranslations('name'),
            'module' => $module,
            'module_name' => SystemEventModuleEnum::resolve($module),
            'model_type' => $this->model_type,
            'event_slug' => $eventSlug,
            //            'is_active' => $this->is_active,
            //            'variables' => $this->whenLoaded('variables', VariableResource::collection($this->variables), []),
            'notification_events' => $this->whenLoaded('notificationEvents', NotificationEventResource::collection($this->notificationEvents), []),
            'created_at' => $this->created_at,
        ];
    }
}
