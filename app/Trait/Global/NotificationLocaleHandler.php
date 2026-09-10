<?php

namespace App\Trait\Global;

trait NotificationLocaleHandler
{
    /**
     * Handle sending notifications with fixed 'ar' locale.
     * must be used inside a class that has a method sendNotifications()
     */
    public function handleNotifications(): void
    {
        if (method_exists($this, 'sendNotifications')) {
            $currentLocale = app()->getLocale();
            app()->setLocale('ar');

            $this->sendNotifications();

            app()->setLocale($currentLocale);
        }
    }
}
