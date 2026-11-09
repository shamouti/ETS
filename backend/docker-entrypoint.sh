#!/bin/sh
set -e

php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
php bin/console app:seed --no-interaction

exec "$@"
