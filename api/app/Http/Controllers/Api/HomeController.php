<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupSession;
use App\Services\AVoirService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $household = $user->currentHousehold()->with('members')->first();

        $avoir = AVoirService::items($user);
        $count = count($avoir);

        $ceSoir = null;
        if ($count > 0) {
            $groupItem = collect($avoir)->firstWhere('badge', 'Groupe');
            $detail = null;

            if ($groupItem) {
                $group = Group::find($groupItem['group_id']);
                $bottleneck = $group?->bottleneckMembers()->first();
                if ($bottleneck && $bottleneck->user_id !== $user->id) {
                    $detail = $groupItem['show']['title'].' avec le groupe — '.$bottleneck->user->name
                        ." n'a pas encore vu le ".$groupItem['episode']['code'];
                } else {
                    $detail = $groupItem['show']['title'].' avec le groupe — au tour de tout le monde de rattraper !';
                }
            }

            $ceSoir = [
                'headline' => $count.' épisode'.($count > 1 ? 's' : '').' vous attendent',
                'detail' => $detail,
            ];
        }

        $lastSession = GroupSession::whereHas(
            'group.members', fn ($q) => $q->where('user_id', $user->id)
        )
            ->with(['group.show', 'episode', 'participants.user'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->first();

        $lastSessionSummary = null;
        if ($lastSession) {
            $names = $lastSession->participants->pluck('user.name')->all();
            $lastSessionSummary = [
                'group_id' => $lastSession->group_id,
                'text' => 'Séance '.$lastSession->group->show->title.' '.$lastSession->episode->code()
                    .' avec '.$this->joinNames($names),
            ];
        }

        $modules = [
            ['key' => 'series', 'pic' => '📺', 'title' => 'Séries', 'info' => $count.' à voir ce soir', 'route' => 'a-voir', 'active' => true],
            ['key' => 'cuisine', 'pic' => '🍲', 'title' => 'Cuisine', 'info' => 'Bientôt', 'active' => false],
            ['key' => 'budget', 'pic' => '🐷', 'title' => 'Budget', 'info' => 'Bientôt', 'active' => false],
            ['key' => 'planning', 'pic' => '🗓️', 'title' => 'Planning', 'info' => 'Bientôt', 'active' => false],
            ['key' => 'vacances', 'pic' => '🏖️', 'title' => 'Vacances', 'info' => 'Bientôt', 'active' => false],
            ['key' => 'mediatheque', 'pic' => '📚', 'title' => 'Médiathèque', 'info' => 'Bientôt', 'active' => false],
        ];

        return [
            'household' => ['name' => $household->name],
            'user' => ['name' => $user->name],
            'today_label' => Carbon::now()->locale('fr')->isoFormat('dddd D MMMM'),
            'members' => $household->members->map(fn ($m) => [
                'id' => $m->id, 'name' => $m->name, 'initial' => $m->initial, 'color' => $m->color,
            ]),
            'ce_soir' => $ceSoir,
            'modules' => $modules,
            'last_session' => $lastSessionSummary,
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
}
