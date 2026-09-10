<?php

namespace Modules\Form\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\app\Enum\FormOptionsEnum;
use Modules\Form\app\Models\Form;
use Throwable;

class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        // Form 1: User Registration Form (with steps)
        $registrationForm = Form::create([
            'name' => [
                'en' => 'User Registration Form',
                'ar' => 'نموذج تسجيل المستخدم',
            ],
            'description' => [
                'en' => 'Complete registration process with multiple steps',
                'ar' => 'عملية التسجيل الكاملة بخطوات متعددة',
            ],
            'has_steps' => true,
        ]);

        // Create steps and fields for registration form
        $this->createRegistrationFormSteps($registrationForm);

        // Form 2: Contact Us Form (without steps)
        Form::create([
            'name' => [
                'en' => 'Contact Us Form',
                'ar' => 'نموذج اتصل بنا',
            ],
            'description' => [
                'en' => 'Get in touch with our team',
                'ar' => 'تواصل مع فريقنا',
            ],
            'has_steps' => false,
        ]);

        // Note: Contact form fields will be created by observer
    }

    /**
     * Create steps and fields for registration form
     */
    private function createRegistrationFormSteps($form): void
    {
        // Step 1: Personal Information
        $step1 = $form->steps()->create([
            'name' => [
                'en' => 'Personal Information',
                'ar' => 'المعلومات الشخصية',
            ],
            'sorting_order' => 1,
        ]);

        $step1->fields()->createMany([
            [
                'name' => ['en' => 'First Name', 'ar' => 'الاسم الأول'],
                'scheme' => [
                    'input_type' => 'text',
                    'rules' => ['required', 'string', 'min:2', 'max:50'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Enter your first name'],
                ],
            ],
            [
                'name' => ['en' => 'Last Name', 'ar' => 'اسم العائلة'],
                'scheme' => [
                    'input_type' => 'text',
                    'rules' => ['required', 'string', 'min:2', 'max:50'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Enter your last name'],
                ],
            ],
            [
                'name' => ['en' => 'Date of Birth', 'ar' => 'تاريخ الميلاد'],
                'scheme' => [
                    'input_type' => 'date',
                    'rules' => ['required', 'date', 'before:today'],
                    'reference' => ['type' => 'date'],
                    'html_attributes' => ['placeholder' => 'Select your date of birth'],
                ],
            ],
            [
                'name' => ['en' => 'Gender', 'ar' => 'الجنس'],
                'scheme' => [
                    'input_type' => 'select',
                    'rules' => ['required', 'in:male,female,other'],
                    'reference' => [
                        'type' => 'enum',
                        'options' => [
                            ['value' => 'male', 'label' => ['en' => 'Male', 'ar' => 'ذكر']],
                            ['value' => 'female', 'label' => ['en' => 'Female', 'ar' => 'أنثى']],
                            ['value' => 'other', 'label' => ['en' => 'Other', 'ar' => 'آخر']],
                        ],
                    ],
                    'html_attributes' => ['placeholder' => 'Select gender'],
                ],
            ],
        ]);

        // Step 2: Contact Information
        $step2 = $form->steps()->create([
            'name' => [
                'en' => 'Contact Information',
                'ar' => 'معلومات الاتصال',
            ],
            'sorting_order' => 2,
        ]);

        $step2->fields()->createMany([
            [
                'name' => ['en' => 'Email Address', 'ar' => 'البريد الإلكتروني'],
                'scheme' => [
                    'input_type' => 'email',
                    'rules' => ['required', 'email', 'unique:users,email', 'max:255'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Enter your email address'],
                ],
            ],
            [
                'name' => ['en' => 'Phone Number', 'ar' => 'رقم الهاتف'],
                'scheme' => [
                    'input_type' => 'tel',
                    'rules' => ['required', 'regex:/^[0-9]{10,15}$/', 'unique:users,phone'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Enter your phone number'],
                ],
            ],
            [
                'name' => ['en' => 'Country', 'ar' => 'الدولة'],
                'scheme' => [
                    'input_type' => 'select',
                    'rules' => ['required', 'string'],
                    'reference' => [
                        'type' => FormOptionsEnum::HelpModel->value,
                        'path' => 'countries',
                        'module' => null,
                        'column' => 'id',
                    ],
                    'html_attributes' => ['placeholder' => 'Select your country'],
                ],
            ],
            [
                'name' => ['en' => 'City', 'ar' => 'المدينة'],
                'scheme' => [
                    'input_type' => 'text',
                    'rules' => ['required', 'string', 'min:2', 'max:100'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Enter your city'],
                ],
            ],
        ]);

        // Step 3: Account Security
        $step3 = $form->steps()->create([
            'name' => [
                'en' => 'Account Security',
                'ar' => 'أمان الحساب',
            ],
            'sorting_order' => 3,
        ]);

        $step3->fields()->createMany([
            [
                'name' => ['en' => 'Username', 'ar' => 'اسم المستخدم'],
                'scheme' => [
                    'input_type' => 'text',
                    'rules' => ['required', 'string', 'min:4', 'max:30', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Choose a username'],
                ],
            ],
            [
                'name' => ['en' => 'Password', 'ar' => 'كلمة المرور'],
                'scheme' => [
                    'input_type' => 'password',
                    'rules' => ['required', 'string', 'min:8', 'max:255', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]/'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Enter a strong password'],
                ],
            ],
            [
                'name' => ['en' => 'Confirm Password', 'ar' => 'تأكيد كلمة المرور'],
                'scheme' => [
                    'input_type' => 'password',
                    'rules' => ['required', 'string', 'same:password'],
                    'reference' => ['type' => 'string'],
                    'html_attributes' => ['placeholder' => 'Re-enter your password'],
                ],
            ],
        ]);

        // Step 4: Additional Information
        $step4 = $form->steps()->create([
            'name' => [
                'en' => 'Additional Information',
                'ar' => 'معلومات إضافية',
            ],
            'sorting_order' => 4,
        ]);

        $step4->fields()->createMany([
            [
                'name' => ['en' => 'Profile Picture', 'ar' => 'صورة الملف الشخصي'],
                'scheme' => [
                    'input_type' => 'file',
                    'rules' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                    'reference' => ['type' => 'file'],
                    'html_attributes' => ['accept' => 'image/*'],
                ],
            ],
            [
                'name' => ['en' => 'Bio', 'ar' => 'نبذة تعريفية'],
                'scheme' => [
                    'input_type' => 'textarea',
                    'rules' => ['nullable', 'string', 'max:500'],
                    'reference' => ['type' => 'text'],
                    'html_attributes' => ['placeholder' => 'Tell us about yourself (optional)', 'rows' => '4'],
                ],
            ],
            [
                'name' => ['en' => 'Terms and Conditions', 'ar' => 'الشروط والأحكام'],
                'scheme' => [
                    'input_type' => 'checkbox',
                    'rules' => ['required', 'accepted'],
                    'reference' => ['type' => 'boolean'],
                    'html_attributes' => ['label' => 'I agree to the terms and conditions'],
                ],
            ],
            [
                'name' => ['en' => 'Newsletter Subscription', 'ar' => 'الاشتراك في النشرة الإخبارية'],
                'scheme' => [
                    'input_type' => 'checkbox',
                    'rules' => ['nullable', 'boolean'],
                    'reference' => ['type' => 'boolean'],
                    'html_attributes' => ['label' => 'Subscribe to our newsletter'],
                ],
            ],
        ]);
    }
}
