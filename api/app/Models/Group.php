<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A watch-together group. Per the cahier des charges §3.4, a group is a set of
 * ACCOUNTS tied to a show — deliberately not tied to a household, so people in
 * different households can follow a show together.
 */
class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $fillable = ['show_id', 'created_by', 'name', 'archived_at'];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(GroupInvite::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GroupSession::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** The (non-archived) group this user belongs to for a given show, if any. Groups are per-account, not per-household. */
    public static function forUserAndShow(User $user, Show $show): ?self
    {
        return static::where('show_id', $show->id)
            ->whereNull('archived_at')
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->first();
    }

    /** @return \Illuminate\Support\Collection<int, GroupMember> */
    public function activeMembers()
    {
        return $this->members()->with('user')->where('status', 'actif')->get();
    }

    /**
     * §3.3-3.4: the point commun is a CALCULATED VIEW over individual progression
     * (watch_marks) — never a stored counter. It's the order_index of the last
     * episode watched by every active member.
     */
    public function pointCommunOrderIndex(): ?int
    {
        $actifs = $this->activeMembers();

        if ($actifs->isEmpty()) {
            return null;
        }

        return $actifs->min(fn (GroupMember $m) => $m->progressOrderIndex($this->show));
    }

    /** The member(s) currently holding the group back — the ones the rest are waiting for. */
    public function bottleneckMembers()
    {
        $order = $this->pointCommunOrderIndex();

        if ($order === null) {
            return $this->activeMembers();
        }

        return $this->activeMembers()->filter(
            fn (GroupMember $m) => $m->progressOrderIndex($this->show) === $order
        );
    }

    /** The next episode nobody in the group has watched together yet. */
    public function nextEpisode(): ?Episode
    {
        $order = $this->pointCommunOrderIndex() ?? 0;

        return $this->show->episodes()->where('order_index', '>', $order)->first();
    }

    /** §5.2: a group auto-archives once it has no active member left. */
    public function refreshArchivedState(): void
    {
        $hasActive = $this->members()->where('status', 'actif')->exists();

        if (! $hasActive && ! $this->isArchived()) {
            $this->update(['archived_at' => now()]);
        } elseif ($hasActive && $this->isArchived()) {
            $this->update(['archived_at' => null]);
        }
    }
}
