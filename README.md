# ContiApp

Applicazione Symfony 6.4 per la gestione delle transazioni personali, preparata per ambienti `dev` e `prod` e pronta al deploy su Render o Railway.

## Sviluppo locale

L'ambiente locale usa MySQL via Docker Compose e mantiene `APP_ENV=dev` come default:

```bash
docker compose up -d
```

Valori locali principali:

- `.env`: default condivisi non sensibili
- `.env.dev`: override di sviluppo
- `.env.local`: override macchina-specifici non versionati

Il database locale espone `127.0.0.1:3307` ed e` coerente con le migration Doctrine del progetto.

## Produzione

Il deploy PaaS usa immagine Docker con Nginx + PHP-FPM + Supervisor. I valori da impostare lato piattaforma o in `.env.prod.local` sono:

- `APP_SECRET`
- `DATABASE_URL`
- `APP_DEFAULT_URI`
- `ASSET_VERSION`
- `TRUSTED_PROXIES`
- `TRUSTED_HOSTS`
- `MYSQL_SSL_CA` oppure `MYSQL_SSL_CA_BASE64`

Il file [.env.prod](/Volumes/NETAC/WORKS/works/contiApp/.env.prod:1) contiene placeholder sicuri e supporta proxy fidati, host fidati e SSL MySQL per Aiven.

## Deploy Docker

File principali:

- [Dockerfile](/Volumes/NETAC/WORKS/works/contiApp/Dockerfile:1)
- [docker/entrypoint.sh](/Volumes/NETAC/WORKS/works/contiApp/docker/entrypoint.sh:1)
- [docker/nginx/nginx.conf](/Volumes/NETAC/WORKS/works/contiApp/docker/nginx/nginx.conf:1)
- [docker/nginx/default.conf.template](/Volumes/NETAC/WORKS/works/contiApp/docker/nginx/default.conf.template:1)
- [docker/supervisor/supervisord.conf](/Volumes/NETAC/WORKS/works/contiApp/docker/supervisor/supervisord.conf:1)
- [render.yaml](/Volumes/NETAC/WORKS/works/contiApp/render.yaml:1)
- [railway.json](/Volumes/NETAC/WORKS/works/contiApp/railway.json:1)

Build locale immagine:

```bash
docker build -t contiapp .
```

Esecuzione locale con immagine prod:

```bash
docker run --rm -p 8080:8080 \
  -e APP_ENV=prod \
  -e APP_DEBUG=0 \
  -e APP_SECRET=change-me \
  -e APP_DEFAULT_URI=http://localhost:8080 \
  -e TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR \
  -e TRUSTED_HOSTS='^localhost$' \
  -e DATABASE_URL='mysql://contiapp:contiapp@host.docker.internal:3307/contiapp_dev?serverVersion=8.0.36&charset=utf8mb4' \
  contiapp
```

## Verifiche

Verifiche applicative:

```bash
composer check:prod
composer deploy
```

Verifica health endpoint:

```bash
curl http://127.0.0.1:8080/healthz
curl http://127.0.0.1:8080/healthz/deep
```

## Note operative

- gli asset Twig usano `ASSET_VERSION`, quindi puoi invalidare la cache incrementando il valore a ogni release
- `config/packages/prod/` contiene cache Doctrine/Twig, trusted proxies e trusted hosts
- `config/packages/doctrine.yaml` abilita il CA SSL MySQL in `prod`, utile per Aiven
- `.github/workflows/deploy.yml` valida Composer, lint Symfony, builda l'immagine e può triggerare Render via deploy hook
