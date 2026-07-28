<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Group;
use App\Models\GroupInvite;
use App\Models\Household;
use App\Models\GroupMember;
use App\Models\GroupSession;
use App\Models\GroupSessionParticipant;
use App\Models\Show;
use App\Models\User;
use App\Services\WatchMarkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    /** Create a group for a show. The creator is its first (actif) member. §4.1. */
    public function store(Request $request, Show $show)
    {
        $data = $request->validate(['name' => ['nullable', 'string', 'max:120']]);
        $user = $request->user();

        $group = DB::transaction(function () use ($show, $user, $data) {
            $group = Group::create([
                'show_id' => $show->id, 'created_by' => $user->id,
                'name' => $data['name'] ?? 'Groupe '.$show->title,
            ]);
            GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'status' => 'actif']);
            GroupInvite::create(['group_id' => $group->id, 'code' => 'GROUPE-'.Str::upper(Str::random(6))]);

            return $group;
        });

        return $this->present($group->fresh(), $user);
    }

    /** Join an existing group via its invite code. */
    public function join(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        $invite = GroupInvite::where('code', Str::upper(trim($data['code'])))->first();
        abort_unless($invite, 422, "Ce code d'invitation est introuvable.");

        $member = GroupMember::firstOrNew(['group_id' => $invite->group_id, 'user_id' => $user->id]);
        $member->status = 'actif';
        $member->save();
        $invite->group->refreshArchivedState();

        return $this->present($invite->group->fresh(), $user);
    }

    public function show(Request $request, Group $group)
    {
        $this->authorizeMembership($request, $group);

        return $this->present($group, $request->user());
    }

    /**
     * §3.1: "le foyer... réduit à suggérer des membres lors de la création d'un
     * groupe" — co-members of the requester's current foyer who aren't in this
     * group yet, so they can be added with one tap instead of an invite code.
     */
    public function addFromHousehold(Request $request, Group $group)
    {
        $this->authorizeMembership($request, $group);
        $requester = $request->user();
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        abort_unless($requester->current_household_id, 422, "Vous n'avez pas de foyer actif.");
        $shared = User::find($data['user_id'])->households()
            ->where('households.id', $requester->current_household_id)->exists();
        abort_unless($shared, 422, "Ce compte ne partage pas votre foyer actif.");

        $member = GroupMember::firstOrNew(['group_id' => $group->id, 'user_id' => $data['user_id']]);
        $member->status = 'actif';
        $member->save();
        $group->refreshArchivedState();

        return $this->present($group->fresh(), $requester);
    }

    /** A member updates their own status (actif / pause / abandonné). §3.4, §5.2. */
    public function updateMyStatus(Request $request, Group $group)
    {
        $this->authorizeMembership($request, $group);
        $data = $request->validate(['status' => ['required', 'in:actif,pause,abandonne']]);

        $member = GroupMember::where('group_id', $group->id)->where('user_id', $request->user()->id)->firstOrFail();
        $member->update(['status' => $data['status']]);
        $group->refreshArchivedState();

        return $this->present($group->fresh(), $request->user());
    }

    /** Removal by the group's creator. The removed member keeps their progression — it belongs to their account. §5.2. */
    public function removeMember(Request $request, Group $group, User $user)
    {
        $this->authorizeMembership($request, $group);
        abort_unless($group->created_by === $request->user()->id, 403, 'Seul le créateur du groupe peut retirer un membre.');
        abort_if($user->id === $group->created_by, 422, 'Le créateur ne peut pas se retirer lui-même.');

        GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->delete();
        $group->refreshArchivedState();

        return $this->present($group->fresh(), $request->user());
    }

    public function seancePrep(Request $request, Group $group)
    {
        $this->authorizeMembership($request, $group);
        $group->load(['show', 'members.user']);

        $episode = $group->nextEpisode();

        $participants = $group->members
            ->reject(fn (GroupMember $m) => $m->status === 'abandonne')
            ->map(function (GroupMember $m) use ($group, $episode) {
                $note = null;
                if ($m->status === 'pause') {
                    $note = 'en pause';
                } elseif ($episode && $m->progressOrderIndex($group->show) >= $episode->order_index) {
                    $note = 'déjà vu';
                }

                return [
                    'user' => ['id' => $m->user->id, 'name' => $m->user->name, 'initial' => $m->user->initial, 'color' => $m->user->color],
                    'default_present' => $m->status === 'actif',
                    'note' => $note,
                ];
            })->values();

        return [
            'group' => ['id' => $group->id, 'name' => $group->name, 'show' => $group->show->card()],
            'episode' => $episode ? ['id' => $episode->id, 'code' => $episode->code(), 'title' => $episode->title] : null,
            'participants' => $participants,
        ];
    }

    public function saveSeance(Request $request, Group $group)
    {
        $this->authorizeMembership($request, $group);

        $data = $request->validate([
            'episode_id' => ['required', 'integer', 'exists:episodes,id'],
            'participant_ids' => ['present', 'array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $episode = Episode::findOrFail($data['episode_id']);
        $newProgressions = 0;

        DB::transaction(function () use ($group, $episode, $data, &$newProgressions) {
            $session = GroupSession::create([
                'group_id' => $group->id, 'episode_id' => $episode->id, 'occurred_on' => now()->toDateString(),
            ]);

            foreach ($data['participant_ids'] as $userId) {
                $member = GroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
                if (! $member) {
                    continue; // only current group members can be logged as present
                }

                GroupSessionParticipant::create(['group_session_id' => $session->id, 'user_id' => $userId]);

                // §3.4: "cocher un épisode depuis l'interface groupe écrit dans la
                // progression individuelle" — never a separate group-owned counter.
                $mark = WatchMarkService::recordViewing($member->user, $episode, $session);
                if ($mark->wasRecentlyCreated) {
                    $newProgressions++;
                }

                if ($member->status !== 'actif') {
                    $member->update(['status' => 'actif']);
                }
            }
        });

        $group->refreshArchivedState();

        return [
            'toast' => '🍿 Séance enregistrée — '.$newProgressions.' progression'.($newProgressions > 1 ? 's' : '').' mise'.($newProgressions > 1 ? 's' : '').' à jour',
        ];
    }

    /**
     * §5.2 "Correction d'une séance" — add/remove participants after the fact,
     * with the progression cascade the spec spells out explicitly.
     */
    public function updateSeance(Request $request, Group $group, GroupSession $session)
    {
        $this->authorizeMembership($request, $group);
        abort_unless($session->group_id === $group->id, 404);

        $data = $request->validate([
            'occurred_on' => ['nullable', 'date'],
            'participant_ids' => ['present', 'array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $before = $session->participants()->pluck('user_id')->all();
        $after = $data['participant_ids'];
        $added = array_diff($after, $before);
        $removed = array_diff($before, $after);

        DB::transaction(function () use ($session, $group, $data, $added, $removed) {
            if (! empty($data['occurred_on'])) {
                $session->update(['occurred_on' => $data['occurred_on']]);
            }

            foreach ($added as $userId) {
                $member = GroupMember::where('group_id', $group->id)->where('user_id', $userId)->first();
                if (! $member) {
                    continue;
                }
                GroupSessionParticipant::firstOrCreate(['group_session_id' => $session->id, 'user_id' => $userId]);
                WatchMarkService::recordViewing($member->user, $session->episode, $session);
            }

            foreach ($removed as $userId) {
                GroupSessionParticipant::where('group_session_id', $session->id)->where('user_id', $userId)->delete();
                $user = User::find($userId);
                if ($user) {
                    WatchMarkService::removeViewing($user, $session->episode);
                }
            }
        });

        return ['ok' => true];
    }

    private function present(Group $group, User $viewer): array
    {
        $group->load(['show.episodes', 'members.user']);

        $episodes = $group->show->episodes; // already ordered by Show::episodes()
        $total = $episodes->count();
        $order = $group->pointCommunOrderIndex();
        $pcEpisode = $order ? $episodes->firstWhere('order_index', $order) : null;

        $members = $group->members->map(function (GroupMember $m) use ($group, $total) {
            $progressOrder = $m->progressOrderIndex($group->show);
            $episode = $progressOrder ? $group->show->episodes->firstWhere('order_index', $progressOrder) : null;

            return [
                'user' => ['id' => $m->user->id, 'name' => $m->user->name, 'initial' => $m->user->initial, 'color' => $m->user->color],
                'statut' => $m->status,
                'pos_code' => $episode?->code(),
                'pct' => $total ? round(($episode?->episode_number ?? 0) / $total * 100) : 0,
            ];
        });

        $bottleneck = $group->bottleneckMembers()->pluck('user.name')->all();
        $nextEpNum = $pcEpisode ? $pcEpisode->episode_number + 1 : 1;

        if ($order === null || $bottleneck === []) {
            $note = 'La troupe démarre tout juste — personne en retard !';
        } elseif (count($bottleneck) === $group->activeMembers()->count()) {
            $note = 'Toute la troupe est synchro — prêts pour la suite !';
        } else {
            $note = 'On attend '.$this->joinNames($bottleneck)." pour l'épisode {$nextEpNum} — pas de spoiler !";
        }

        $geps = $episodes->map(function (Episode $ep) use ($group, $order) {
            $masked = $order === null ? true : $ep->order_index > $order;

            if (! $masked) {
                return ['num' => $ep->code(), 'titre' => $ep->title, 'sub' => 'Vu par toute la troupe', 'pic' => '✅', 'masked' => false];
            }

            $ahead = $group->activeMembers()->filter(
                fn (GroupMember $m) => $m->progressOrderIndex($group->show) >= $ep->order_index
            )->pluck('user.name')->all();

            $sub = $ahead === [] ? 'Chut ! On attend tout le monde' : $this->joinNames($ahead).' déjà vu — on protège les autres';

            return ['num' => $ep->code(), 'titre' => 'Épisode masqué', 'sub' => $sub, 'pic' => $ahead === [] ? '🤫' : '⏳', 'masked' => true];
        });

        $myMembership = $group->members->firstWhere('user_id', $viewer->id);
        $inviteCode = $group->invites()->latest()->value('code');

        $memberIds = $group->members->pluck('user_id')->all();
        $suggestions = $viewer->current_household_id
            ? Household::find($viewer->current_household_id)?->members()
                ->whereNotIn('users.id', $memberIds)
                ->get()->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'initial' => $u->initial, 'color' => $u->color])
            : collect();

        return [
            'group' => [
                'id' => $group->id, 'name' => $group->name, 'show' => $group->show->card(),
                'archived' => $group->isArchived(), 'invite_code' => $inviteCode,
                'is_creator' => $group->created_by === $viewer->id,
                'my_status' => $myMembership?->status,
            ],
            'household_suggestions' => $suggestions,
            'point_commun' => [
                'label' => $pcEpisode ? 'S'.$pcEpisode->season_number.' · épisode '.$pcEpisode->episode_number : 'Pas encore commencé',
                'note' => $note,
            ],
            'members' => $members,
            'episodes' => $geps,
        ];
    }

    private function joinNames(array $names): string
    {
        if (count($names) <= 1) {
            return $names[0] ?? '';
        }

        $last = array_pop($names);

        return implode(', ', $names).' et '.$last;
    }

    private function authorizeMembership(Request $request, Group $group): void
    {
        abort_unless(
            GroupMember::where('group_id', $group->id)->where('user_id', $request->user()->id)->exists(),
            403
        );
    }
}
