<?php

use App\Helpers\DelimiterParamValue;
use App\Models\User;
use App\Services\Global\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jenssegers\Date\Date;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
|--------------------------------------------------------------------------
| Responses Methods
|--------------------------------------------------------------------------
*/
if (! function_exists('currnetLogo')) {
    function currnetLogo(): ?string
    {
        $logo = public_path('wakeb.png');

        return file_exists($logo) ? $logo : null;
    }
}

if (! function_exists('successResponse')) {
    function successResponse($data = [], $msg = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'code' => $code,
            'message' => $msg ?? __('api.success'),
            'data' => $data,
        ], $code);
    }
}

if (! function_exists('failResponse')) {
    function failResponse($msg = 'fail', $data = [], $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'code' => $code,
            'message' => $msg,
            'data' => $data,
        ], $code);
    }
}
if (! function_exists('abort403')) {
    function abort403($condition = true): void
    {
        if ($condition) {
            abort(403, trans('api.no_required_permissions'));
        }
    }
}
if (! function_exists('unKnownError')) {
    function unKnownError($message = null): JsonResponse|RedirectResponse
    {
        $message = trans('dashboard.something_error').''.(config('debug') ? " : $message" : '');

        return request()?->expectsJson()
            ? response()->json(['message' => $message], 400)
            : redirect()->back()->with(['status' => 'error', 'message' => $message]);
    }
}

/*
|--------------------------------------------------------------------------
| App Check Methods (IS)
|--------------------------------------------------------------------------
*/
if (! function_exists('isArrayIndex')) {
    function isArrayIndex($value): bool
    {
        return is_array($value) && count(array_filter(array_keys($value), 'is_string')) === 0;
    }
}

if (! function_exists('iSnake')) {
    function iSnake($value): bool
    {
        // Define the pattern for snake_case
        $pattern = '/^[a-z-A-Z]+(_[a-z]+)*$/';

        // Check if the value matches the pattern
        if (preg_match($pattern, $value)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('isBase64')) {
    function isBase64($data): bool
    {
        $decoded_data = base64_decode($data, true);
        $encoded_data = base64_encode($decoded_data);

        if ($encoded_data !== $data) {
            return false;
        }

        if (! ctype_print($decoded_data)) {
            return false;
        }

        return true;
    }
}

if (! function_exists('isRoot')) {
    function isRoot($user = null): bool
    {
        return (bool) $user?->hasRole('root');
    }
}

/*
|--------------------------------------------------------------------------
| Resolves Methods
|--------------------------------------------------------------------------
*/
if (! function_exists('resolveTrans')) {
    function resolveTrans($trans = '', $page = 'api', $lang = null, $snaked = true): ?string
    {
        if (empty($trans)) {
            return '---';
        }

        app()->setLocale($lang ?? app()->getLocale());

        $key = $snaked ? Str::snake($trans) : $trans;

        return Str::startsWith(__("$page.$key"), "$page.") ? $trans : __("$page.$key");
    }
}

if (! function_exists('resolveBool')) {
    function resolveBool($item): string
    {
        if ($item === 0) {
            return __('api.no');
        }

        if ($item === 1) {
            return __('api.yes');
        }

        return $item;
    }
}

if (! function_exists('resolvePhoto')) {
    function resolvePhoto($image = null, $type = 'user')
    {
        $result = ($type === 'user'
            ? asset('media/avatar.svg')
            : asset('media/blank.png'));

        if (is_null($image)) {
            return $result;
        }

        if (Str::startsWith($image, 'http')) {
            return $image;
        }

        return Storage::exists($image)
            ? Storage::url($image)
            : $result;
    }
}

if (! function_exists('resolveArray')) {
    function resolveArray(string|array $array): array
    {
        return is_array($array) ? $array : explode(',', $array);
    }
}

if (! function_exists('resolveModel')) {
    function resolveModel(string $name, $module = null): ?object
    {
        $modelPath = ! empty($module) && $module !== 'none'
            ? 'Modules\\'.ucfirst(Str::camel($module)).'\\App\\Models'
            : 'App\\Models';

        $modelClass = $modelPath.'\\'.Str::studly(Str::singular($name));

        return class_exists($modelClass) ? app($modelClass) : null;
    }
}

if (! function_exists('resolveClass')) {
    function resolveClass(string $path): ?object
    {
        return class_exists($path) ? app($path) : null;
    }
}

if (! function_exists('resolveEmptyLang')) {
    function resolveEmptyLang(array $trans): array
    {
        $trans['ar'] = $trans['ar'] ?? $trans['en'] ?? '';
        $trans['en'] = $trans['en'] ?? $trans['ar'] ?? '';

        return $trans;
    }
}

if (! function_exists('resolveEmptyToNull')) {
    function resolveEmptyToNull($value)
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($v) => resolveEmptyToNull($v))->toArray();
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || strtolower($trimmed) === 'null') {
                return null;
            }
        }

        if ($value === []) {
            return null;
        }

        return $value;
    }
}

