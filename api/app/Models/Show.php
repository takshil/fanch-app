<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Show extends Model
{
    /** @use HasFactory<\Database\Factories\ShowFactory> */
    use HasFactory;

    protected $fillable = [
        'tvmaze_id', 'slug', 'title', 'platform', 'genre', 'premiered_year', 'status', 'seasons_count', 'color',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('order_index');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function initiale(): string
    {
        return mb_strtoupper(mb_substr($this->title, 0, 1));
    }

    public function infoLine(): string
    {
        $parts = array_filter([
            $this->platform,
            $this->premiered_year,
            $this->seasons_count.' saison'.($this->seasons_count > 1 ? 's' : ''),
        ]);

        return implode(' · ', $parts);
    }

    public function card(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'initiale' => $this->initiale(),
            'color' => $this->color,
            'info' => $this->infoLine(),
        ];
    }
}
