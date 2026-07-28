<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\GroupSession;
use App\Models\User;
use App\Models\WatchMark;

/**
 * All progression (watch_marks) mutations go through here so the "single
 * source of truth" rule (cahier des charges §3.3) has exactly one place
 * where it can be violated — nowhere else writes to watch_marks directly.
 */
class WatchMarkService
{
    /** Records a viewing. Rewatches increment views_count on the same row rather than creating a new one. */
    public static function recordViewing(User $user, Episode $episode, ?GroupSession $session = null): WatchMark
    {
        $mark = WatchMark::firstOrNew(['user_id' => $user->id, 'episode_id' => $episode->id]);
        $mark->views_count = ($mark->views_count ?? 0) + 1;
        $mark->watched_at = now();
        if ($session && ! $mark->group_session_id) {
            $mark->group_session_id = $session->id;
        }
        $mark->save();

        return $mark;
    }

    /** Removes one viewing (a séance correction). Deletes the row once the count reaches zero. */
    public static function removeViewing(User $user, Episode $episode): void
    {
        $mark = WatchMark::where('user_id', $user->id)->where('episode_id', $episode->id)->first();

        if (! $mark) {
            return;
        }

        $mark->views_count -= 1;

        if ($mark->views_count <= 0) {
            $mark->delete();
        } else {
            $mark->save();
        }
    }

    /** A full un-check from the UI — not a rewatch correction, just "I didn't mean that". */
    public static function clearMark(User $user, Episode $episode): void
    {
        WatchMark::where('user_id', $user->id)->where('episode_id', $episode->id)->delete();
    }
}
