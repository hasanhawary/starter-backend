<?php

namespace Modules\Form\Database\Seeders;

use Illuminate\Database\Seeder;

class FormDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            FormSeeder::class,
        ]);
    }
}
