<?php

namespace Modules\Notification\database\seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Enum\NotificationEventTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingUnitsEnum;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\NotificationReceiver;
use Modules\Notification\app\Models\NotificationVerifiableDate;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Models\Variable;

/**
 * Gives every system event exactly one notification event, carrying one template
 * per channel, written as a realistic message in each locale: the event name,
 * the record's identifying reference, and a short block of labelled details drawn
 * from the event's own variables — never a raw variable dump, and never a locale
 * mix (the Arabic template is entirely Arabic, the English one entirely English).
 *
 * The variables of an event are the ones SystemEventVariableSeeder allows for it
 * — the module/model variables minus the ones that cannot hold a value yet when
 * the event fires — so the seeded templates always follow the current variable set.
 *
 * An event that carries written copy in `data/event_templates.json` uses it
 * instead of the generated message, per channel: the written body names
 * the variables that action actually resolves, labelled for the reader. Any
 * channel the file leaves out still falls back to the generated template, so
 * partial copy is allowed.
 *
 * Idempotent: re-running rewrites titles, bodies and variable links in place,
 * keeping template ids (and the notification/log rows pointing at them) intact,
 * and adds only the recipients and reminder settings that are missing.
 */
class DefaultNotificationEventTemplatesSeeder extends Seeder
{
    /**
     * Every channel gets a template, so a single trigger can be verified end to end.
     */
    private const CHANNELS = [
        NotificationChannelEnum::Notification,
        NotificationChannelEnum::Email,
        NotificationChannelEnum::Push,
        NotificationChannelEnum::Sms,
        NotificationChannelEnum::Reminder,
        NotificationChannelEnum::Calendar,
    ];

    /**
     * Channels whose template is scheduled off a notification_verifiable_dates row.
     */
    private const DATED_CHANNELS = [
        NotificationChannelEnum::Reminder,
        NotificationChannelEnum::Calendar,
    ];

    /**
     * Access keys that best identify a record, most specific first. A reference
     * is quotable back to the system, so it outranks a name.
     */
    private const IDENTITY_KEYS = [
        'reference',
        'name',
    ];

    /**
     * Access keys worth naming inside a message body, in reading order: the
     * record's own reference, what it is, where it stands, then the dates. Keys
     * absent here (emails, phones, links, free text, file paths) never enter a
     * generated body — they don't read as a message a person would send.
     */
    private const DETAIL_KEYS = [
        'reference', 'name', 'nationality', 'code', 'phone_code', 'phone_length',
        'category.name', 'status', 'priority', 'visibility', 'owner.name',
        'is_active', 'published_at', 'expires_at', 'created_at',
    ];

    /**
     * Date columns worth reminding about, most meaningful first. A country carries
     * no business date of its own, so its reminders hang off the `created_at`
     * fallback in verifiableDateFor(); a showcase expires, which is the date its
     * audience actually needs warning about.
     */
    private const DATE_KEYS = [
        'expires_at',
    ];

    private ?int $roleId = null;

    /**
     * Written templates keyed by event slug then channel.
     *
     * @var array<string, array<string, array{title: array<string, string>, body: array<string, string>}>>
     */
    private array $writtenTemplates = [];

    /**
     * @var array<string, Collection<int, Variable>>
     */
    private array $eventVariablesCache = [];

    /**
     * @var array<string, Collection<int, Variable>>
     */
    private array $moduleVariablesCache = [];

    /**
     * @var array<string, NotificationVerifiableDate|null>
     */
    private array $verifiableDatesCache = [];

    /**
     * @var array<string, array<int, int>>
     */
    private array $relationReceiversCache = [];

    public function run(): void
    {
        // The variable table was just (re)seeded; drop any ids cached from a previous run.
        SystemEventVariableSeeder::flushCache();

        $this->roleId = Role::query()->orderBy('id')->value('id');
        $this->writtenTemplates = $this->loadWrittenTemplates();

        SystemEvent::query()->orderBy('id')->chunk(50, function ($systemEvents): void {
            foreach ($systemEvents as $systemEvent) {
                DB::transaction(fn () => $this->seedSystemEvent($systemEvent));
            }
        });
    }

