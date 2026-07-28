# Déploiement sur o2switch

Pipeline de déploiement continu de **Fanch** vers un hébergement mutualisé
**o2switch** (cPanel / Apache, PHP 8.3, MySQL, accès SSH).

Le workflow [`/.github/workflows/deploy-o2switch.yml`](../../.github/workflows/deploy-o2switch.yml)
construit puis déploie automatiquement, à chaque push sur `main` (ou
manuellement) :

- **`api/`** — API Laravel, déployée par rsync/SSH avec une stratégie de
  *releases* + symlink `current` (bascule atomique), `.env` et `storage`
  partagés, migrations et caches Laravel.
- **`app/`** — SPA Vue, buildée par Vite et déployée en statique dans le
  web root, avec un `.htaccess` de repli pour le mode *history*.

---

## 1. Architecture cible

Deux sous-domaines (recommandé, car l'authentification se fait par jeton
Sanctum, sans cookie partagé) :

| Élément | Sous-domaine | Document root (cPanel) |
| ------- | ------------ | ---------------------- |
| SPA Vue | `app.mondomaine.fr`  | `O2SWITCH_APP_PATH` (ex: `/home/moncompte/app.mondomaine.fr`) |
| API Laravel | `api.mondomaine.fr` | `O2SWITCH_API_PATH/current/public` |

Arborescence créée automatiquement par le pipeline pour l'API :

```
O2SWITCH_API_PATH/
├── shared/
│   ├── .env            # configuration de prod (persistante, hors Git)
│   └── storage/        # logs, cache, sessions, fichiers (persistant)
├── releases/
│   ├── 20260728093000/ # une release par déploiement
│   └── 20260728101500/
└── current -> releases/20260728101500   # <- docroot Apache = current/public
```

---

## 2. Préparation côté o2switch (une seule fois)

1. **Activer l'accès SSH** depuis l'espace client o2switch, et récupérer
   l'hôte SSH (ex: `sXX.o2switch.net`) et l'utilisateur cPanel.

2. **Générer une paire de clés SSH** dédiée au déploiement (sur votre poste) :

   ```bash
   ssh-keygen -t ed25519 -C "github-actions-fanch" -f fanch_deploy
   ```

   Ajoutez la **clé publique** (`fanch_deploy.pub`) dans cPanel →
   *Accès SSH* → *Gérer les clés SSH* → *Importer*, puis **autorisez-la**.
   La **clé privée** (`fanch_deploy`) ira dans les secrets GitHub.

3. **Créer la base MySQL** dans cPanel (*Bases de données MySQL*) : une base,
   un utilisateur, un mot de passe, et associez l'utilisateur à la base avec
   tous les privilèges.

4. **Créer les deux sous-domaines** dans cPanel (*Domaines* / *Sous-domaines*) :
   - `app.mondomaine.fr` → dossier `O2SWITCH_APP_PATH`
   - `api.mondomaine.fr` → dossier `O2SWITCH_API_PATH/current/public`

   > Le dossier `current` n'existe qu'après le premier déploiement réussi.
   > Vous pouvez créer le sous-domaine API maintenant et corriger son docroot
   > juste après le premier passage du pipeline.

5. **Sélectionner PHP 8.3** pour les deux sous-domaines dans cPanel
   (*Sélectionner une version de PHP* / MultiPHP), et activer les extensions
   usuelles de Laravel (mbstring, bcmath, pdo_mysql, openssl, tokenizer,
   xml, ctype, curl, fileinfo).

---

## 3. Secrets & variables GitHub

Dans le dépôt : **Settings → Secrets and variables → Actions**.

### Secrets

| Nom | Description |
| --- | ----------- |
| `O2SWITCH_SSH_HOST` | Hôte SSH (ex: `sXX.o2switch.net`) |
| `O2SWITCH_SSH_USER` | Utilisateur cPanel |
| `O2SWITCH_SSH_KEY`  | Contenu **complet** de la clé privée (`fanch_deploy`) |

### Variables

| Nom | Défaut | Description |
| --- | ------ | ----------- |
| `O2SWITCH_API_PATH` | — | Racine de déploiement de l'API (ex: `/home/moncompte/fanch-api`) |
| `O2SWITCH_APP_PATH` | — | Web root de la SPA (ex: `/home/moncompte/app.mondomaine.fr`) |
| `VITE_API_URL` | — | URL publique de l'API utilisée par la SPA (ex: `https://api.mondomaine.fr/api`) |
| `O2SWITCH_SSH_PORT` | `22` | Port SSH |
| `O2SWITCH_PHP_BIN` | `php` | Binaire PHP CLI 8.3 sur le serveur |
| `O2SWITCH_KEEP_RELEASES` | `5` | Nombre de releases conservées |

> **PHP CLI :** si `php` en ligne de commande ne pointe pas vers la 8.3 sur
> votre compte, renseignez le chemin complet dans `O2SWITCH_PHP_BIN`, par
> exemple `/opt/cpanel/ea-php83/root/usr/bin/php`.

---

## 4. Premier déploiement

Le pipeline gère l'amorçage en deux temps (par sécurité, il ne devine pas
votre configuration de production) :

1. **Lancez le workflow** (push sur `main` ou *Run workflow*). Au premier
   passage, aucun `shared/.env` n'existe : le script distant en dépose un à
   partir de [`api/.env.o2switch.example`](../../api/.env.o2switch.example)
   puis **s'arrête volontairement**.

2. **Connectez-vous en SSH** et complétez la configuration de production :

   ```bash
   ssh moncompte@sXX.o2switch.net
   cd ~/fanch-api            # = O2SWITCH_API_PATH
   nano shared/.env          # base MySQL, APP_URL, mail, ...

   # Génère une clé d'application si APP_KEY est vide :
   php releases/*/artisan key:generate --show
   # -> copiez la valeur "base64:..." dans APP_KEY du shared/.env
   ```

3. **Relancez le workflow.** Cette fois migrations, caches et bascule du
   symlink `current` s'exécutent, et la SPA est publiée.

4. **Vérifiez le docroot** du sous-domaine API : il doit pointer sur
   `O2SWITCH_API_PATH/current/public`. Testez la route de santé :
   `https://api.mondomaine.fr/up`.

Les déploiements suivants sont entièrement automatiques.

---

## 5. Notes

- **Zéro dépendance de build sur le serveur** : `composer install --no-dev` et
  les builds Vite tournent sur le runner GitHub. Le serveur mutualisé n'a
  qu'à exécuter `artisan` (migrations + caches). PHP du serveur doit rester en
  **8.3** pour rester compatible avec le `vendor/` construit en CI.
- **CORS** : l'auth utilise un *bearer token* Sanctum ; la config CORS par
  défaut de Laravel (`api/*`, origines `*`) suffit pour des domaines séparés.
  Pour restreindre, publiez `config/cors.php` et limitez `allowed_origins` à
  l'URL de la SPA.
- **File d'attente / tâches planifiées** : en mutualisé, préférez une tâche
  *cron* cPanel appelant `php O2SWITCH_API_PATH/current/artisan schedule:run`
  chaque minute (et, si besoin, `queue:work --stop-when-empty`).
- **Rollback** : chaque release reste dans `releases/`. Pour revenir en
  arrière, re-pointez le symlink `current` :
  ```bash
  ln -sfn ~/fanch-api/releases/<ANCIENNE_RELEASE> ~/fanch-api/current
  ```
- **Déploiement manuel / ponctuel** : le workflow accepte le déclenchement
  `workflow_dispatch` depuis l'onglet *Actions* de GitHub.
