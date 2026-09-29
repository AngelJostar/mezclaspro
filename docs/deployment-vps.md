# Despliegue GitHub -> VPS

## Alcance y estado

El repositorio incluye `.github/workflows/deploy-vps.yml`. Comprueba el codigo de
soporte clinico con HTTP simulado, compila los assets y despliega al recibir un
push en `main` (incluido un merge). Los pull requests solo ejecutan pruebas; no
reciben secretos de produccion. Tambien puede ejecutarse manualmente desde Actions
sobre `main`.

**Preparado en codigo, no conectado aun a produccion.** Faltan la ruta, el usuario
SSH y los secretos del VPS. No se ha realizado un despliegue remoto. GitHub aloja
el codigo; el servidor que ejecuta Laravel es el VPS. Un push por si solo no copia
datos locales, archivos subidos, claves ni configuraciones de base de datos.

## Configuracion inicial (una sola vez)

En GitHub, repositorio `AngelJostar/mezclaspro`, Settings -> Secrets and variables
-> Actions, registrar estos secrets (o usar el environment `production` si el plan
permite environment secrets en ese repositorio):

| Secret | Contenido |
| --- | --- |
| `VPS_HOST` | IP o nombre del VPS, sin protocolo |
| `VPS_USER` | Usuario SSH dedicado, no root |
| `VPS_PATH` | Ruta absoluta al checkout existente de Mezclaspro, sin espacios |
| `VPS_SSH_PRIVATE_KEY` | Clave privada SSH dedicada a despliegues |
| `VPS_KNOWN_HOSTS` | Entrada known_hosts del VPS verificada con su proveedor/administrador |
| `OPENAI_API_KEY` | Clave API del proyecto OpenAI, completa; no la contrasena de ChatGPT |

Variables opcionales de Actions: `VPS_PORT` (22 por defecto) y
`OPENAI_AGENT_MODEL` (si falta, conserva el modelo del VPS; en una instalacion sin
modelo usa `gpt-4.1-mini`). No poner secretos en variables ordinarias ni en
archivos YAML. No usar `VITE_OPENAI_API_KEY` ni incorporar una clave a JavaScript.

`OPENAI_API_KEY` puede omitirse solo si el VPS ya tiene una clave en su base de
datos o entorno. No se exporta la clave local automaticamente: debe aprovisionarse
por este canal seguro una vez. Si se mantiene el secreto en GitHub, este pasa a
ser la fuente de la clave en cada despliegue; para rotarla, actualizar el secreto.
No imprimir claves en chats, comandos con argumentos, capturas ni logs.

Requisitos del VPS Linux:

- Checkout Git existente en `main`, con remoto `origin` del mismo repositorio y
  acceso de lectura a GitHub (deploy key de solo lectura si el repo es privado).
- PHP compatible con el proyecto (verificado con PHP 8.3), Composer 2, Node >=18,
  npm, Git, Bash, flock y extensiones PHP requeridas por Composer, incluida zip.
- `.env` de produccion existente: base de datos, APP_ENV=production,
  APP_DEBUG=false, APP_URL HTTPS y el **APP_KEY original de ese VPS**.
- Usuario de despliegue con permisos en el checkout, `storage`, `bootstrap/cache`
  y `public/build`; mismos permisos/grupo de lectura y escritura que requiera PHP.
  No usar chmod 777. El script no crea usuarios ni configura permisos del sistema.
- Conexion HTTPS de salida a OpenAI, GitHub y registros de dependencias. Respaldo
  comprobado de base de datos, .env y storage antes del primer despliegue.
- Document root del servidor web en `public`; nunca servir .env, vendor, resources
  ni los documentos fuente. OPcache debe comprobar cambios de archivos; si esta
  configurado sin comprobacion, el administrador debe integrar la recarga de PHP-FPM
  en su despliegue antes de habilitar este workflow.

