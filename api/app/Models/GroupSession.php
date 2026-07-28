<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupSession extends Model
{
    protected $fillable = ['group_id', 'episode_id', 'occurred_on'];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupSessionParticipant::class);
    }
}