/*
|--------------------------------------------------------------------------
| App Global Methods
|--------------------------------------------------------------------------
*/
if (! function_exists('dateFormat')) {
    function dateFormat($date, $format = 'j F Y'): string
    {
        return ! is_numeric($date)
            ? Date::parse('2024-01-01')
            : '----';
    }
}

if (! function_exists('timeFormat')) {
    function timeFormat($time): ?string
    {
        if ($time === null) {
            return null;
        }

        return Date::parse($time)->format('h:i a');
    }
}

if (! function_exists('getModelKey')) {
    function getModelKey($className = null, $case = 'snake'): ?string
    {
        if (! $className) {
            return null;
        }

        if (is_object($className)) {
            $className = get_class($className);
        }

        $shortName = class_basename($className);

        $delimiter = $case === 'slug' ? '-' : '';
        $caseType = (method_exists(Str::class, $case) && $case != 'slug') ? $case : 'snake';

        return strtolower($delimiter ? Str::{$caseType}($shortName, $delimiter) : Str::{$caseType}($shortName));

    }
}

if (! function_exists('detectModelPath')) {
    function detectModelPath($type): string
    {
        return 'App\\Models\\'.Str::ucfirst(Str::camel(Str::singular($type)));
    }
}

if (! function_exists('fetchData')) {
    /**
     * Fetch query results through an API resource, merging the sortable state of
     * the resource keys into the pagination payload.
     *
     * Only paginated results gain a `sorting` entry alongside the pagination
     * entries; non paginated results carry no sorting. The keys are read from the
     * first record, so an empty page has none.
     *
     * A custom `$pageName` lets several paginators live in the same response
     * without sharing a page cursor, e.g. one per kanban column. `$page` forces
     * the page to resolve to, instead of reading `$pageName` off the request.
     */
    function fetchData(Builder $query, string|int|null $pageSize = null, $resource = null, $meta = [], string $pageName = 'page', ?int $page = null)
    {
        $paginated = $pageSize && (int) $pageSize !== -1;

        $data = $paginated ? $query->paginate($pageSize, ['*'], $pageName, $page) : $query->get();

        if ($resource) {
            $collection = $resource::collection($data);

            $data = $paginated
                ? [...$data->toArray(), 'sorting' => resourceSorting($collection, getModelKey($query->getModel()))]
                : $collection;
        }

        if (count($meta)) {
            $data = [
                'data' => $data,
                ...$meta,
            ];
        }

        return $data;
    }
}

if (! function_exists('wrapPaginate')) {
    /**
     * Fetch query results through an API resource using the configured page size.
     *
     * When paginated, the sortable state of the resource keys is merged into the
     * pagination payload as a `sorting` entry; non paginated results carry no
     * sorting. The keys are read from the first record, so an empty page has none.
     */
    function wrapPaginate(Builder $query, $resource = null, $meta = [])
    {
        $perPage = request('per_page', config('project.pagination.per_page'));

        $paginated = $perPage && (int) $perPage !== -1;

        $data = $paginated ? $query->paginate($perPage) : $query->get();

        if ($resource) {
            $collection = $resource::collection($data);

            $data = $paginated
                ? [...$data->toArray(), 'sorting' => resourceSorting($collection, getModelKey($query->getModel()))]
                : $collection;
        }

        if (count($meta)) {
            $data = [
                'data' => $data,
                ...$meta,
            ];
        }

        return $data;
    }
}

if (! function_exists('resourceKeys')) {
    /**
     * Resolve the list of keys an API resource exposes.
     *
     * Accepts a resource instance, a resource collection (the first item defines
     * the shape, an empty collection has no keys), or a resource class name
     * together with the model to hydrate it.
     *
     * @param  JsonResource|class-string<JsonResource>  $resource
     * @param  Model|null  $model  Model used to hydrate the resource when a class name is passed.
     * @return array<int, string>
     */
    function resourceKeys(JsonResource|string $resource, ?Model $model = null): array
    {
        if (is_string($resource)) {
            if (! is_subclass_of($resource, JsonResource::class)) {
                throw new InvalidArgumentException("[$resource] is not a valid API resource class.");
            }

            $resource = new $resource($model);
        }

        if ($resource instanceof ResourceCollection) {
            $resource = $resource->collection->first();

            if (! $resource) {
                return [];
            }
        }

        $resolved = $resource->resolve(request());

        if (isArrayIndex($resolved)) {
            $resolved = Arr::first($resolved) ?? [];
        }

        return array_keys($resolved);
    }
}

