<?php

namespace Modules\Form\app\Observers;

use Modules\Form\app\Models\Form;

class FormObserver
{
    /**
     * Handle the Form "created" event.
     */
    public function created(Form $form): void
    {
        if (! $form->has_steps) {
            $form->steps()->create([
                'name' => [
                    'en' => 'Default Step',
                    'ar' => 'الخطوة الافتراضية',
                ],
                'sorting_order' => 1,
            ]);
        }
    }
}
