<?php

namespace Modules\Notification\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

class NotificationEvent extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    public bool $inPermission = true;

    public array $specialOperations = [];

    protected $fillable = [
        'system_event_id',
        'name',
        'model_type',
        'sent_types',
        'is_reminder',
        'recipientable_id',
        'recipientable_type',
        'type',
    ];

    protected $casts = [
        'sent_types' => 'array',
        'is_reminder' => 'boolean',
        'name' => 'array',
    ];

    public function syncVariables(mixed $variables): self
    {
        $this->variables()->sync($variables);

        return $this;
    }

    public function syncRecipients(array $handleRecipients): static
    {
        $this->notificationRecipients()->createMany($handleRecipients);

        return $this;
    }

    public function getRecipients(?Model $model = null): Collection
    {
        $usersIds = collect();

        $recipients = $this->notificationRecipients()->with('recipientable')->get();

        foreach ($recipients as $recipient) {
            $usersIds = $usersIds->merge(match ($recipient->type) {
                'user' => [$recipient->recipientable_id],
                'role' => $recipient->recipientable?->roleUsers()->pluck('id')->all() ?? [],
                'relation' => $recipient->recipientable?->getRelatedUsersIds($model) ,
                default => [],
            });
        }

        return User::whereIsActive(true)
            ->whereIn('id', $usersIds->filter()->unique()->values())
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function systemEvent(): BelongsTo
    {
        return $this->belongsTo(SystemEvent::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'event_channel', 'event_id', 'channel_id');
    }

    public function notificationRecipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function remindersSetting(): HasMany
    {
        return $this->hasMany(RemindersSetting::class);
    }

    public function scheduleEvents(): HasMany
    {
        return $this->hasMany(ScheduleEvent::class);
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function variables(): MorphToMany
    {
        return $this->morphToMany(
            Variable::class,
            'variableable',
            'variable_assignments'
        )->withTimestamps();
    }
}
