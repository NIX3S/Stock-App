#!/bin/bash
set -e

echo "Import des migrations..."

for file in /docker-entrypoint-initdb.d/migrations/*.sql; do
    echo "-> $file"
    mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "$file"
done

echo "Import des seeds..."

for file in /docker-entrypoint-initdb.d/seeds/*.sql; do
    echo "-> $file"
    mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "$file"
done

echo "Initialisation terminée."