    /**
     * Build (or refresh) the single notification event of a system event.
     */
    private function seedSystemEvent(SystemEvent $systemEvent): void
    {
        $variables = $this->variablesFor($systemEvent);
        $verifiableDate = $this->verifiableDateFor($systemEvent);

        $notificationEvent = NotificationEvent::firstOrNew(['system_event_id' => $systemEvent->id]);

        $notificationEvent->fill([
            'name' => $this->eventName($systemEvent),
            // A reminder setting can only exist when the model carries a schedulable date.
            'is_reminder' => $verifiableDate !== null,
            'type' => NotificationEventTypesEnum::Notification->value,
        ])->save();

        $notificationEvent->syncVariables($variables->pluck('id')->all());

        $this->syncTemplates($notificationEvent, $systemEvent, $variables, $verifiableDate);
        $this->ensureRecipients($notificationEvent, $systemEvent);
        $this->ensureReminderSetting($notificationEvent, $verifiableDate);
    }

    /**
     * Write one template per channel, keyed by channel so existing rows are rewritten
     * instead of replaced (notifications and logs reference template ids).
     *
     * @param  Collection<int, Variable>  $variables
     */
    private function syncTemplates(
        NotificationEvent $notificationEvent,
        SystemEvent $systemEvent,
        Collection $variables,
        ?NotificationVerifiableDate $verifiableDate,
    ): void {
        foreach (self::CHANNELS as $channel) {
            $isDated = in_array($channel, self::DATED_CHANNELS, true);
            $written = $this->writtenTemplateFor($systemEvent, $channel, $variables);

            $notificationEvent->templates()->updateOrCreate(
                ['channel' => $channel->value],
                [
                    'title' => $written['title'] ?? $this->templateTitle($systemEvent, $channel, $variables),
                    'body' => $written['body'] ?? $this->templateBody($systemEvent, $channel, $variables, $isDated ? $verifiableDate : null),
                    'date_column' => $isDated ? $verifiableDate?->id : null,
                ],
            );
        }
    }

