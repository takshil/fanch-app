# Déploiement sur o2switch

Déploiement continu de **Fanch** (API Laravel `api/` + SPA Vue `app/`) vers un
hébergement mutualisé **o2switch** (cPanel / Apache, PHP 8.4, MySQL).

Deux approches sont fournies. **Sur o2switch, le pare-feu de l'hébergeur bloque
généralement les connexions SSH entrantes depuis les runners GitHub** (timeout
sur le port 22) : dans ce cas, utilisez l'approche « pull » ci-dessous.

| Approche | Workflow | Quand l'utiliser |
| -------- | -------- | ---------------- |
| **A. Pull via cPanel Git** (recommandée) | [`o2switch-deploy.yml`](../../.github/workflows/o2switch-deploy.yml) | Cas général. C'est **o2switch qui va chercher le code** (sortant HTTPS, non filtré). Aucune connexion entrante. |
| **B. Push SSH/rsync** (manuelle) | [`deploy-o2switch.yml`](../../.github/workflows/deploy-o2switch.yml) | Seulement si le SSH entrant est ouvert. Déclenchement manuel. |

Voir la **section « Déploiement via cPanel Git (approche A) »** plus bas pour
la mise en place recommandée. Les sections 1 à 5 qui suivent décrivent
l'approche B (SSH).

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

---

## Déploiement via cPanel Git (approche A — recommandée)

Ici, **GitHub ne se connecte jamais au serveur**. Le workflow
[`o2switch-deploy.yml`](../../.github/workflows/o2switch-deploy.yml) construit
tout en CI (Laravel `vendor/` + assets + SPA) et publie un arbre prêt-à-servir
sur la branche **`o2switch-deploy`**. C'est ensuite **o2switch qui récupère
cette branche** (connexion sortante HTTPS vers GitHub, non filtrée) et déploie
en exécutant le fichier [`.cpanel.yml`](./cpanel.yml) présent à sa racine.

```
 push main ─▶ GitHub Actions ─▶ build ─▶ branche o2switch-deploy
                                                 │
                    (o2switch tire le code, sortant HTTPS)
                                                 ▼
                          cPanel Git ─▶ exécute .cpanel.yml ─▶ site en ligne
```

### 1. Personnaliser la recette de déploiement

Éditez [`deploy/o2switch/cpanel.yml`](./cpanel.yml) et renseignez les 3
variables du haut (chemins **absolus** dans votre dossier o2switch) :

```yaml
- export API_PATH="$HOME/api.tyfanch.bzh"   # docroot API = $API_PATH/app/public
- export APP_PATH="$HOME/app.tyfanch.bzh"   # docroot de la SPA
- export PHP="/opt/cpanel/ea-php84/root/usr/bin/php"
```

Ce fichier est copié automatiquement à la racine de la branche
`o2switch-deploy` par le workflow. Après édition, poussez sur `main` : le
workflow régénère la branche.

### 2. Créer les sous-domaines (cPanel)

- `api.<domaine>` → document root `$API_PATH/app/public`
- `app.<domaine>` → document root `$APP_PATH`

> Ces dossiers seront créés au premier déploiement ; vous pourrez ajuster les
> docroots juste après.

### 3. Connecter le dépôt dans cPanel

**cPanel → Git™ Version Control → Créer**, en mode « Cloner un dépôt » :

- **URL du clone** (dépôt privé → jeton d'accès en lecture seule) :
  ```
  https://<TOKEN_GITHUB>@github.com/takshil/fanch-app.git
  ```
  Créez le jeton sur GitHub : *Settings → Developer settings → Personal access
  tokens → Fine-grained*, accès **Contents: Read-only** sur ce dépôt.
- **Chemin du dépôt** : ex. `/home/<compte>/repositories/fanch-app`

Une fois cloné, dans l'onglet **Gérer** du dépôt :
1. **Branche extraite** : sélectionnez `o2switch-deploy`.
2. **Mettre à jour depuis la télécommande** (pull), puis **Déployer le
   HEAD Commit** → exécute `.cpanel.yml`.

### 4. Premier déploiement (config de prod)

Au tout premier déploiement, `.cpanel.yml` crée `shared/.env` depuis le modèle
puis les migrations peuvent échouer tant que la base n'est pas renseignée.
Éditez la config, puis redéployez :

```bash
ssh ...   # ou via le gestionnaire de fichiers cPanel
nano ~/api.tyfanch.bzh/shared/.env         # MySQL, APP_URL, mail...
/opt/cpanel/ea-php84/root/usr/bin/php ~/api.tyfanch.bzh/app/artisan key:generate --show
# -> copiez la valeur base64:... dans APP_KEY
```

Relancez ensuite **Déployer le HEAD Commit** dans cPanel.

### 5. Automatiser le déploiement

cPanel ne reçoit pas de webhook depuis GitHub. Deux options :

- **Cron cPanel** (simple, léger décalage) — pull + déploiement toutes les
  10 min via l'API cPanel :
  ```
  */10 * * * * /usr/local/cpanel/bin/uapi --output=json VersionControl update repository_root="$HOME/repositories/fanch-app" >/dev/null 2>&1 && /usr/local/cpanel/bin/uapi --output=json VersionControlDeployment create repository_root="$HOME/repositories/fanch-app" >/dev/null 2>&1
  ```
  Le déploiement ne s'exécute réellement que s'il y a de nouveaux commits.

- **Déclenchement immédiat via webhook HTTPS** (le port 443 du site, lui, est
  ouvert) : un petit script PHP protégé par un secret, appelé par une étape
  `curl` en fin de workflow, qui lance les deux commandes `uapi` ci-dessus.
  Demandez-le si vous voulez cette variante.

### Notes

- **Aucune compilation côté serveur** : `vendor/` et les assets sont buildés
  en CI et versionnés sur la branche `o2switch-deploy`. Le serveur n'a besoin
  que de **PHP 8.4**.
- **Rollback** : redéployez un commit précédent de `o2switch-deploy` depuis
  l'onglet *Gérer* de cPanel Git, ou re-lancez le workflow sur un ancien SHA.
- La branche `o2switch-deploy` est **générée automatiquement** (historique à
  commit unique reconstruit à chaque build) : ne la modifiez pas à la main.
