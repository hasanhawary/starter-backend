<?php

namespace Database\Seeders\Tenant;

use App\Enum\User\UserGenderEnum;
use App\Models\Central\Country;
use App\Models\Tenant\User;
use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Tenant;

class UserTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = 'sanctum';

        // Remove the relationships from pivot tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Generate Default Role And Permission and assign
        Access::setGuard($guardName)->handle();

        $tenant = Tenant::current();
        $domain = Str::before($tenant->domain,'.');

        $countryId = Country::first()->id;
        User::query()->firstOrCreate([
            'email' => "root@{$domain}.com"
        ], [
            'name' => 'root',
            'password' => '123456',
            'phone' => '01005164154',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
            'is_active' => true
        ])->assignRole('root');

        User::query()->firstOrCreate([
            'email' => "admin@{$domain}.com"
        ], [
            'name' => 'admin',
            'password' => '123456',
            'phone' => '01005164154',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
            'is_active' => true
        ])->assignRole('admin');
    }
}
