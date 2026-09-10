<?php

namespace Modules\Notification\app\Tools\Channels;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\NotificationLog;
use Modules\Notification\app\Models\NotificationTemplate;
use Modules\Notification\app\Models\NotificationVerifiableDate;
use Throwable;

/**
 * Base class for all notification channels (SMS, Email, Push, etc.)
 * Handles common operations like variable replacement and logging.
 */
abstract class BaseChannel
{
    protected array $config;

    protected array $title = [];

    protected array $body = [];

    protected ?Model $model = null;

    protected NotificationEvent $notificationEvent;

    protected Collection $users;

    public function __construct(protected string $channel)
    {
        $this->config = config("notification.notifications.channels.{$this->channel}");
    }

    /**
     * Send notification to a single user (must be implemented by child classes)
     *
     * @param  mixed  $user
     */
    abstract protected function sendToUser(User $user, NotificationTemplate $template): void;

    /**
     * Send notification to all users
     */
    public function send(NotificationTemplate $template, NotificationEvent $notificationEvent): bool
    {
        $this->notificationEvent = $notificationEvent;

        foreach ($this->users as $user) {
            $this->sendToUser($user, $template);
        }

        return true;
    }

    /**
     * Check if channel is enabled in config
     *
     * @throws Throwable
     */
    public function checkEnabled(): bool
    {
        throw_unless(
            $this->config['enabled'],
            \RuntimeException::class,
            "{$this->channel} channel is disabled"
        );

        return true;
    }

    /**
     * Set users to receive notifications
     */
    public function setData(Collection $users, ?Model $model): self
    {
        $this->users = $users;
        $this->model = $model;

        return $this;
    }

    /**
     * Replace {{variable}} placeholders with pre-resolved values.
     *
     * Values are resolved once per event (see VariableResolver) and reused for
     * every template, so this method only performs the string replacement.
     *
     * @param  array<string, string>  $replacements  Map of placeholder => value
     */
    public function replaceVariables(NotificationTemplate $template, array $replacements): self
    {
        // Read the full translations array (all locales), not the single
        // current-locale string HasTranslations would otherwise return.
        $this->title = $this->normalizeTranslations($template, 'title');
        $this->body = $this->normalizeTranslations($template, 'body');

        // Replace placeholders in every available language.
        foreach ($this->title as $lang => $text) {
            foreach ($replacements as $placeholder => $value) {
                $replacementString = is_array($value)
                    ? ($value[$lang] ?? $value['ar'] ?? $value['en'] ?? reset($value) ?? '')
                    : (string) $value;
                $this->title[$lang] = str_replace($placeholder, $replacementString, (string) $this->title[$lang]);
            }
        }

        foreach ($this->body as $lang => $text) {
            foreach ($replacements as $placeholder => $value) {
                $replacementString = is_array($value)
                    ? ($value[$lang] ?? $value['ar'] ?? $value['en'] ?? reset($value) ?? '')
                    : (string) $value;
                $this->body[$lang] = str_replace($placeholder, $replacementString, (string) $this->body[$lang]);
            }
        }

        return $this;
    }

    /**
     * The locale to address a user in: their own preference, else the app's.
     */
    protected function localeFor(User $user): string
    {
        return $user->preferred_language ?: app()->getLocale();
    }

    /**
     * The title, in the reader's language.
     */
    protected function titleFor(User $user): string
    {
        return $this->inReadersLanguage($this->title, $user);
    }

    /**
     * The body, in the reader's language.
     */
    protected function bodyFor(User $user): string
    {
        return $this->inReadersLanguage($this->body, $user);
    }

    /**
     * Pick one locale out of the copy replaceVariables() resolved for all of them.
     *
     * A user may prefer a language the template was never written in, so the
     * choice falls back through Arabic, then English, then whatever the template
     * does carry — an unexpected preference costs the reader a translation, never
     * the whole message.
     *
     * @param  array<string, string>  $translations
     */
    private function inReadersLanguage(array $translations, User $user): string
    {
        $value = $translations[$this->localeFor($user)]
            ?? $translations['ar']
            ?? $translations['en']
            ?? reset($translations);

        return is_string($value) ? $value : '';
    }

    /**
     * Get a template field as a full locale => text map.
     *
     * @return array<string, string>
     */
    private function normalizeTranslations(NotificationTemplate $template, string $field): array
    {
        $value = method_exists($template, 'getTranslations')
            ? $template->getTranslations($field)
            : $template->{$field};

        if (is_array($value) && $value !== []) {
            return $value;
        }

        // Plain (non-translatable) string → apply to both supported locales.
        $value = is_array($value) ? '' : (string) $value;

        return ['ar' => $value, 'en' => $value];
    }

    /**
     * Resolve a date/time value from a notification_verifiable_dates id.
     *
     * The template's `date_column` holds the id of a NotificationVerifiableDate
     * row, which points to the actual date attribute (`access_key`) on the model
     * — optionally living on a related model (`relation`).
     */
    protected function resolveDateTime(mixed $dateColumn): mixed
    {
        if (empty($dateColumn) || ! isset($this->model)) {
            return null;
        }

        $verifiable = NotificationVerifiableDate::find($dateColumn);

        if (! $verifiable || empty($verifiable->access_key)) {
            return null;
        }

        $source = $this->model;

        if (! empty($verifiable->relation)) {
            $related = $this->model->{$verifiable->relation} ?? null;
            $source = $related instanceof Collection ? $related->first() : $related;
        }

        return $source?->{$verifiable->access_key};
    }

    /**
     * Create notification log entry.
     *
     * Every channel writes its own log, right after the delivery it performed
     * synchronously, so the status is the real outcome. `sent_at` marks the
     * moment the message went out and therefore belongs to a sent one only.
     *
     * @param  mixed  $user
     */
    protected function createLog(
        User $user,
        NotificationTemplate $template,
        string $status = 'sent',
        ?string $errorMessage = null
    ): NotificationLog {
        return $user->notificationLogs()->create([
            'notification_event_id' => $this->notificationEvent->id,
            'notification_template_id' => $template->id,
            'channel' => $this->channel,
            'payload' => [
                'title' => $this->title,
                'body' => $this->body,
            ],
            'error_message' => $errorMessage,
            'retry_count' => 1,
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
