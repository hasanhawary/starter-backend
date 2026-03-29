<?php

namespace App\Models;

use App\Enum\User\UserGenderEnum;
use App\Scopes\User\UserScopes;
use App\Trait\Global\ApplyNotification;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements LdapAuthenticatable
{
    use HasFactory, SoftDeletes, AuthenticatesWithLdap, UserScopes, ApplyNotification, CreatedByObserver, Notifiable, HasApiTokens, HasRoles, InteractsWithSockets, LogsActivityOptions;

    protected string $guard_name = 'api';
    public bool $inPermission = true;
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active'];

    protected $fillable = [
        'name', 'email', 'phone_code_id', 'phone', 'avatar', 'gender', 'password', 'otp_data',
        'is_active', 'last_login', 'ldap_name', 'guid', 'uid', 'created_by'
    ];

    protected $hidden = ['password', 'remember_token'];
    protected $with = ['phoneCode'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'gender' => UserGenderEnum::class,
        'otp_data' => 'array'
    ];

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
    public function avatar(): Attribute
    {
        return Attribute::make(
            get: static fn($value) => Media::url($value)
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: static fn($value) => bcrypt($value),
        );
    }

    public function getFullPhone(): string
    {
        $code = $this->phoneCode?->phone_code ?? '';
        $number = $this->phone ?? '';

        $fullPhone = trim(($code ?? '') . $number);
        return preg_replace('/\s+/', '', $fullPhone) ?: '---';
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

    public function phoneCode(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_code_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }
}
