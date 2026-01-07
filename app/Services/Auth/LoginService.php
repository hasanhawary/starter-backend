<?php

namespace App\Services\Auth;

use App\Exceptions\InactiveUserException;
use App\Exceptions\InvalidEmailAndPasswordCombinationException;
use Illuminate\Support\Facades\Hash;
use LdapRecord\Auth\PasswordRequiredException;
use LdapRecord\Auth\UsernameRequiredException;
use LdapRecord\Models\ActiveDirectory\User as ActiveDirectoryLdapUser;
use LdapRecord\Models\Attributes\Guid;
use LdapRecord\Models\OpenLDAP\User as OpenLdapUser;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LoginService extends BaseAuthService
{
    /**
     * @param array|null $data
     * @return array
     * @throws InactiveUserException
     * @throws InvalidEmailAndPasswordCombinationException
     */
    public function attempt(?array $data): array
    {
        $user = (config('ldap.active'))
            ? $this->attemptLdapLogin($data)
            : $this->attemptDefaultLogin($data);

        if (!$user->is_active) {
            throw new InactiveUserException(__('api.account_not_active'), ResponseAlias::HTTP_FORBIDDEN);
        }

        $this->setLastLogin($user);

        return [
            'user' => $user,
            'token' => $user->createToken($this->getGuard())->plainTextToken
        ];
    }

    /**
     * @param $data
     * @return mixed
     * @throws InvalidEmailAndPasswordCombinationException
     */
    public function attemptDefaultLogin($data): mixed
    {
        $user = $this->getModel()
            ->query()
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check(@$data['password'], $user->password)) {
            throw new InvalidEmailAndPasswordCombinationException(__('api.invalid_email_and_password'), ResponseAlias::HTTP_NOT_ACCEPTABLE);
        }

        return $user;
    }

    /**
     * @param $data
     * @return mixed
     * @throws InvalidEmailAndPasswordCombinationException
     */
    protected function attemptLdapLogin($data): mixed
    {
        $username = $data['email'];
        $password = $data['password'];

        $ldapUserModel = config('ldap.local') ? OpenLdapUser::class : ActiveDirectoryLdapUser::class;
        $ldapAttributes = ['uid', 'cn', 'samaccountname', 'userprincipalname', 'mail'];

        $ldapUser = collect($ldapAttributes)
            ->map(fn($attr) => $ldapUserModel::where($attr, '=', $username)->first())
            ->filter()
            ->first();

        if (!$ldapUser) {
            return $this->attemptDefaultLogin($data);
        }

        try {
            $ldapUser->getConnection()->auth()->bind($ldapUser->getDn(), $password);

            return $this->findOrCreateUserFromLdap($ldapUser, $password);

        } catch (PasswordRequiredException|UsernameRequiredException $e) {
            throw new InvalidEmailAndPasswordCombinationException(__('api.invalid_email_and_password'), ResponseAlias::HTTP_FORBIDDEN);
        }
    }

    /**
     * @param  $user
     * @return bool
     */
    public function setLastLogin($user): bool
    {
        $user->last_login = now();
        $user->save();

        return true;
    }

    /**
     * @param $ldapUser
     * @param $password
     * @return mixed
     */
    protected function findOrCreateUserFromLdap($ldapUser, $password): mixed
    {
        $parts = explode(' ', trim($ldapUser->getFirstAttribute('cn')), 2);

        $user = $this->getModel()->updateOrCreate([
            'uid' => @$ldapUser->getFirstAttribute('uid'),
            'email' => @$ldapUser->getFirstAttribute('mail'),
        ], [
            'first_name' => $parts[0] ?? null,
            'last_name' => $parts[1] ?? null,
            'phone' => $ldapUser->getFirstAttribute('telephonenumber') ?? "00966",
            'phone_code_id' => 1,
            'guid' => isset($ldapUser->objectguid[0]) ? (string)new Guid($ldapUser->objectguid[0]) : $ldapUser->getObjectGuid(),
            'ldap_name' => $ldapUser->getFirstAttribute('cn'),
            'password' => $password,
        ]);

        if ($user->wasRecentlyCreated) {
            $user->assignRole('default_role');
            $user->save();
        }

        return $user->fresh();
    }
}
