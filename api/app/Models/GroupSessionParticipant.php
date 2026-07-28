<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupSessionParticipant extends Model
{
    protected $fillable = ['group_session_id', 'user_id'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GroupSession::class, 'group_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
