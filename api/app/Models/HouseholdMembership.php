<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Adhésion (§3.1): the link between a Compte and a Foyer. Never holds
 * personal data itself — progression, watchlist, etc. all live on the
 * Compte (User) directly so they survive independently of any one foyer.
 */
class HouseholdMembership extends Model
{
    protected $fillable = ['user_id', 'household_id', 'role', 'joined_at'];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