if (! function_exists('resourceSorting')) {
    /**
     * Map the keys an API resource exposes to the `sortColumn` the frontend
     * must submit to sort by them, based on the module's `discovery.sorting`
     * configuration.
     *
     * Each resource key maps to its sorting key: the key itself for plain
     * sortable columns, the declared column for aliased entries (e.g.
     * `display_status` => `status`), and `null` when the key is not sortable.
     *
     * @param  JsonResource|class-string<JsonResource>  $resource
     * @param  string|null  $module  Discovery module key, e.g. `legal_study`.
     * @param  Model|null  $model  Model used to hydrate the resource when a class name is passed.
     * @return array<string, string|null> Empty when the module has no sorting configured.
     */
    function resourceSorting(JsonResource|string $resource, ?string $module, ?Model $model = null): array
    {
        $sortable = config("discovery.sorting.$module");

        if (! is_array($sortable)) {
            return [];
        }

        // Normalize the config: a plain entry sorts by itself, a
        // `'key' => 'sorting_key'` entry sorts by the declared column.
        $sortingKeys = collect($sortable)
            ->mapWithKeys(fn ($value, $key) => is_int($key) ? [$value => $value] : [$key => $value]);

        return collect(resourceKeys($resource, $model))
            ->mapWithKeys(fn (string $key): array => [$key => $sortingKeys[$key] ?? null])
            ->all();
    }
}

if (! function_exists('imageExtensions')) {
    function imageExtensions(): array
    {
        return ['jpg', 'png', 'jpeg', 'png', 'gif', 'svg'];
    }
}

if (! function_exists('vImage')) {
    function vImage($ext = null): string
    {
        return ($ext === null) ? 'mimes:jpg,png,jpeg,png,gif,bmp,svg' : 'mimes:'.$ext;
    }
}

if (! function_exists('updateDotEnv')) {
    function updateDotEnv(array $data = []): void
    {
        $path = base_path('.env');

        foreach ($data as $dataKey => $dataValue) {
            if (is_bool($dataValue)) {
                $dataValue = $dataValue ? 'true' : 'false';
            }

            if (str_contains(file_get_contents($path), "\n".$dataKey.'=')) {
                $contents = array_values(array_filter(explode("\n", file_get_contents($path))));
                foreach ($contents as $content) {
                    if (str_starts_with($content, $dataKey.'=')) {
                        $delim = '';

                        if (str_contains($content, '"') || str_contains($dataValue, ' ') || str_contains($dataValue, '#')) {
                            $delim = '"';
                        }
                        file_put_contents(
                            $path,
                            str_replace(
                                $content,
                                $dataKey.'='.$delim.$dataValue.$delim,
                                file_get_contents($path)
                            )
                        );
                    }
                }
            } elseif (str_contains($dataValue, ' ') || str_contains($dataValue, '#')) {
                File::append($path, $dataKey.'="'.$dataValue.'"'."\n");
            } else {
                File::append($path, $dataKey.'='.$dataValue."\n");
            }
        }
    }
}

if (! function_exists('logError')) {
    function logError($exception): void
    {
        info('Error In Line => '.$exception->getLine()." in File => {$exception->getFile()} , ErrorDetails => ".$exception->getMessage());
    }
}

if (! function_exists('when')) {
    /**
     * Executes the given closure if the condition is true.
     * The condition is considered true if:
     * - It is a boolean and true
     * - It is a collection and not empty
     * - It is an array and not empty
     * - It is a string and not empty
     *
     * @param  callable  $closure  The closure to execute if the condition is pass from check.
     */
    function when(mixed $condition, callable $closure): void
    {
        // Determine if the condition is true based on its type using match
        $isTrue = match (true) {
            is_bool($condition) => $condition,
            $condition instanceof Collection => ! $condition->isEmpty(),
            is_array($condition) => ! empty($condition),
            is_string($condition) => $condition !== '',
            default => false,
        };

        // If the condition is true, execute the closure
        if ($isTrue) {
            $closure();
        }
    }
}