    /**
     * The event name plus the record's identity variable, kept short enough for
     * a push title — prefixed on the reminder channel so the reader knows why the
     * message arrived again.
     *
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function templateTitle(SystemEvent $systemEvent, NotificationChannelEnum $channel, Collection $variables): array
    {
        $name = $this->localized($systemEvent->getTranslations('name'));
        $identity = $this->identityVariable($variables);
        $reference = $identity ? ' — {{'.$identity->id.'}}' : '';

        if ($channel === NotificationChannelEnum::Reminder) {
            return [
                'en' => "Reminder: {$name['en']}{$reference}",
                'ar' => "تذكير: {$name['ar']}{$reference}",
            ];
        }

        return [
            'en' => $name['en'].$reference,
            'ar' => $name['ar'].$reference,
        ];
    }

    /**
     * A message a person could have sent: short labelled facts on the compact
     * channels, a greeting + details + sign-off on email, a follow-up nudge on
     * reminder — each locale entirely in its own language.
     *
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function templateBody(
        SystemEvent $systemEvent,
        NotificationChannelEnum $channel,
        Collection $variables,
        ?NotificationVerifiableDate $verifiableDate,
    ): array {
        $name = $this->localized($systemEvent->getTranslations('name'));

        return match ($channel) {
            NotificationChannelEnum::Email => $this->emailBody($name, $variables),
            NotificationChannelEnum::Push => $this->pushBody($name, $variables),
            NotificationChannelEnum::Sms => $this->smsBody($name, $variables),
            NotificationChannelEnum::Reminder => $this->reminderBody($name, $variables, $verifiableDate),
            NotificationChannelEnum::Calendar => $this->calendarBody($name, $variables),
            default => $this->notificationBody($name, $variables),
        };
    }

    /**
     * @param  array{en: string, ar: string}  $name
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function emailBody(array $name, Collection $variables): array
    {
        $details = $this->detailVariables($variables, 8);

        $body = [
            'en' => "Hello,\nPlease be informed of the following update: {$name['en']}.",
            'ar' => "مرحباً،\nنحيطكم علماً بالمستجد التالي: {$name['ar']}.",
        ];

        if ($details->isNotEmpty()) {
            $body['en'] .= "\n\n".$this->detailLines($details, 'en');
            $body['ar'] .= "\n\n".$this->detailLines($details, 'ar');
        }

        $body['en'] .= "\n\nYou can view the full details in the system.\nBest regards.";
        $body['ar'] .= "\n\nيمكنكم الاطلاع على كامل التفاصيل من خلال النظام.\nمع خالص التحية.";

        return $body;
    }

    /**
     * @param  array{en: string, ar: string}  $name
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function notificationBody(array $name, Collection $variables): array
    {
        $details = $this->detailVariables($variables, 4);

        if ($details->isEmpty()) {
            return [
                'en' => "{$name['en']}. You can view the details in the system.",
                'ar' => "{$name['ar']}. يمكنك الاطلاع على التفاصيل من خلال النظام.",
            ];
        }

        return [
            'en' => $this->detailLines($details, 'en')."\n\nYou can follow up on the details in the system.",
            'ar' => $this->detailLines($details, 'ar')."\n\nيمكنك متابعة التفاصيل من خلال النظام.",
        ];
    }

    /**
     * @param  array{en: string, ar: string}  $name
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function pushBody(array $name, Collection $variables): array
    {
        $details = $this->detailVariables($variables, 2);

        if ($details->isEmpty()) {
            return $name;
        }

        return [
            'en' => $this->detailLines($details, 'en', ' — '),
            'ar' => $this->detailLines($details, 'ar', ' — '),
        ];
    }

    /**
     * @param  array{en: string, ar: string}  $name
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function smsBody(array $name, Collection $variables): array
    {
        $details = $this->detailVariables($variables, 2);

        $reference = [
            'en' => $details->isEmpty() ? '' : ' — '.$this->detailLines($details, 'en', ' — '),
            'ar' => $details->isEmpty() ? '' : ' — '.$this->detailLines($details, 'ar', ' — '),
        ];

        return [
            'en' => "{$name['en']}{$reference['en']}. Log in to the system for more details.",
            'ar' => "{$name['ar']}{$reference['ar']}. لمزيد من التفاصيل يرجى الدخول إلى النظام.",
        ];
    }

    /**
     * @param  array{en: string, ar: string}  $name
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function reminderBody(array $name, Collection $variables, ?NotificationVerifiableDate $verifiableDate): array
    {
        $details = $this->detailVariables($variables, 4);
        $dateVariable = $verifiableDate
            ? $variables->firstWhere('access_key', $verifiableDate->access_key)
            : null;

        if ($dateVariable && ! $details->contains('id', $dateVariable->id)) {
            $details->push($dateVariable);
        }

        $body = [
            'en' => "This is a reminder regarding: {$name['en']}.",
            'ar' => "هذا تذكير بخصوص: {$name['ar']}.",
        ];

        if ($details->isNotEmpty()) {
            $body['en'] .= "\n\n".$this->detailLines($details, 'en');
            $body['ar'] .= "\n\n".$this->detailLines($details, 'ar');
        }

        $body['en'] .= "\n\nPlease follow up and take the necessary action.";
        $body['ar'] .= "\n\nيرجى المتابعة واتخاذ الإجراء اللازم.";

        return $body;
    }

    /**
     * @param  array{en: string, ar: string}  $name
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function calendarBody(array $name, Collection $variables): array
    {
        $details = $this->detailVariables($variables, 4);

        if ($details->isEmpty()) {
            return $name;
        }

        return [
            'en' => "{$name['en']}\n\n".$this->detailLines($details, 'en'),
            'ar' => "{$name['ar']}\n\n".$this->detailLines($details, 'ar'),
        ];
    }

    /**
     * The event's variables worth reading in a message, in DETAIL_KEYS order,
     * capped so each channel stays the length its medium expects.
     *
     * @param  Collection<int, Variable>  $variables
     * @return Collection<int, Variable>
     */
    private function detailVariables(Collection $variables, int $limit): Collection
    {
        return $variables
            ->filter(fn (Variable $variable) => in_array($variable->access_key, self::DETAIL_KEYS, true))
            ->sortBy(fn (Variable $variable) => array_search($variable->access_key, self::DETAIL_KEYS, true))
            ->take($limit)
            ->values();
    }

