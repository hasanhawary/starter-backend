<?php

namespace AiChat\Models;

use AiChat\Database\Factories\AiFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return AiFeedbackFactory::new();
    }

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'ai_feedback';

    protected $fillable = [
        'id',
        'message_id',
        'conversation_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'message_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