if (! function_exists('parseKeyValueString')) {
    function parseKeyValueString($data = null, string $page = 'api'): ?string
    {
        if (is_null($data)) {
            return null;
        }

        if (is_array($data)) {
            $locale = app()->getLocale();
            if (isset($data[$locale])) {
                return $data[$locale];
            }

            $key = $data['id'] ?? null;
            $params = $data['parameters'] ?? [];

            return $key ? __("$page.$key", $params) : null;
        }

        // Split the string into key-value pairs
        $pairs = explode('|', $data);

        // Extract the first key (the primary identifier or title)
        $firstPair = array_shift($pairs);
        // Return translation directly if there are no additional pairs
        if (empty($pairs)) {
            return __("$page.$firstPair");
        }

        // Parse key-value pairs into an associative array
        $result = array_reduce($pairs, static function (array $carry, string $pair) {
            if (! str_contains($pair, '=')) {
                return $carry; // Skip invalid pairs
            }

            [$key, $value] = explode('=', $pair, 2);

            if ($key !== 'prefix') {
                $carry[trim($key)] = trim($value);
            }

            return $carry;
        }, []);

        // Return the translation with parameters
        return __("$page.$firstPair", $result);
    }
}

if (! function_exists('buildDelimiterMessage')) {
    /**
     * Build the packed message string.
     *
     * Param values can be:
     *  - scalar (string/int)            → name=John
     *  - DelimiterParamValue::json(...)    → name={"en":"John","ar":"جون"}
     *  - DelimiterParamValue::enum(...)    → enum_status=App\Enums\StatusEnum@Active
     *
     * Output: 'create_admin_data_msg|name=John|email=john@example.com|...'
     */
    function buildDelimiterMessage(string $translationKey, array $params = []): string
    {
        if (empty($params)) {
            return $translationKey;
        }

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value instanceof DelimiterParamValue) {
                $resolvedKey = $value->type === 'enum' ? "enum_{$key}" : $key;
                $parts[] = "{$resolvedKey}={$value->formatted}";
            } else {
                $parts[] = "{$key}={$value}";
            }
        }

        return $translationKey.'|'.implode('|', $parts);
    }
}

if (! function_exists('transWithParams')) {
    function transWithParams(?string $data, string $page = 'notifications.emails', array $params = []): ?string
    {
        if (! $data) {
            return null;
        }

        $parts = explode('|', $data);
        $key = array_shift($parts);

        foreach ($parts as $part) {
            if (! str_contains($part, '=')) {
                continue;
            }

            [$k, $v] = explode('=', $part, 2);
            $k = trim($k);
            $v = trim($v);

            // Enum: enum_status => App\Enums\StatusEnum@Active
            if (str_starts_with($k, 'enum_')) {
                $paramKey = substr($k, 5); // strip "enum_"
                [$fqn, $case] = explode('@', $v, 2);
                $params[$paramKey] = enum_exists($fqn)
                    ? $fqn::resolve($case)
                    : $case;

                continue;
            }

            // JSON: name => {"en":"John","ar":"جون"}
            if (str_starts_with($v, '{') || str_starts_with($v, '[')) {
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $params[$k] = $decoded[app()->getLocale()]
                        ?? $decoded['en']
                        ?? $v;

                    continue;
                }
            }

            // Plain
            $params[$k] = $v;
        }

        return __("$page.$key", $params);
    }
}

if (! function_exists('emailTrans')) {
    function emailTrans(?string $data, array $params = []): ?string
    {
        return transWithParams(
            $data,
            'notifications.emails',
            array_merge([
                'platform_name' => brandName(),
            ], $params)
        );
    }
}

if (! function_exists('rootUsers')) {
    function rootUsers(): array
    {
        return User::whereHas('roles', static fn ($q) => $q->where('name', 'root'))
            ->pluck('id')
            ->toArray();
    }
}

if (! function_exists('utf8StrRev')) {
    function utf8StrRev($str = null): ?string
    {
        if ($str) {
            preg_match_all('/./us', $str, $ar);

            return implode('', array_reverse($ar[0]));
        }

        return null;
    }
}

if (! function_exists('safeExecute')) {
    function safeExecute($callback, $return = true)
    {
        try {
            $callback();
        } catch (QueryException|Exception|Error|QueryException $e) {
            logError($e);

            if (config('app.env', 'local')) {
                throw $e;  // Re-throw the exception for local environment
            }

            return $return;
        }
    }
}

