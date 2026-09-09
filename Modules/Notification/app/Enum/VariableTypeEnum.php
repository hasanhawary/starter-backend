<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum VariableTypeEnum: string
{
    use EnumMethods;

    case Column = 'column';
    case Relation = 'relation';
    case ManyRelation = 'many_relation';

}
