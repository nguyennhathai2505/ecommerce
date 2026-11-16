# Production deployment

This Compose setup runs Laravel and FastAPI as separate application services on one VPS. Caddy is the only service published to the host; it obtains and renews HTTPS certificates for `APP_DOMAIN`. MySQL, Redis, RabbitMQ, and Qdrant are external services and are not exposed or managed by this Compose project.

## Prepare the host

1. Point the domain's DNS A/AAAA records to the VPS and allow inbound TCP ports 80 and 443 (UDP 443 is optional for HTTP/3).
2. Provide private, reachable endpoints for MySQL, Redis, RabbitMQ, and Qdrant. Restrict those services to the VPS/VPC, enable authentication/TLS where supported, and allowlist the VPS as a client.
3. Create two MySQL databases and least-privilege users: one for Laravel and one for recommendation data.
4. Copy `.env.production.example` to `.env.production`, `deploy/laravel.env.production.example` to `deploy/laravel.env.production`, and `deploy/fastapi.env.production.example` to `deploy/fastapi.env.production`. Replace every example value. Keep the actual files out of version control.
5. Use the same `REVERB_APP_KEY` in the Laravel environment file and `VITE_REVERB_APP_KEY` in `.env.production`; set matching Reverb domain and credentials.
6. Generate a Laravel key before the first deployment with `php artisan key:generate --show` (or `docker compose --env-file .env.production run --rm laravel php artisan key:generate --show` after the image has been built) and place the output in `APP_KEY`.

FastAPI supports Redis username/password and TLS (`REDIS_SSL`), plus Qdrant API key and HTTPS (`QDRANT_API_KEY`, `QDRANT_HTTPS`). Its MySQL connection uses a SQLAlchemy URL object so passwords containing reserved URL characters are handled correctly. Laravel's RabbitMQ package configuration in this repository uses its configured host/port credentials; keep RabbitMQ on a private network unless TLS is configured separately.

## Build and start

Build and publish immutable images from CI using the release's `IMAGE_PREFIX` and `IMAGE_TAG`. On the VPS, authenticate to the registry and run these commands from the repository root:

```sh
docker compose --env-file .env.production config --quiet
docker compose --env-file .env.production pull
docker compose --env-file .env.production up -d --no-build
docker compose --env-file .env.production ps
docker compose --env-file .env.production logs -f caddy laravel fastapi
```

For a small deployment without CI, build on the VPS with `docker compose --env-file .env.production build` instead of pulling images.

Apply Laravel migrations after the external database is ready:

```sh
docker compose --env-file .env.production run --rm laravel php artisan migrate --force
```

FastAPI currently has SQLAlchemy models but no migration framework. For a first install, create its mapped tables with:

```sh
docker compose --env-file .env.production run --rm fastapi python -c "from app.database import Base, engine; import app.models; Base.metadata.create_all(bind=engine)"
```

For ongoing schema changes, introduce and run a migration tool (for example Alembic) rather than relying on `create_all`.

The website is served at `https://APP_DOMAIN`; FastAPI is reverse-proxied under `https://APP_DOMAIN/ai/` (for example `/ai/api/v1/health/db`), and Reverb WebSockets use the same domain. App containers have no published host ports.

## Release and operations

Set `IMAGE_PREFIX` to the organization's registry namespace and `IMAGE_TAG` to an immutable release identifier (for example, the Git commit SHA). Build and test that image in CI, then deploy the same tag. Back up the external databases and object/media storage independently. Keep the Compose env files and Caddy's `caddy_data` volume persistent and protected. Monitor container health and logs; do not run database migrations automatically in the web container's startup command.
