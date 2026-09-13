<?php

namespace Modules\Showcase\Database\Seeders;

use Illuminate\Database\Seeder;

class ShowcaseDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ShowcaseSeeder::class,
        ]);
    }
}
