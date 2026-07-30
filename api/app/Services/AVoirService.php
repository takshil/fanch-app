<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Group;
use App\Models\User;

class AVoirService
{
    /** The list of "next episode to watch" across every show the user is actively tracking. */
    public static function items(User $user): array
    {
        $trackings = $user->showTrackings()->with('show')->get();
        $watchedEpisodeIds = $user->watchMarks()->pluck('episode_id')->all();

        $items = [];

        foreach ($trackings as $tracking) {
            $show = $tracking->show;
            $group = Group::forUserAndShow($user, $show);

            $episode = $group ? $group->nextEpisode() : $show->episodes()->whereNotIn('id', $watchedEpisodeIds)->first();

            if (! $episode) {
                continue; // rien de nouveau à voir sur cette série
            }

            $items[] = [
                'id' => $show->slug,
                'show' => $show->card(),
                'episode' => self::episodeCard($episode),
                'group_id' => $group?->id,
                'badge' => $group ? 'Groupe' : null,
                'vu' => in_array($episode->id, $watchedEpisodeIds, true),
            ];
        }

        return $items;
    }

    public static function episodeCard(Episode $episode): array
    {
        return [
            'id' => $episode->id,
            'code' => $episode->code(),
            'title' => $episode->title,
            'season_number' => $episode->season_number,
            'episode_number' => $episode->episode_number,
        ];
    }
}
