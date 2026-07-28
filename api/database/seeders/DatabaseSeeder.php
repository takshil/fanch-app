<?php

namespace Database\Seeders;

use App\Models\Episode;
use App\Models\Group;
use App\Models\GroupInvite;
use App\Models\GroupMember;
use App\Models\GroupSession;
use App\Models\GroupSessionParticipant;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\HouseholdMembership;
use App\Models\Show;
use App\Models\ShowTracking;
use App\Models\ShowVisibility;
use App\Models\User;
use App\Models\WatchlistItem;
use App\Models\WatchMark;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $household = Household::create(['name' => 'Chez les Kerbrat']);

        HouseholdInvite::create(['household_id' => $household->id, 'code' => 'FANCH-7K2M']);

        $camille = User::create([
            'current_household_id' => $household->id,
            'name' => 'Camille', 'initial' => 'C', 'color' => '#F0B091',
            'email' => 'camille@exemple.fr', 'password' => Hash::make(str()->random(40)),
            'visibility_global' => 'foyers',
        ]);
        $marie = User::create([
            'current_household_id' => $household->id,
            'name' => 'Marie', 'initial' => 'M', 'color' => '#AECBA0',
            'email' => 'marie@exemple.fr', 'password' => Hash::make(str()->random(40)),
            'visibility_global' => 'foyers',
        ]);
        $papi = User::create([
            'current_household_id' => $household->id,
            'name' => 'Papi', 'initial' => 'P', 'color' => '#A9C3E3',
            'email' => 'papi@exemple.fr', 'password' => Hash::make(str()->random(40)),
            'visibility_global' => 'foyers',
        ]);
        $jules = User::create([
            'current_household_id' => $household->id,
            'name' => 'Jules', 'initial' => 'J', 'color' => '#C9AEDC',
            'email' => 'jules@exemple.fr', 'password' => Hash::make(str()->random(40)),
            'visibility_global' => 'foyers',
        ]);

        HouseholdMembership::create(['user_id' => $camille->id, 'household_id' => $household->id, 'role' => 'admin', 'joined_at' => now()->subYear()]);
        HouseholdMembership::create(['user_id' => $marie->id, 'household_id' => $household->id, 'role' => 'membre', 'joined_at' => now()->subYear()]);
        HouseholdMembership::create(['user_id' => $papi->id, 'household_id' => $household->id, 'role' => 'membre', 'joined_at' => now()->subYear()]);
        HouseholdMembership::create(['user_id' => $jules->id, 'household_id' => $household->id, 'role' => 'enfant', 'joined_at' => now()->subYear()]);

        // §3.1: a compte can belong to several foyers — Papi also keeps his own place,
        // and it's his admin household, distinct from "Chez les Kerbrat" where he's a membre.
        $papiHousehold = Household::create(['name' => 'Chez Papi']);
        HouseholdInvite::create(['household_id' => $papiHousehold->id, 'code' => 'FANCH-PAPI01']);
        HouseholdMembership::create(['user_id' => $papi->id, 'household_id' => $papiHousehold->id, 'role' => 'admin', 'joined_at' => now()->subYears(2)]);

        // --- Severance ---
        $severance = Show::create([
            'slug' => 'severance', 'title' => 'Severance', 'platform' => 'Apple TV+',
            'genre' => 'Drame, SF', 'status' => 'En cours', 'seasons_count' => 2, 'color' => '#A9C3E3',
        ]);
        $severanceS2Titles = [
            'Hello, Ms. Cobel', 'Goodbye, Mrs. Selvig', 'Who Is Alive?', "Woe's Hollow",
            "Trojan's Horse", 'Attila', 'Chikhai Bardo', 'Sweet Vitriol', 'The After Hours',
        ];
        $severanceEpisodes = [];
        foreach ($severanceS2Titles as $i => $title) {
            $n = $i + 1;
            $severanceEpisodes[] = Episode::create([
                'show_id' => $severance->id, 'season_number' => 2, 'episode_number' => $n,
                'order_index' => 2000 + $n, 'title' => $title, 'runtime_minutes' => 58,
                'air_date' => $n === 8 ? '2026-07-30' : null,
            ]);
        }
        // Camille has watched episodes 1-6, next up is E7 "Chikhai Bardo".
        foreach (array_slice($severanceEpisodes, 0, 6) as $ep) {
            WatchMark::create(['user_id' => $camille->id, 'episode_id' => $ep->id, 'watched_at' => now()->subDays(3)]);
        }

        // --- Slow Horses (household watch-together group) ---
        $slowHorses = Show::create([
            'slug' => 'slow-horses', 'title' => 'Slow Horses', 'platform' => 'Apple TV+',
            'genre' => 'Thriller', 'status' => 'En cours', 'seasons_count' => 4, 'color' => '#AECBA0',
        ]);
        $slowHorsesTitles = [
            'Identity Theft', 'Un inconnu en ville', 'Négociations', 'Les règles du jeu',
            'Trahison', 'Fin de partie', 'Le Chant du cygne',
        ];
        $slowHorsesEpisodes = [];
        foreach ($slowHorsesTitles as $i => $title) {
            $n = $i + 1;
            $slowHorsesEpisodes[] = Episode::create([
                'show_id' => $slowHorses->id, 'season_number' => 4, 'episode_number' => $n,
                'order_index' => 4000 + $n, 'title' => $title, 'runtime_minutes' => 47,
                'air_date' => $n === 7 ? '2026-07-31' : null,
            ]);
        }

        // Groups are a set of ACCOUNTS, not a household resource (cahier des charges §3.4) —
        // Camille created it and it happens that everyone here is in her household, but the
        // model doesn't require that.
        $group = Group::create([
            'show_id' => $slowHorses->id, 'created_by' => $camille->id, 'name' => 'Les mardis série 🍿',
        ]);
        GroupInvite::create(['group_id' => $group->id, 'code' => 'GROUPE-SLOWH1']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $camille->id, 'status' => 'actif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $marie->id, 'status' => 'actif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $papi->id, 'status' => 'actif']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $jules->id, 'status' => 'pause']);

        // Last night's session: Marie & Papi watched S4E2 together. This WRITES their
        // individual progression (§3.3) — it is not a separate group-owned counter.
        $session = GroupSession::create([
            'group_id' => $group->id, 'episode_id' => $slowHorsesEpisodes[1]->id, 'occurred_on' => now()->subDay()->toDateString(),
        ]);
        GroupSessionParticipant::create(['group_session_id' => $session->id, 'user_id' => $marie->id]);
        GroupSessionParticipant::create(['group_session_id' => $session->id, 'user_id' => $papi->id]);

        // Progression per member, giving: Camille S4·E3, Marie S4·E4, Papi S4·E2, Jules S4·E1.
        // E2 for Marie & Papi is attributed to last night's session; the rest were solo.
        $this->watchSolo($camille, [$slowHorsesEpisodes[0], $slowHorsesEpisodes[1], $slowHorsesEpisodes[2]]);
        $this->watchSolo($marie, [$slowHorsesEpisodes[0], $slowHorsesEpisodes[3]]);
        WatchMark::create(['user_id' => $marie->id, 'episode_id' => $slowHorsesEpisodes[1]->id, 'group_session_id' => $session->id, 'watched_at' => now()->subDay()]);
        $this->watchSolo($marie, [$slowHorsesEpisodes[2]]);
        $this->watchSolo($papi, [$slowHorsesEpisodes[0]]);
        WatchMark::create(['user_id' => $papi->id, 'episode_id' => $slowHorsesEpisodes[1]->id, 'group_session_id' => $session->id, 'watched_at' => now()->subDay()]);
        $this->watchSolo($jules, [$slowHorsesEpisodes[0]]);

        // --- Dix pour cent ---
        $dixPourCent = Show::create([
            'slug' => 'dix-pour-cent', 'title' => 'Dix pour cent', 'platform' => 'Netflix',
            'genre' => 'Comédie dramatique', 'status' => 'Terminée', 'seasons_count' => 4, 'color' => '#C9AEDC',
        ]);
        Episode::create(['show_id' => $dixPourCent->id, 'season_number' => 3, 'episode_number' => 1, 'order_index' => 3001, 'title' => 'Gérard', 'runtime_minutes' => 52]);
        Episode::create(['show_id' => $dixPourCent->id, 'season_number' => 3, 'episode_number' => 2, 'order_index' => 3002, 'title' => 'Camille', 'runtime_minutes' => 52, 'air_date' => '2026-08-04']);

        // --- Lupin ---
        $lupin = Show::create([
            'slug' => 'lupin', 'title' => 'Lupin', 'platform' => 'Netflix',
            'genre' => 'Thriller', 'status' => 'En cours', 'seasons_count' => 4, 'color' => '#EBCB8F',
        ]);
        Episode::create(['show_id' => $lupin->id, 'season_number' => 4, 'episode_number' => 1, 'order_index' => 4101, 'title' => 'Pilote', 'runtime_minutes' => 45, 'air_date' => '2026-08-04']);

        // --- Watchlist-only shows ---
        $dark = Show::create(['slug' => 'dark', 'title' => 'Dark', 'platform' => 'Netflix', 'genre' => 'SF', 'status' => 'Terminée', 'seasons_count' => 3, 'color' => '#B7AB96']);
        $bcs = Show::create(['slug' => 'better-call-saul', 'title' => 'Better Call Saul', 'platform' => 'AMC', 'genre' => 'Drame', 'status' => 'Terminée', 'seasons_count' => 6, 'color' => '#EBCB8F']);
        $stationEleven = Show::create(['slug' => 'station-eleven', 'title' => 'Station Eleven', 'platform' => 'HBO Max', 'genre' => 'Mini-série', 'status' => 'Terminée', 'seasons_count' => 1, 'color' => '#AECBA0']);

        // --- Catalogue-only shows (discoverable via Recherche, not yet followed by anyone) ---
        Show::create(['slug' => 'shetland', 'title' => 'Shetland', 'platform' => 'BBC One', 'genre' => 'Policier', 'premiered_year' => 2013, 'status' => 'En cours', 'seasons_count' => 9, 'color' => '#A9C3E3']);
        Show::create(['slug' => 'shrinking', 'title' => 'Shrinking', 'platform' => 'Apple TV+', 'genre' => 'Comédie', 'premiered_year' => 2023, 'status' => 'En cours', 'seasons_count' => 2, 'color' => '#EBCB8F']);
        Show::create(['slug' => 'sherlock', 'title' => 'Sherlock', 'platform' => 'BBC One', 'genre' => 'Policier', 'premiered_year' => 2010, 'status' => 'Terminée', 'seasons_count' => 4, 'color' => '#C9AEDC']);

        foreach ([$dark, $bcs, $stationEleven] as $show) {
            WatchlistItem::create(['user_id' => $camille->id, 'show_id' => $show->id]);
        }

        // --- "À voir" tracking (shows actively in progress for Camille) ---
        foreach ([$severance, $slowHorses, $dixPourCent] as $show) {
            ShowTracking::create(['user_id' => $camille->id, 'show_id' => $show->id]);
        }

        // --- Visibility exceptions for Camille ---
        ShowVisibility::create(['user_id' => $camille->id, 'show_id' => $lupin->id, 'visibility' => 'privee']);
        ShowVisibility::create(['user_id' => $camille->id, 'show_id' => $slowHorses->id, 'visibility' => 'visible']);
    }

    /** @param  array<Episode>  $episodes */
    private function watchSolo(User $user, array $episodes): void
    {
        foreach ($episodes as $episode) {
            WatchMark::create(['user_id' => $user->id, 'episode_id' => $episode->id, 'watched_at' => now()->subDays(3)]);
        }
    }
}
