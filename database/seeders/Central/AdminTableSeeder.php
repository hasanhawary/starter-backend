<?php

namespace Database\Seeders\Central;

use App\Enum\User\UserGenderEnum;
use App\Models\Central\Admin;
use App\Models\Central\Country;
use App\Models\Tenant\User;
use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Remove the relationships from pivot tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Access::handle();

        $countryId = Country::first()->id;
        $domain = Str::snake(config('brands.default_brand'));
        Admin::query()->firstOrCreate([
            'email' => "root@$domain.com"
        ], [
            'name' => 'root',
            'password' => '123456',
            'phone' => '5412545214',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
        ])->assignRole('root');

        Admin::query()->firstOrCreate([
            'email' => "admin@$domain.com"
        ], [
            'name' => 'admin',
            'password' => '123456',
            'phone' => '5412545215',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
        ])->assignRole('admin');
    }
}