La llave publica correspondiente a `VPS_SSH_PRIVATE_KEY` debe estar en el
authorized_keys del usuario de despliegue. Verificar la huella del VPS por un
canal independiente antes de guardar `VPS_KNOWN_HOSTS`; el workflow no acepta
automaticamente hosts desconocidos ni desactiva StrictHostKeyChecking.
Restringir el environment de produccion a main y proteger la rama. Revisar los
cambios en workflows: quien modifica un workflow autorizado puede usar sus secretos.

## Que hace cada merge

1. Pruebas aisladas y build en GitHub, sin consumir OpenAI ni tocar pacientes.
2. Conexion SSH verificada al VPS y bloqueo exclusivo para evitar despliegues
   simultaneos. Rechaza otra rama, otro remoto, cambios locales o un SHA obsoleto.
3. Mantenimiento temporal y avance fast-forward al commit probado, sin reset ni
   checkout forzado. Instala dependencias con lockfiles y reconstruye JavaScript/CSS.
4. Limpia caches de codigo y ejecuta `migrate --force` sobre la base del VPS.
   No importa la base local ni ejecuta migrate:fresh o key:generate.
5. Completa agentes PROMESA y carga el manual incluido en resources/clinical.
   No duplica el documento, sobreescribe instrucciones personalizadas, reactiva un
   agente apagado ni aprueba fuentes clinicas.
6. Recibe la clave OpenAI por stdin sobre SSH, verifica acceso al modelo mediante
   GET /v1/models/{modelo} y la cifra con el APP_KEY del VPS. No se envia al navegador,
   a Git, a un artifact ni como argumento de un proceso. Un error no sustituye la
   clave anterior. Si la clave/modelo no cambiaron, conserva ciphertext y timestamps.
7. Regenera caches, crea public/storage solo si falta, solicita reinicio a los
   workers de cola y levanta la aplicacion. Queue workers necesitan su supervisor
   existente para reiniciarse. El scheduler de Laravel debe seguir instalado; el
   agente clinico es bajo demanda, no se programa automaticamente.

La comprobacion del modelo confirma acceso, no saldo disponible ni suficiencia
clinica. La cuota OpenAI y las aprobaciones de protocolos siguen siendo condiciones
externas, no se pueden garantizar mediante un push. Las credenciales de usuarios,
mensajes, archivos y demas registros existentes permanecen en la base/storage del VPS.
El manual institucional se incluye sin cambios; no incluye expedientes ni claves.

## Fallos y recuperacion

El workflow falla claramente si faltan secretos o requisitos. Nunca fuerza cambios
locales ni cambia de repositorio/rama. Si falla despues de entrar en mantenimiento,
**no levanta automaticamente una version parcialmente migrada** ni revierte la base.
El administrador debe revisar Actions y resolver la causa antes de ejecutar
`php artisan up` y reintentar el workflow de main. Un mantenimiento que ya existia
antes del deploy se respeta y debe resolverlo su responsable.

No reemplazar APP_KEY para reparar errores de descifrado: se perderia acceso a claves
y conversaciones cifradas. Restaurar la clave original desde un respaldo seguro.
No copiar ciphertext de la base local a otra instalacion; el comando de despliegue
cifra de nuevo la clave API en el servidor de destino.

## Verificacion local

Las pruebas PHP deben ejecutarse con APP_ENV=testing, DB_CONNECTION=sqlite,
DB_DATABASE=:memory:, CACHE_DRIVER=array, SESSION_DRIVER=array y una APP_KEY de
prueba. Usar `--no-configuration` para evitar la base MySQL indicada en phpunit.xml.

```sh
php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Feature/OpenAiDeploymentTest.php
node --test tests/Deployment/deploy-vps.test.cjs
bash -n scripts/deploy-vps.sh
```

Las pruebas de despliegue simulan Git, PHP, Composer y npm en una carpeta temporal;
no se conectan al VPS. No ejecutar el script real contra desarrollo para probarlo.

Referencias:
- https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets
- https://developers.openai.com/api/docs/guides/production-best-practices
- https://laravel.com/docs/10.x/deployment
