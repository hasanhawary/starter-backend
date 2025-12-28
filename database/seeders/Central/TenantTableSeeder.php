<?php

namespace Database\Seeders\Central;

use App\Enum\User\UserGenderEnum;
use App\Models\Country;
use App\Models\tenant;
use App\Models\User;
use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Spatie\Multitenancy\Models\Tenant::query()->firstOrCreate([
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