    /**
     * One "label: value" fact per variable, referenced by numeric id — the
     * placeholder format the UI variable picker emits and VariableResolver replaces.
     *
     * @param  Collection<int, Variable>  $details
     */
    private function detailLines(Collection $details, string $locale, string $separator = "\n"): string
    {
        return $details
            ->map(function (Variable $variable) use ($locale): string {
                $name = $this->localized($variable->getTranslations('name'));
                $label = $name[$locale] !== '' ? $name[$locale] : $variable->access_key;

                return sprintf('%s: {{%d}}', $label, $variable->id);
            })
            ->implode($separator);
    }

    /**
     * Name the notification event after the system event it answers to.
     *
     * @return array{en: string, ar: string}
     */
    private function eventName(SystemEvent $systemEvent): array
    {
        $name = $this->localized($systemEvent->getTranslations('name'));

        return [
            'en' => $name['en'].' Notification',
            'ar' => 'إشعار '.$name['ar'],
        ];
    }

    /**
     * The variables the event exposes, ordered so the identifying ones read first.
     *
     * Mirrors SystemEventVariableSeeder: the module/model variables minus the access
     * keys excluded globally, for the module, or for this specific event.
     *
     * @return Collection<int, Variable>
     */
    private function variablesFor(SystemEvent $systemEvent): Collection
    {
        $module = $this->moduleOf($systemEvent);
        $cacheKey = $module.'|'.$systemEvent->model_type.'|'.$this->slugOf($systemEvent);

        return $this->eventVariablesCache[$cacheKey] ??= $this->moduleVariables($module, $systemEvent->model_type)
            ->reject(fn (Variable $variable) => in_array(
                $variable->access_key,
                SystemEventVariableSeeder::excludedAccessKeys($module, $this->slugOf($systemEvent)),
                true,
            ))
            ->sortBy(fn (Variable $variable) => [
                array_search($variable->access_key, self::IDENTITY_KEYS, true) === false ? 1 : 0,
                $variable->access_key,
            ])
            ->values();
    }

    /**
     * @return Collection<int, Variable>
     */
    private function moduleVariables(?string $module, ?string $modelType): Collection
    {
        return $this->moduleVariablesCache[$module.'|'.$modelType] ??= Variable::query()
            ->where('module', $module)
            ->where('model_type', $modelType)
            ->get();
    }

    /**
     * The best identity variable of the event, used to reference the record in titles.
     *
     * @param  Collection<int, Variable>  $variables
     */
    private function identityVariable(Collection $variables): ?Variable
    {
        foreach (self::IDENTITY_KEYS as $key) {
            if ($variable = $variables->firstWhere('access_key', $key)) {
                return $variable;
            }
        }

        return null;
    }

    /**
     * The notification_verifiable_dates row the reminder and calendar templates hang
     * off, preferring a meaningful business date over created_at.
     */
    private function verifiableDateFor(SystemEvent $systemEvent): ?NotificationVerifiableDate
    {
        $modelType = $systemEvent->model_type;

        if (array_key_exists($modelType, $this->verifiableDatesCache)) {
            return $this->verifiableDatesCache[$modelType];
        }

        $dates = NotificationVerifiableDate::query()
            ->where('model_type', $modelType)
            ->where('is_active', true)
            ->get();

        $best = null;

        foreach (self::DATE_KEYS as $key) {
            if ($best = $dates->firstWhere('access_key', $key)) {
                break;
            }
        }

        return $this->verifiableDatesCache[$modelType] = $best
            ?? $dates->firstWhere('access_key', 'created_at')
            ?? $dates->first();
    }

    /**
     * Add the default audience — the first role plus the module's relation receivers
     * — without touching recipients that are already configured.
     */
    private function ensureRecipients(NotificationEvent $notificationEvent, SystemEvent $systemEvent): void
    {
        $existing = $notificationEvent->notificationRecipients()
            ->get(['type', 'recipientable_id'])
            ->map(fn ($recipient) => $recipient->type.'|'.$recipient->recipientable_id)
            ->all();

        $desired = [];

        if ($this->roleId) {
            $desired[] = [
                'type' => 'role',
                'recipientable_type' => Role::class,
                'recipientable_id' => $this->roleId,
            ];
        }

        foreach ($this->relationReceiverIds($systemEvent) as $receiverId) {
            $desired[] = [
                'type' => 'relation',
                'recipientable_type' => NotificationReceiver::class,
                'recipientable_id' => $receiverId,
            ];
        }

        $missing = array_values(array_filter(
            $desired,
            fn (array $row) => ! in_array($row['type'].'|'.$row['recipientable_id'], $existing, true),
        ));

        if ($missing !== []) {
            $notificationEvent->notificationRecipients()->createMany($missing);
        }
    }

