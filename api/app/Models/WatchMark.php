<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The progression — §3.3: single source of truth for what a user has watched.
 * Unique on (user_id, episode_id). Rewatches increment views_count on this
 * same row rather than creating a new one.
 */
class WatchMark extends Model
{
    protected $fillable = ['user_id', 'episode_id', 'views_count', 'group_session_id', 'watched_at'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /** null = watched solo; otherwise the séance during which this was first logged. */
    public function groupSession(): BelongsTo
    {
        return $this->belongsTo(GroupSession::class);
    }
}
