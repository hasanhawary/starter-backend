<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use JsonException;
use Modules\Notification\app\Http\Resources\NotificationVerifiableDateResource;
use Modules\Notification\app\Models\NotificationVerifiableDate;

enum NotificationChannelEnum: string
{
    use EnumMethods;

    case Sms = 'sms';
    case Email = 'email';
    case Push = 'push';
    case Reminder = 'reminder';
    case Notification = 'notification';
    case Calendar = 'calendar';

    /**
     * Resolve the module sent alongside this enum in the help-enums request,
     * e.g. enums[i][name]=notification_channel with enums[i][extra][module]=cause.
     *
     * The lookup manager calls enum methods without arguments, so the module is
     * read from the current request payload instead.
     */
    public static function getOptionsByModule(): Collection
    {
        $key = Str::of(class_basename(self::class))
            ->snake()
            ->replaceLast('_enum', '')
            ->toString();
        $enums = request()->all()['enums'] ?? [];

        foreach ((array) $enums as $enum) {
            $name = $enum['name'] ?? null;
            if ($name === $key && isset($enum['extra']['module'])) {
                return NotificationVerifiableDate::select('id', 'name', 'module')
                    ->where('module', $enum['extra']['module'])
                    ->get();
            }
        }

        return new Collection;
    }

    public static function icons(): array
    {
        return [
            self::Sms->value => 'push',
            self::Email->value => 'sms',
            self::Push->value => 'send',
            self::Reminder->value => 'reminder',
            self::Notification->value => 'notification',
            self::Calendar->value => 'calendar',
        ];
    }

    public static function extra(): array
    {
        return [
            self::Push->value => [
                'schema' => self::titleAndBodySchema(),
            ],

            self::Email->value => [
                'schema' => self::titleAndBodySchema(),
            ],

            self::Sms->value => [
                'schema' => [...self::bodyField()],
            ],

            self::Reminder->value => [
                'schema' => self::datedSchema(),
            ],

            self::Calendar->value => [
                'schema' => self::datedSchema(),
            ],

            self::Notification->value => [
                'schema' => self::titleAndBodySchema(),
            ],
        ];
    }

    private static function titleAndBodySchema(): array
    {
        return [
            ...self::titleField(),
            ...self::bodyField(),
        ];
    }

    /**
     * @throws JsonException
     */
    private static function datedSchema(): array
    {
        return [
            ...self::titleAndBodySchema(),
            self::dateColumnField(),
        ];
    }

    private static function titleField(): array
    {
        return [
            [
                'key' => 'title.ar',
                'type' => 'text',
                'label' => __('api.title'),
                'placeholder' => __('api.enter_notification_title'),
                'rules' => ['required', 'max:200'],
                'cols' => [
                    'sm' => 3,
                    'md' => 3,
                    'lg' => 3,
                ],
                'variableOptions' => [
                    'allowVariables' => true,
                    'variableActionMode' => 'select',
                    'classList' => '!mt-7',
                    'itemTitle' => 'name',
                    'itemValue' => 'id',
                    'position' => 'end',
                    'placeholder' => __('api.select_a_variable'),
                    'preventDuplicateSelection' => true,
                    'cols' => [
                        'md' => 3,
                        'lg' => 3,
                    ],

                ],
            ],
            [
                'key' => 'title.en',
                'type' => 'text',
                'label' => __('api.title'),
                'placeholder' => __('api.enter_notification_title'),
                'rules' => ['required', 'max:200'],
                'cols' => [
                    'sm' => 3,
                    'md' => 3,
                    'lg' => 3,
                ],
                'variableOptions' => [
                    'allowVariables' => true,
                    'variableActionMode' => 'select',
                    'classList' => '!mt-7',
                    'itemTitle' => 'name',
                    'itemValue' => 'id',
                    'position' => 'end',
                    'placeholder' => __('api.select_a_variable'),
                    'preventDuplicateSelection' => true,
                    'cols' => [
                        'md' => 3,
                        'lg' => 3,
                    ],

                ],
            ],
        ];
    }

    private static function bodyField(): array
    {
        return [
            [
                'key' => 'body.ar',
                'type' => 'textarea',
                'label' => __('api.body'),
                'placeholder' => __('api.enter_notification_body'),
                'rules' => ['required', 'max:1000'],
                'cols' => [
                    'sm' => 3,
                    'md' => 3,
                    'lg' => 3,
                ],
                'variableOptions' => [
                    'allowVariables' => true,
                    'variableActionMode' => 'select',
                    'classList' => '!mt-7',
                    'itemTitle' => 'name',
                    'itemValue' => 'id',
                    'position' => 'end',
                    'placeholder' => __('api.select_a_variable'),
                    'preventDuplicateSelection' => true,
                    'cols' => [
                        'md' => 3,
                        'lg' => 3,
                    ],

                ],
            ],
            [
                'key' => 'body.en',
                'type' => 'textarea',
                'label' => __('api.body'),
                'placeholder' => __('api.enter_notification_body'),
                'rules' => ['required', 'max:1000'],
                'cols' => [
                    'sm' => 3,
                    'md' => 3,
                    'lg' => 3,
                ],
                'variableOptions' => [
                    'allowVariables' => true,
                    'variableActionMode' => 'select',
                    'classList' => '!mt-7',
                    'itemTitle' => 'name',
                    'itemValue' => 'id',
                    'position' => 'end',
                    'placeholder' => __('api.select_a_variable'),
                    'preventDuplicateSelection' => true,
                    'cols' => [
                        'md' => 3,
                        'lg' => 3,
                    ],

                ],
            ],

        ];
    }

    /**
     * @throws JsonException
     */
    private static function dateColumnField(): array
    {
        return [
            'key' => 'channel',
            'type' => 'select',
            'label' => __('api.reminder_date_type'),
            'placeholder' => __('api.select_reminder_date_type'),
            'options' => NotificationVerifiableDateResource::collection(self::getOptionsByModule()),
            'rules' => ['required', 'date'],
        ];
    }
}
