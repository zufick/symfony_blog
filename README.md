
## Running locally:
#### Fresh setup:
```bash
composer install && \
cp .env.example .env && \
php bin/console secrets:generate-keys && \
php bin/console asset-map:compile && \
php bin/console cache:pool:clear --all && \
php bin/console doctrine:database:create && \
php bin/console doctrine:schema:update --force && \
php bin/console doctrine:fixtures:load
```

#### Starting server:
```bash
symfony server:start
```

```bash
php bin/console messenger:consume async
```


## Testing:
```bash
php bin/console --env=test doctrine:database:drop --force && \
php bin/console --env=test doctrine:database:create && \
php bin/console --env=test doctrine:schema:update --force && \
php bin/console --env=test doctrine:fixtures:load --append && \
php bin/phpunit --testdox
```