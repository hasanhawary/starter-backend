<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VariableAssignment extends Model
{
    public bool $inPermission = true;
    public array $specialOperations = [];

    protected $fillable = [
        'variable_id',
        'variableable_id',
        'variableable_type',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function variable(): BelongsTo
    {
        return $this->belongsTo(Variable::class);
    }

    public function variableable(): MorphTo
    {
        return $this->morphTo();
    }
}

