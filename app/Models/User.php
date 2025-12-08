<?php

namespace App\Models;

use App\Scopes\User\UserScopes;
use App\Trait\Global\ApplyNotification;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use UserScopes,
        ApplyNotification,
        CreatedByObserver,
        Notifiable,
        HasApiTokens,
        HasRoles,
        InteractsWithSockets,
        LogsActivityOptions;

    protected string $guard_name = 'sanctum';
    public bool $inPermission = true;

    public array $basicOperations = ['create', 'update','delete'];
    public array $specialOperations = ['view-all', 'view-own', 'export', 'restore'];

    protected $hidden = ['password', 'remember_token'];
    protected $fillable = [
        'name', 'email', 'phone_code_id', 'phone', 'avatar', 'gender', 'nationality_id', 'password', 'otp',
        'otp_expire_at', 'is_active', 'last_login', 'ldap_name', 'guid', 'uid', 'created_by'
    ];

    /*
     |--------------------------------------------------------------------------
     | Casts && Set Custom Attributes
     |--------------------------------------------------------------------------
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expire_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    public function avatar(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Media::url(paths: $value),
            set: fn($value) => Media::replace($this->avatar)->upload($value, 'users'),
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(set: static fn($value) => bcrypt($value));
    }

    /*
    |--------------------------------------------------------------------------
    | Activity log methods
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly(array_merge($this->fillable, []));
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'created_by');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_id');
    }
}
