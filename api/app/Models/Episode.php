<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    /** @use HasFactory<\Database\Factories\EpisodeFactory> */
    use HasFactory;

    protected $fillable = [
        'show_id', 'season_number', 'episode_number', 'order_index', 'title', 'runtime_minutes', 'air_date',
    ];

    protected function casts(): array
    {
        return [
            'air_date' => 'date',
        ];
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function code(): string
    {
        return sprintf('S%d·E%d', $this->season_number, $this->episode_number);
    }
}
