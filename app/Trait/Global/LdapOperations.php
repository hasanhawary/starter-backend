<?php

namespace App\Trait\Global;

use LdapRecord\Models\ActiveDirectory\User as ActiveDirectoryLdapUser;
use LdapRecord\Models\Attributes\Guid;
use LdapRecord\Models\OpenLDAP\User as OpenLdapUser;

trait LdapOperations
{
    public function getLdapUsers($request): array
    {
        if (! config('ldap.active')) {
            return [];
        }

        $ldapUserModel = $this->getLdapModel();

        $query = $ldapUserModel::query();
        $search = $request->search;

        if ($search) {
            $query->whereContains('cn', $search)
                ->orWhereContains('mail', $search);
        }

        $ldapUsers = $query->get();

        $filtered = collect($ldapUsers)->filter(fn ($ldapUser) => $ldapUser->getFirstAttribute('mail'));

        $mapped = $filtered->map(function ($ldapUser) {
            return [
                'name' => $ldapUser->getFirstAttribute('cn'),
                'email' => $ldapUser->getFirstAttribute('mail') ?? null,
                'phone' => $ldapUser->getFirstAttribute('telephonenumber') ?? null,
                'phone_code' => $ldapUser->getFirstAttribute('telephonenumber') ? '+966' : null,
                'uid' => $ldapUser->getFirstAttribute('uid') ?? null,
                'guid' => isset($ldapUser->objectguid[0]) ? (string) new Guid($ldapUser->objectguid[0]) : $ldapUser->getObjectGuid(),
                'ldap_name' => 'wakeb',
            ];
        })->sortBy('name')->values();

        return $mapped->toArray();
    }

    protected function getLdapModel(): OpenLdapUser|ActiveDirectoryLdapUser
    {
        return config('ldap.local') ? new OpenLdapUser : new ActiveDirectoryLdapUser;
    }
}
