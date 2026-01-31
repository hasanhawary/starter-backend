<?php

namespace Database\Seeders;

use App\Enum\User\UserGenderEnum;
use App\Models\User;
use App\Models\Country;
use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = 'api';

        // Remove the relationships from pivot tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Generate Default Role And Permission and assign
        Access::setGuard($guardName)->handle();

        $countryId = Country::first()->id;
        $domain = Str::snake(config('brands.default_brand'));
        User::query()->firstOrCreate([
            'email' => "root@$domain.com"
        ], [
            'name' => 'root',
            'password' => '123456',
            'phone' => '01005164154',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
            'is_active' => true
        ])->assignRole('root');

        User::query()->firstOrCreate([
            'email' => "admin@$domain.com"
        ], [
            'name' => 'user',
            'password' => '123456',
            'phone' => '01005164154',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
            'is_active' => true
        ])->assignRole('admin');
    }
}
