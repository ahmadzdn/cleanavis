# cleanavis

Site Symfony — CleanAvis.fr (offres Stripe, admin EasyAdmin, Cloudflare Turnstile).

## Installation locale

```bash
cp .env.example .env
composer install
php bin/console doctrine:migrations:migrate --no-interaction
symfony server:start
```

Configurer dans `.env` : base de données, clés Stripe, Turnstile, Google Maps si besoin.
