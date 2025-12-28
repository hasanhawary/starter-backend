<?php

namespace Database\Seeders;

use App\Enum\User\UserGenderEnum;
use App\Models\Country;
use App\Models\User;
use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
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

        User::query()->firstOrCreate([
            'email' => 'root@wakeb.com'
        ], [
            'name' => 'root',
            'password' => '123456',
            'phone' => '5412545214',
            'phone_code_id' => $countryId,
            'nationality_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
        ])->assignRole('root');

        User::query()->firstOrCreate([
            'email' => 'admin@wakeb.com'
        ], [
            'name' => 'admin',
            'password' => '123456',
            'phone' => '5412545215',
            'phone_code_id' => $countryId,
            'nationality_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
        ])->assignRole('admin');
    }
}
