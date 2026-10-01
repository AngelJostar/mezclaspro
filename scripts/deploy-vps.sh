#!/usr/bin/env bash
set +x
set -Eeuo pipefail
umask 077

fail() { printf '%s\n' "$1" >&2; exit 1; }
[[ $# -ge 3 && $# -le 4 ]] || fail 'Uso: deploy-vps.sh RUTA SHA REPOSITORIO [MODELO] (clave API opcional por stdin).'
app_path=$1
commit=$2
repository=$3
model=${4:-}
[[ $app_path = /* && $app_path != / ]] || fail 'La ruta debe ser absoluta y no puede ser la raiz.'
[[ $commit =~ ^[a-f0-9]{40}$ ]] || fail 'SHA de despliegue invalido.'
[[ $repository =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]] || fail 'Repositorio invalido.'
[[ -z $model || $model =~ ^[A-Za-z0-9._:-]{1,100}$ ]] || fail 'Modelo invalido.'

# Consume stdin before invoking git/composer; the secret never becomes a process argument.
api_key=$(cat)
[[ -z $api_key || ( ${#api_key} -ge 19 && ${#api_key} -le 503 && $api_key =~ ^sk-[A-Za-z0-9_-]+$ ) ]] || fail 'Formato del secreto OpenAI invalido.'
for executable in git php composer npm flock; do
    command -v "$executable" >/dev/null || fail "Falta dependencia en VPS: $executable"
done
cd -- "$app_path"
[[ $(pwd -P) != / && -f artisan && -f .env && -f vendor/autoload.php ]] || fail 'La ruta no es una instalacion Laravel existente con .env y vendor.'
[[ $(git rev-parse --show-toplevel) = "$(pwd -P)" ]] || fail 'La ruta debe ser la raiz del repositorio.'
[[ $(git branch --show-current) = main ]] || fail 'El checkout del VPS debe estar en main.'
origin=$(git remote get-url origin)
case "$origin" in
    "https://github.com/$repository"|"https://github.com/$repository.git"|"git@github.com:$repository.git"|"ssh://git@github.com/$repository.git") ;;
    *) fail 'El remoto origin no coincide con el repositorio autorizado.' ;;
esac
git diff --quiet && git diff --cached --quiet || fail 'Hay cambios locales en archivos versionados. No se sobrescribieron.'
if git ls-files --error-unmatch .env >/dev/null 2>&1; then fail '.env no debe estar versionado.'; fi
[[ ! -f storage/framework/down ]] || fail 'El sitio ya esta en mantenimiento. No se modifico ese estado.'

exec 9>storage/framework/deploy.lock
flock -n 9 || fail 'Ya hay otro despliegue en curso.'
export GIT_TERMINAL_PROMPT=0
git fetch --no-tags origin main
[[ $(git rev-parse refs/remotes/origin/main) = "$commit" ]] || fail 'El commit ya no es el ultimo de main. Ejecuta el despliegue mas reciente.'
git merge-base --is-ancestor HEAD "$commit" || fail 'El VPS contiene commits ajenos a main. No se forzo el historial.'

# Deliberately do not run key:generate, migrate:fresh, database imports or forced checkout.
# After a failure the site stays in maintenance: never publish partially migrated code.
trap 'printf "%s\n" "Despliegue interrumpido. Revisa GitHub Actions; el sitio puede seguir en mantenimiento. No se revirtio la base de datos." >&2' ERR
php artisan down --retry=60 --no-interaction
git merge --ff-only "$commit"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
php artisan config:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction
php artisan migrate --force --no-interaction
php artisan db:seed --class=PromesaAiAgentsSeeder --force --no-interaction
php artisan clinical:import-manual resources/clinical/manual-v4.docx --manual-version=4 --no-interaction
provider_args=(agents:configure-openai --from-stdin --verify --no-interaction)
if [[ -n $model ]]; then provider_args+=("--model=$model"); fi
printf '%s' "$api_key" | php artisan "${provider_args[@]}"
unset api_key
if [[ ! -e public/storage && ! -L public/storage ]]; then php artisan storage:link --no-interaction; fi
php artisan config:cache --no-interaction
php artisan view:cache --no-interaction
php artisan queue:restart --no-interaction
php artisan up --no-interaction
printf 'Despliegue terminado: %s\n' "$commit"
