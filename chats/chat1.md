# Design app mobile Fanch

_Started 2026-07-28 04:50 UTC_

---

## User

Depuis mon cahier des charges pour fanch dans notions, peux-tu me faire un design pour une application mobile ?

## Assistant

_[tool: notion__notion-search]_

_[tool: tool_search_tool_bm25]_

_[tool: notion__notion-fetch]_

_[tool: questions_v2]_

## User

Questions answered:
- screens: Accueil / À voir (prochains épisodes), Fiche série (saisons, épisodes, marquage vu), Groupe de visionnage (point commun, anti-spoiler), Enregistrement d'une séance (qui était présent), Recherche / ajout depuis le catalogue, Calendrier « à venir », Watchlist, Réglages de visibilité / vie privée, Onboarding (compte, foyer, invitation)
- nb_screens: Decide for me
- platform: Écrans nus côte à côte
- fidelity: Hi-fi avec vraies données d'exemple (séries réelles fictives)
- interactive: Prototype cliquable (navigation entre écrans)
- variations: Non, une seule direction
- nav: Prévoir déjà l'entrée multi-modules (accueil Fanch + module séries)
- tone: Chaleureux / familial
- langue: Français

## Assistant

_[tool: read_skill_prompt]_

_[tool: read_file]_

## Assistant

## User

Peux tu continuer ?

## Assistant

_[tool: notion__notion-fetch]_

_[tool: update_todos]_

_[tool: dc_write]_

## Assistant

_[tool: dc_write]_

_[tool: update_todos]_

_[tool: ready_for_verification]_

Prototype cliquable prêt : 10 écrans (onboarding, accueil multi-modules, à voir, fiche série, groupe avec point commun et anti-spoiler, séance, recherche TVmaze, calendrier, watchlist, réglages de visibilité) dans un cadre 390×844, système Modernist. Navigation par le rail de gauche ou dans l'app ; les coches, séances et réglages sont fonctionnels. Trois tweaks : écran initial, masquage anti-spoiler, « Papi a rattrapé » (recalcul du point commun). Il manque de vraies affiches — envoyez-moi des visuels si vous en voulez.

## User

Tu peux me faire un design plus sympa. Pour le menu en bas, j’aimerais qu'il dépende du module dans lequel on se trouve

## Assistant

_[tool: dc_write]_

_[tool: ready_for_verification]_

V2 créée (la V1 est conservée). Ce qui change :
- **Barre du bas contextuelle** : case « F » fixe à gauche (retour accueil, rouge quand on y est) ; sur l'accueil → Accueil / Réglages ; dans le module séries → À voir / Agenda / Recherche / Watchlist, avec un bandeau noir « Module · Séries » sous la barre de statut.
- Accueil repensé : héros rouge « Bonsoir, Camille », bloc noir « Ce soir », modules en liste numérotée 01–06.
- À voir en cartes encadrées avec numéro d'épisode en gros, case « vu » intégrée.
- Fiche série : barre de progression ; groupe : barres de position par membre.

Dites-moi si vous vouliez d'autres onglets dans l'un des deux contextes.

## User

Refais un tout nouveau design. Celui-là ne me convient pas du tout. Je veux quelque chose de familial

## Assistant

_[tool: read_skill_prompt]_

_[tool: dc_write]_

_[tool: ready_for_verification]_

_[tool: snip]_

V3 en ligne, direction chaleureuse et familiale : papier crème, terracotta et vert sauge, coins arrondis, avatars colorés du foyer (Camille, Marie, Papi, Jules), typo Bricolage Grotesque + Nunito, wording complice (« On en est où ? », « Qui est sur le canapé ? », « Pour plus tard »). La barre du bas reste contextuelle : maison Fanch d'un côté, onglets séries de l'autre, avec le bouton « f » pour revenir.

Les mêmes 10 écrans et interactions sont conservés. V1 et V2 restent dans le projet si vous voulez comparer.

## User

Mets des couleurs pastels

## Assistant

_[tool: run_script]_

_[tool: ready_for_verification]_

Palette passée en pastel : pêche, vert amande, bleu ciel, lavande et miel clair, avec les textes en brun foncé pour rester lisibles. Dites-moi si certains blocs vous semblent trop clairs (barres de progression notamment) et j'ajusterai le contraste.

