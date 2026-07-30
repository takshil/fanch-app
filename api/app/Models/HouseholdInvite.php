<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdInvite extends Model
{
    protected $fillable = ['household_id', 'code'];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
