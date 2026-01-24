<?php

namespace Database\Seeders;

use App\Enum\User\UserGenderEnum;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domain = Str::snake(config('brands.default_brand'));

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
        ]);

        User::query()->firstOrCreate([
            'email' => "admin@{$domain}.com"
        ], [
            'name' => 'admin',
            'password' => '123456',
            'phone' => '01005164154',
            'phone_code_id' => $countryId,
            'gender' => UserGenderEnum::Male->value,
            'is_active' => true
        ]);
    }
}
