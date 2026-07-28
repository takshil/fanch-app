#!/usr/bin/env bash
#
# Script exécuté SUR le serveur o2switch par le workflow GitHub Actions
# (.github/workflows/deploy-o2switch.yml) après l'envoi du code d'une release.
#
# Il met en place les ressources partagées (.env, storage), lance les
# migrations et la mise en cache Laravel, puis bascule atomiquement le
# symlink `current` vers la nouvelle release.
#
# Variables d'environnement attendues :
#   API_PATH        Racine de déploiement de l'API (ex: /home/moncompte/fanch-api)
#   RELEASE         Identifiant de la release (horodatage)
#   PHP_BIN         Binaire PHP CLI 8.3 (défaut: php)
#   KEEP_RELEASES   Nombre de releases à conserver (défaut: 5)
#
# Arborescence gérée côté serveur :
#   $API_PATH/
#     shared/.env            <- configuration persistante (hors versionnement)
#     shared/storage/        <- storage persistant (logs, cache, sessions, fichiers)
#     releases/<RELEASE>/    <- code de chaque déploiement
#     current -> releases/<RELEASE>   <- racine servie par Apache (docroot: current/public)

set -euo pipefail

API_PATH="${API_PATH:?API_PATH est requis}"
RELEASE="${RELEASE:?RELEASE est requis}"
PHP_BIN="${PHP_BIN:-php}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

RELEASE_DIR="$API_PATH/releases/$RELEASE"
SHARED_DIR="$API_PATH/shared"

echo "==> Déploiement de la release $RELEASE"
mkdir -p "$SHARED_DIR"

# --- .env partagé --------------------------------------------------------
if [ ! -f "$SHARED_DIR/.env" ]; then
  echo "==> Aucun $SHARED_DIR/.env trouvé : première installation."
  if [ -f "$RELEASE_DIR/.env.o2switch.example" ]; then
    cp "$RELEASE_DIR/.env.o2switch.example" "$SHARED_DIR/.env"
  else
    cp "$RELEASE_DIR/.env.example" "$SHARED_DIR/.env"
  fi
  echo "::error:: -> Éditez $SHARED_DIR/.env (base MySQL, APP_KEY, APP_URL, ...)"
  echo "            puis relancez le déploiement. Génération d'une clé :"
  echo "            $PHP_BIN $RELEASE_DIR/artisan key:generate --show"
  exit 1
fi
ln -sfn "$SHARED_DIR/.env" "$RELEASE_DIR/.env"

# --- storage partagé -----------------------------------------------------
# Au premier déploiement, on déplace le squelette storage de la release vers
# le dossier partagé ; ensuite on remplace toujours par un lien symbolique.
if [ ! -d "$SHARED_DIR/storage" ]; then
  echo "==> Initialisation du storage partagé."
  mv "$RELEASE_DIR/storage" "$SHARED_DIR/storage"
fi
rm -rf "$RELEASE_DIR/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_DIR/storage"

# --- Commandes artisan ---------------------------------------------------
cd "$RELEASE_DIR"

echo "==> storage:link"
"$PHP_BIN" artisan storage:link --force || true

echo "==> Migrations (--force)"
"$PHP_BIN" artisan migrate --force

echo "==> Mise en cache (config / route / view / event)"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache || true

# --- Bascule atomique ----------------------------------------------------
echo "==> Bascule du symlink current -> releases/$RELEASE"
ln -sfn "$RELEASE_DIR" "$API_PATH/current"

# --- Nettoyage des anciennes releases ------------------------------------
echo "==> Conservation des $KEEP_RELEASES dernières releases"
cd "$API_PATH/releases"
# Liste des releases (plus récentes d'abord), suppression au-delà du quota.
ls -1dt */ 2>/dev/null | tail -n +"$((KEEP_RELEASES + 1))" | while read -r old; do
  echo "    suppression de $old"
  rm -rf -- "$old"
done

echo "==> Release $RELEASE déployée avec succès."
