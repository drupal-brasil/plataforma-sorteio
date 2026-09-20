# Deploy no GCP

O ambiente de produção é mapeado em `web/sites/sites.php` para
`web/sites/sorteio.drupal.tec.br`. O projeto deve ser instalado em
`/srv/www/sorteio`, com o `root` do Nginx apontando para
`/srv/www/sorteio/web`. A raiz do projeto não deve ser exposta pelo Nginx.

O banco de produção é SQLite em `/srv/www/sorteio/database/sorteio.sqlite`.
O usuário do PHP-FPM precisa ler e gravar esse arquivo e seu diretório-pai,
além de gravar em `web/sites/sorteio.drupal.tec.br/files` e
`/srv/www/sorteio/private`.

Antes da primeira requisição, crie `/etc/sorteio/hash_salt` com um valor longo
e aleatório, legível somente pelo usuário do PHP-FPM, ou forneça
`DRUPAL_HASH_SALT` ao PHP-FPM. O salt não deve ser versionado.

Exemplos usando o alias do Drush:

```sh
vendor/bin/drush @sorteio.prod status
vendor/bin/drush @sorteio.prod cr
vendor/bin/drush @sorteio.prod cim -y
```

O último comando só deve ser executado quando o banco estiver pronto. O
ambiente local do Lando continua usando o alias `lndo.site` e MySQL.
