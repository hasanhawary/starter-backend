<?php

namespace Modules\Form\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormRelated extends Model
{
    public $timestamps = false;

    protected $table = 'form_related';

    protected $fillable = ['form_id', 'related_type', 'related_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function module(): MorphTo
    {
        return $this->morphTo('related');
    }
}
