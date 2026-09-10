<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;

class NotificationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            NotificationVariablesSeeder::class,
            NotificationSystemEventSeeder::class,
            NotificationReceiversSeeder::class,
            NotificationVerifiableDatesSeeder::class,
            SystemEventVariableSeeder::class,
            DefaultNotificationEventTemplatesSeeder::class,
            NotificationEventVariableSeeder::class,
            //            ScheduleEventSeeder::class,
        ]);
    }
}
