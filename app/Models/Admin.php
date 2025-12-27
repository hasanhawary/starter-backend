<?php

namespace App\Models;

use App\Scopes\Admin\User\AdminScopes;
use App\Services\Global\UploadService;
use App\Trait\Global\ApplyNotification;
use App\Trait\Global\CreatedByAdminObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements LdapAuthenticatable
{
    use AuthenticatesWithLdap, AdminScopes, ApplyNotification, CreatedByAdminObserver, Notifiable, HasApiTokens, HasRoles, InteractsWithSockets, LogsActivityOptions;

    protected string $guard_name = 'admin';

    public bool $inPermission = true;
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = [
        'view-all',
        'view-own',
        'restore',
        'escalated',
        'remind-innovation-manager',
        'remind-general-manager'
    ];

    protected $fillable = [
        'name',
        'email',
        'phone_code',
        'phone',
        'avatar',
        'password',
        'gender',
        'department_id',
        'created_by',
        'is_active',
        'email_verified_at',
        'last_login',
        'parent_id',
        'guid',
        'username',
        'uid',
        'domain',
        'ldap_name',
    ];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Expect attributes for logging ActivityLog
     */
    protected array $logExceptAttributes = ['password', 'email_verified_at'];

    /*
    |--------------------------------------------------------------------------
    | Activity logs
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly($this->fillable);
    }

    /*
     |--------------------------------------------------------------------------
     | Casts && Set Custom Attributes
     |--------------------------------------------------------------------------
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function avatar(): Attribute
    {
        return Attribute::make(
            get: static fn($value) => UploadService::url($value)
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: static fn($value) => bcrypt($value),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'created_by');
    }

    public function createdLookups(): MorphMany
    {
        return $this->morphMany(Lookup::class, 'created_by');
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(ExpertSchedule::class, 'expert_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(
            Field::class,
            'field_experts',
            'admin_id',
            'field_id'
        );
    }

    public function expertRequests()
    {
        return $this->belongsToMany(Request::class, 'request_experts', 'expert_id', 'request_id')
            ->withPivot(['active'])
            ->withTimestamps();
    }
}