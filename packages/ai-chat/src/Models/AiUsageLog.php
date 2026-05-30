<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiUsageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return AiUsageLogFactory::new();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'id',
        'user_id',
        'conversation_id',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'cost',
        'latency_ms',
        'status',
        'error',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'float',
        'latency_ms' => 'integer',
    ];
}
