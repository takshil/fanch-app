<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    protected $fillable = ['group_id', 'user_id', 'status'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * §3.3: computed from the progression (watch_marks), never stored — the
     * order_index of the last episode of $show this member has watched in an
     * unbroken run from episode 1. Returns 0 if they haven't started.
     */
    public function progressOrderIndex(?Show $show = null): int
    {
        $show ??= $this->group->show;
        $episodes = $show->episodes()->orderBy('order_index')->get(['id', 'order_index']);
        $watchedIds = $this->user->watchMarks()
            ->whereIn('episode_id', $episodes->pluck('id'))
            ->pluck('episode_id')->all();

        $last = 0;
        foreach ($episodes as $episode) {
            if (! in_array($episode->id, $watchedIds, true)) {
                break;
            }
            $last = $episode->order_index;
        }

        return $last;
    }

    public function progressEpisode(?Show $show = null): ?Episode
    {
        $order = $this->progressOrderIndex($show);

        return $order === 0 ? null : Episode::where('order_index', $order)->first();
    }
}