if (! function_exists('findSoftDeletedModel')) {
    function findSoftDeletedModel(string $modelClass, array $conditions): ?Model
    {
        return $modelClass::onlyTrashed()
            ->where(function (Builder $query) use ($conditions) {
                foreach ($conditions as $column => $value) {
                    $query->orWhereRaw("JSON_EXTRACT($column, '$.en') = ?", [$value['en']])
                        ->orWhereRaw("JSON_EXTRACT($column, '$.ar') = ?", [$value['ar']]);
                }
            })
            ->first();
    }
}

if (! function_exists('allModelsNames')) {
    function allModelsNames(): Collection
    {
        $modelPath = app_path('Models');

        return collect(File::allFiles($modelPath))
            ->map(function ($file) {
                return str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $file->getRelativePathname()
                );
            });
    }
}

if (! function_exists('allAttributesFillableModels')) {
    function allAttributesFillableModels(): array
    {
        $modelPath = app_path('Models');
        $models = collect(File::allFiles($modelPath))
            ->map(function ($file) {
                $namespace = 'App\\Models\\';
                $class = $namespace.str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        $file->getRelativePathname()
                    );

                return new $class;
            });

        $fillableAttributes = [];

        foreach ($models as $model) {
            $fillableAttributes = array_merge($fillableAttributes, $model->getFillable());
        }

        return array_unique($fillableAttributes);
    }
}

function getCurrentGuard(): int|string|null
{
    foreach (array_keys(config('auth.guards')) as $guard) {
        if (auth()->guard($guard)->check()) {
            return $guard;
        }
    }

    return null; // No guard is currently authenticated
}

if (! function_exists('toggleBooleanAttribute')) {
    function toggleBooleanAttribute(Model $model, string $attribute): Model
    {
        if (! array_key_exists($attribute, $model->getAttributes())) {
            throw new InvalidArgumentException("Attribute '{$attribute}' does not exist on model ".get_class($model));
        }

        if (! is_bool($model->{$attribute})) {
            throw new InvalidArgumentException("Attribute '{$attribute}' column must be a boolean.");
        }

        $model->{$attribute} = ! $model->{$attribute};
        $model->save();

        return $model;
    }
}

if (! function_exists('canDelete')) {
    function canDelete($object)
    {
        if (isset($object->can_delete) && ! $object->can_delete) {
            throw new NotFoundHttpException;
        }
    }
}

if (! function_exists('checkFromPath')) {
    function checkFromPath(string|array $path, array $rules = []): void
    {
        $paths = Arr::wrap($path);

        foreach ($paths as $path) {
            $filePath = str_starts_with(parse_url($path, PHP_URL_PATH), '/storage/')
                ? str_replace('/storage/', '', parse_url($path, PHP_URL_PATH))
                : Storage::path($path);

            $fileExists = file_exists($filePath);

            if (! $fileExists && ! Storage::exists($filePath)) {
                throw new Exception(resolveTrans('file_not_found'));
            }

            $uploadedFilePath = $fileExists ? $filePath : Storage::path($filePath);
            $uploadedFileMime = $fileExists ? mime_content_type($filePath) : Storage::mimeType($filePath);

            $file = new UploadedFile(path: $uploadedFilePath, originalName: 'sample.jpg', mimeType: $uploadedFileMime, test: true);

            if (Validator::make(['file' => $file], ['file' => $rules])->fails()) {
                throw new InvalidArgumentException(resolveTrans('file_not_match_rules'));
            }
        }
    }
}

if (! function_exists('rulesBasedOnFlag')) {
    function rulesBasedOnFlag(array $rules, bool $flag): array
    {
        $updatedRules = [];
        foreach ($rules as $key => $ruleConditions) {
            $updatedRules[$key] = $flag
                ? array_filter($ruleConditions, fn ($rule) => $rule !== 'required')
                : $ruleConditions;
        }

        return $updatedRules;
    }

    if (! function_exists('shouldVerifyOtp')) {
        function shouldVerifyOtp(): bool
        {
            $default = config('auth.defaults.guard');
            $guard = $default === 'api' ? 'user' : $default;

            return config('project.auth.login_methods.otp')
                && config("project.auth.otp.required_for.{$guard}");
        }
    }
}