    /**
     * @return array<int, int>
     */
    private function relationReceiverIds(SystemEvent $systemEvent): array
    {
        $module = $this->moduleOf($systemEvent);

        return $this->relationReceiversCache[$module] ??= NotificationReceiver::query()
            ->where('module', $module)
            ->where('is_active', true)
            ->whereIn('type', ['self', 'relation'])
            ->pluck('id')
            ->all();
    }

    /**
     * Give the reminder channel something to fire on: one day before the event's date.
     */
    private function ensureReminderSetting(NotificationEvent $notificationEvent, ?NotificationVerifiableDate $verifiableDate): void
    {
        if (! $verifiableDate) {
            return;
        }

        $notificationEvent->remindersSetting()->updateOrCreate(
            ['channel' => NotificationChannelEnum::Reminder->value],
            [
                'offset_type' => ReminderSettingTypesEnum::Before->value,
                'offset_unit' => ReminderSettingUnitsEnum::Day->value,
                'offset_value' => 1,
                'reminder_based_on_column' => $verifiableDate->id,
            ],
        );
    }

    /**
     * Normalise a translations payload into both supported locales.
     *
     * @param  array<string, mixed>  $translations
     * @return array{en: string, ar: string}
     */
    private function localized(array $translations): array
    {
        return [
            'en' => (string) ($translations['en'] ?? $translations['ar'] ?? ''),
            'ar' => (string) ($translations['ar'] ?? $translations['en'] ?? ''),
        ];
    }

    /**
     * The written title/body of an event's channel, normalised to both locales.
     *
     * @return array{title?: array{en: string, ar: string}, body?: array{en: string, ar: string}}
     */
    private function writtenTemplateFor(SystemEvent $systemEvent, NotificationChannelEnum $channel, Collection $variables): array
    {
        $written = $this->writtenTemplates[$this->slugOf($systemEvent)][$channel->value] ?? null;

        if (! $written) {
            return [];
        }

        return array_map(
            fn (array $translations): array => $this->toIdPlaceholders($this->localized($translations), $variables),
            array_intersect_key($written, ['title' => true, 'body' => true]),
        );
    }

    /**
     * Rewrite the `{{access_key}}` placeholders the written copy is authored with
     * into the `{{id}}` form every other template carries.
     *
     * Copy is written against access keys because they read as prose and stay
     * valid across environments, while a stored template is addressed by numeric
     * variable id — that is what the editor renders as a variable and what the
     * generated templates emit. The ids are therefore stamped in here, at seed
     * time, from the event's own variables.
     *
     * @param  array{en: string, ar: string}  $translations
     * @param  Collection<int, Variable>  $variables
     * @return array{en: string, ar: string}
     */
    private function toIdPlaceholders(array $translations, Collection $variables): array
    {
        $map = $variables
            ->mapWithKeys(fn (Variable $variable): array => [
                '{{'.$variable->access_key.'}}' => '{{'.$variable->id.'}}',
            ])
            ->all();

        return array_map(fn (string $text): string => strtr($text, $map), $translations);
    }

    /**
     * @return array<string, array<string, array{title: array<string, string>, body: array<string, string>}>>
     */
    private function loadWrittenTemplates(): array
    {
        $path = __DIR__.'/data/event_templates.json';

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function moduleOf(SystemEvent $systemEvent): ?string
    {
        return $systemEvent->module instanceof \BackedEnum
            ? $systemEvent->module->value
            : $systemEvent->module;
    }

    private function slugOf(SystemEvent $systemEvent): ?string
    {
        return $systemEvent->event_slug instanceof \BackedEnum
            ? $systemEvent->event_slug->value
            : $systemEvent->event_slug;
    }
}
