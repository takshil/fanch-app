<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A show the user is actively "in progress" on (drives the "À voir" list),
 * distinct from WatchlistItem which means "for later, not started".
 */
class ShowTracking extends Model
{
    protected $fillable = ['user_id', 'show_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }
}
