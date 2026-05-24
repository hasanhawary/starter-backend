<?php

namespace App\Services\Auth;

use App\Enum\Global\OtpTypeEnum;
use App\Exceptions\EmailVerifiedException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use App\Exceptions\InvalidOtpException;
use App\Http\Requests\Auth\VerifyOtpRequest;
use Illuminate\Support\Facades\Hash;
use LdapRecord\Auth\PasswordRequiredException;
use LdapRecord\Auth\UsernameRequiredException;
use LdapRecord\Models\ActiveDirectory\User as ActiveDirectoryLdapUser;
use LdapRecord\Models\Attributes\Guid;
use LdapRecord\Models\OpenLDAP\User as OpenLdapUser;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LoginService extends BaseAuthService
{
    public function __construct(protected OTPService $otpService) {}

    /**
     * Attempt login for user or admin
     *
     * @return array ['user' => Model, 'token' => string]
     *
     * @throws InActiveUserException
     * @throws InvalidEmailAndPasswordCombinationException
     * @throws EmailVerifiedException
     * @throws InvalidOtpException
     */
    public function attempt(?array $data): array
    {
        // LDAP login if enabled, otherwise default login
        $user = config('project.ldap.active')
            ? $this->attemptLdapLogin($data)
            : $this->attemptDefaultLogin($data);

        if (! $user->is_active) {
            throw new InActiveUserException(__('api.account_not_active'));
        }

        // OTP verification if enabled in config
        if (shouldVerifyOtp()) {
            $this->verifyOtp($data);
        }

        $this->setLastLogin($user);

        return [
            'user' => $user,
            'token' => $this->createUserToken($user, $data['meta'] ?? []),
        ];
    }

    /**
     * Default login (email + password)
     *
     * @throws InvalidEmailAndPasswordCombinationException
     */
    public function attemptDefaultLogin(array $data): mixed
    {
        $user = $this->getModel()
            ->query()
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'] ?? '', $user->password)) {
            throw new InvalidEmailAndPasswordCombinationException(
                __('api.invalid_email_and_password'),
                ResponseAlias::HTTP_NOT_ACCEPTABLE
            );
        }

        return $user;
    }

    /**
     * LDAP login fallback
     *
     * @throws InvalidEmailAndPasswordCombinationException
     */
    protected function attemptLdapLogin(array $data): mixed
    {
        $username = $data['email'];
        $password = $data['password'];

        $ldapUserModel = config('project.ldap.local') ? OpenLdapUser::class : ActiveDirectoryLdapUser::class;
        $ldapAttributes = ['uid', 'cn', 'samaccountname', 'userprincipalname', 'mail'];

        $ldapUser = collect($ldapAttributes)
            ->map(fn ($attr) => $ldapUserModel::where($attr, $username)->first())
            ->filter()
            ->first();

        if (! $ldapUser) {
            return $this->attemptDefaultLogin($data);
        }

        try {
            $ldapUser->getConnection()->auth()->bind($ldapUser->getDn(), $password);

            return $this->findOrCreateUserFromLdap($ldapUser, $password);

        } catch (PasswordRequiredException|UsernameRequiredException) {
            throw new InvalidEmailAndPasswordCombinationException(
                __('api.invalid_email_and_password'),
                ResponseAlias::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * Update last login timestamp
     */
    public function setLastLogin($user): bool
    {
        $user->last_login = now();
        $user->save();

        return true;
    }

    /**
     * Find or create a user from LDAP
     */
    protected function findOrCreateUserFromLdap($ldapUser, $password): mixed
    {

        $user = $this->getModel()->updateOrCreate([
            'uid' => $ldapUser->getFirstAttribute('uid'),
            'email' => $ldapUser->getFirstAttribute('mail'),
        ], [
            'name' => $ldapUser->getFirstAttribute('cn') ?? null,
            'phone' => $ldapUser->getFirstAttribute('telephonenumber') ?? '00966',
            'phone_code_id' => config('project.auth.default_phone_code_id', 1),
            'guid' => isset($ldapUser->objectguid[0])
                ? (string) new Guid($ldapUser->objectguid[0])
                : $ldapUser->getObjectGuid(),
            'ldap_name' => $ldapUser->getFirstAttribute('cn'),
            'password' => $password,

        ]);

        if ($user->wasRecentlyCreated) {
            $user->assignRole(config('project.auth.default_role', 'default_role'));
            $user->save();
        }

        return $user->fresh();
    }

    /**
     * Verify OTP via OTPService
     *
     * @throws InvalidOtpException
     */
    private function verifyOtp(array $data): void
    {
        $this->otpService
            ->setModel($this->model)
            ->verify(new VerifyOtpRequest($data), OtpTypeEnum::Login->value);
    }

    protected function createUserToken($user, array $meta = []): string
    {
        $token = $user->createToken($this->getGuard());

        if (! empty($meta)) {
            $token->accessToken->update([
                'meta' => [
                    'device' => [
                        'os' => data_get($meta, 'userAgent.os'),
                        'browser' => data_get($meta, 'userAgent.browser'),
                        'type' => data_get($meta, 'userAgent.device'),
                    ],
                    'platform' => $meta['platform'] ?? null,
                    'timezone' => $meta['timezone'] ?? null,
                    'language' => $meta['language'] ?? null,
                    'screen' => [
                        'width' => data_get($meta, 'screen.width'),
                        'height' => data_get($meta, 'screen.height'),
                    ],
                    'ip' => request()->ip(),
                    'ua' => request()->userAgent(),
                ],
            ]);
        }

        return $token->plainTextToken;
    }
}
