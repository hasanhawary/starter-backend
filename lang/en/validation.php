<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal to :value.',
        'file' => 'The :attribute must be greater than or equal to :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal to :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal to :value.',
        'file' => 'The :attribute must be less than or equal to :value kilobytes.',
        'string' => 'The :attribute must be less than or equal to :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute format is invalid.',
    'uuid' => 'The :attribute must be a valid UUID.',
    'multiple_errors' => 'The :attribute must be a valid UUID.',

    'enum' => 'The selected :attribute is invalid.',

    'exact_length' => 'The :attribute must be exactly :length digits.',
    'invalid_model' => 'Invalid model for validation.',
    'length_not_found' => 'Length information not found for validation.',
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'password_min' => 'The :attribute must contain at least 8 characters.',
    'password_upper' => 'The :attribute must contain at least one uppercase letter.',
    'password_lower' => 'The :attribute must contain at least one lowercase letter.',
    'password_special' => 'The :attribute must contain at least one special character.',

    'password_min_length' => 'The :attribute must contain at least 8 characters.',
    'password_has_upper' => 'The :attribute must contain at least one uppercase letter.',
    'password_has_lower' => 'The :attribute must contain at least one lowercase letter.',
    'password_has_digit' => 'The :attribute must contain at least one digit.',
    'password_has_special' => 'The :attribute must contain at least one special character.',
    'password_no_repeats' => 'The :attribute must not contain more than two consecutive repeated characters.',
    'password_no_sequences' => 'The :attribute must not contain sequential characters like "abc" or "123".',
    'password_no_personal_info' => 'The :attribute must not contain parts of your name.',
    'password_no_dictionary' => 'The :attribute must not contain common or predictable words.',
    'at_least_one_language' => 'At least one language must be provided (Arabic or English)',
    'already_exists' => 'This item already exists',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'phone' => [
            'valid_length' => 'The phone number must be exactly :length digits.',
        ],
        'id_number' => [
            'valid_length' => 'The ID number must be exactly :length digits.',
        ],
        'overlap_day_slot' => 'The time slot (:from - :to) overlaps with another slot on :date.',

    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
    'attributes' => [

        /* ========= Identity & Basic Info ========= */
        'id' => 'ID',
        'name' => 'Name',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'username' => 'Username',
        'display_name' => 'Display Name',
        'display_name.ar' => 'Display Name in Arabic',
        'display_name.en' => 'Display Name in English',
        'name.ar' => 'Name in Arabic',
        'name.en' => 'Name in English',
        'gender' => 'Gender',
        'nationality' => 'Nationality',
        'title' => 'Title',

        /* ========= Account & Authentication ========= */
        'email' => 'Email',
        'email_verified_at' => 'Email Verification Date',
        'password' => 'Password',
        'password_confirmation' => 'Password Confirmation',
        'otp' => 'Verification Code',
        'last_login' => 'Last Login',
        'is_active' => 'Active',

        /* ========= Contact & Communication ========= */
        'phone' => 'Phone',
        'mobile' => 'Mobile',
        'telephone' => 'Telephone',
        'phone_code' => 'Phone Code',
        'phone_code_id' => 'Phone Code',
        'phone_length' => 'Phone Number Length',
        'address' => 'Address',

        /* ========= Content & Text ========= */
        'description' => 'Description',
        'content' => 'Content',
        'answers' => 'Answers',
        'event' => 'Event',
        'group' => 'Group',
        'type' => 'Type',
        'status' => 'Status',
        'order' => 'Order',

        /* ========= Roles & Permissions ========= */
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'permissions.*' => 'Permission',
        'permission' => 'Permission',
        'can_delete' => 'Can Delete',

        /* ========= Files & Media ========= */
        'avatar' => 'Avatar',
        'photo' => 'Photo',
        'img' => 'Image',
        'attachment' => 'Attachment',
        'file_name' => 'File Name',
        'chunk_number' => 'Chunk Number',
        'chunk_file' => 'Chunk File',

        /* ========= Dates & Time ========= */
        'date' => 'Date',
        'time' => 'Time',
        'created_by' => 'Created By',
        'read_at' => 'Read At',
        'open_at' => 'Opened At',

        /* ========= System & Settings ========= */
        'key' => 'Key',
        'code' => 'Code',
        'length' => 'Length',
        'department_id' => 'Department',
        'country' => 'Country',
        'notifiable' => 'Notifiable',
        'data' => 'Data',
        'token' => 'Token',
    ],
];
