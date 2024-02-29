# Love Letter

This is a tryout project to learn Symfony.

### Deployment

* Run migrations: `docker compose run app php bin/console doctrine:migrations:migrate -n`
* Fix database file permission: `docker compose run app chown -R www-data:www-data /data/`
