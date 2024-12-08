
## Running locally:
#### Fresh setup:
```bash
php bin/console asset-map:compile && \
php bin/console cache:pool:clear --all && \
php bin/console doctrine:database:create && \
php bin/console doctrine:schema:update --force && \
php bin/console doctrine:fixtures:load
```

#### Starting server:
```bash
symfony server:start && \
php bin/console messenger:consume async
```

#### Stopping server:
```bash
symfony server:stop
```


## Testing:
```bash
php bin/console --env=test doctrine:database:drop --force && \
php bin/console --env=test doctrine:database:create && \
php bin/console --env=test doctrine:schema:update --force && \
php bin/console --env=test doctrine:fixtures:load --append && \
php bin/phpunit
```