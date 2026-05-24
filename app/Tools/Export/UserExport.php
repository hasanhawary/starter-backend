<?php

namespace App\Tools\Export;

use App\Enum\User\UserGenderEnum;
use App\Models\User;
use HasanHawary\ExportBuilder\BaseExport;

class UserExport extends BaseExport
{
    public function __construct(public array $filter)
    {
        $config = [
            'filter_relations' => [
                'many' => [
                    'created_by' => ['relation' => 'creator'],
                ],
            ],
            'model' => User::class,
            'columns' => [
                'id' => 'int',
                'name' => 'text',
                'email' => 'text',
                'phone' => 'text',
                'gender' => UserGenderEnum::class,
                'is_active' => 'boolean',
                'last_login' => 'datetime',
                'created_at' => 'datetime',
            ],
            'relations' => [
                'one' => [
                    'created_by' => ['creator' => ['name' => 'text', 'id' => 'int']],
                ],
                'many' => [
                    'count' => [],
                    'list' => [],
                    'concat' => ['roles' => ['display_name' => 'text']],
                ],
            ],
        ];

        parent::__construct($config, $filter);
    }
}