if (! function_exists('encryptCode')) {
    function encryptCode(array $data): array
    {
        try {
            $key = base64_decode(str_replace('base64:', '', config('app.front_shared_key')));

            $iv = random_bytes(16); // AES block size
            $encrypted = openssl_encrypt(
                json_encode($data, JSON_THROW_ON_ERROR),
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            return [
                'payload' => base64_encode($encrypted),
                'iv' => base64_encode($iv),
            ];
        } catch (Error|Exception $e) {
            return $data;
        }
    }
}

if (! function_exists('setting')) {
    /*
     *
     * Get setting value by path, optionally specify language.
     *
     * @param string $path Dot notation path, e.g. 'theme.colors.primary_color'
     * @param string|null $lang Language code for multi-lang fields
     * @param mixed $default Default value if setting not found
     * @return mixed
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function setting(string $path, ?string $lang = null, $default = null): mixed
    {
        return app(SettingService::class)->get($path, $lang, $default);
    }
}

if (! function_exists('settingBool')) {
    /**
     * Read a boolean (switchbox) setting, correctly handling explicit false.
     */
    function settingBool(string $path, bool $default = true): bool
    {
        return app(SettingService::class)->bool($path, $default);
    }
}

if (! function_exists('notificationChannelEnabled')) {
    /**
     * Check whether a notification channel is globally enabled via the
     * "Notifications" settings sub-module (mail/sms/push/realtime master switches).
     *
     * Channels with no master switch (e.g. reminder, calendar) are always allowed.
     * Defaults to enabled when the switch has not been configured, so existing
     * behaviour is preserved until an admin explicitly turns a channel off.
     */
    function notificationChannelEnabled(string $channel): bool
    {
        $map = [
            'email' => 'mail_support',
            'mail' => 'mail_support',
            'sms' => 'sms_support',
            'push' => 'push_support',
            'notification' => 'real_time_support',
            'notify' => 'real_time_support',
            'realtime' => 'real_time_support',
        ];

        $key = $map[strtolower($channel)] ?? null;

        if (! $key) {
            return true;
        }

        return settingBool("notifications.$key", true);
    }
}

if (! function_exists('brandSettings')) {
    /**
     * Get all brand settings as an associative array.
     *
     * @param  string|null  $lang  Optional language code. Defaults to app locale.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function brandSettings(?string $lang = null): array
    {
        $lang = $lang ?? app()->getLocale();

        return [
            'name' => setting('general.info.name', $lang),
            'website' => setting('general.info.website_address'),
            'logo' => [
                'lg' => setting('properties.logos.website_logo_large'),
                'lg_dark' => setting('properties.logos.website_dark_logo_large'),
                'sm' => setting('properties.logos.website_logo_small'),
                'sm_dark' => setting('properties.logos.website_dark_logo_small'),
            ],
            'theme' => [
                'primary' => setting('theme.colors.primary_color'),
                'secondary' => setting('theme.colors.secondary_color'),
                'text' => setting('theme.colors.text_color'),
                'muted' => setting('theme.colors.muted_color'),
            ],
            'font' => [
                'family' => setting($lang === 'ar' ? 'theme.font.font_family_ar' : 'theme.font.font_family_en', $lang),
                'size' => setting('theme.font.font_size'),
            ],
            'background' => setting('mail_templates.theme.header_image'),
            'mail_otp_style' => [
                'otp_bg' => setting('mail_templates.otp.otp_bg'),
                'otp_border_color' => setting('mail_templates.otp.otp_border_color'),
                'otp_font_size' => setting('mail_templates.otp.otp_font_size'),
                'otp_letter_spacing' => setting('mail_templates.otp.otp_letter_spacing'),
                'otp_text_color' => setting('mail_templates.otp.otp_text_color'),
            ],
            'mail_theme' => [
                'button_bg_color' => setting('mail_templates.theme.button_bg_color'),
                'button_text_color' => setting('mail_templates.theme.button_text_color'),
                'header_image' => setting('mail_templates.theme.header_image'),
                'header_text' => setting('mail_templates.theme.header_text', $lang),
                'footer_image' => setting('mail_templates.theme.footer_image'),
                'footer_text' => setting('mail_templates.theme.footer_text', $lang),
            ],
            'contact' => [
                'email' => setting('general.contact.contact_email'),
                'phone' => setting('general.contact.contact_phone'),
                'address' => setting('general.contact.contact_address'),
            ],
            'social' => [
                'instagram' => setting('social.instagram'),
                'facebook' => setting('social.facebook'),
                'linkedin' => setting('social.linkedin'),
                'twitter' => setting('social.twitter'),
                'youtube' => setting('social.youtube'),
            ],
        ];
    }
}

if (! function_exists('brandName')) {
    function brandName(): string
    {
        return config('brands.default_brand', 'Default');
    }
}
