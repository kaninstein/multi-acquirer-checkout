# kaninstein/multi-acquirer-checkout

Headless Laravel package for processing checkouts with multiple payment gateways (fallback pipeline), plus fee calculation.

## Install (local path)

Add a path repository in your Laravel app `composer.json`:

```json
{
  "repositories": [
    { "type": "path", "url": "../multi-acquirer-checkout", "options": { "symlink": true } }
  ]
}
```

Then require the package:

```bash
composer require kaninstein/multi-acquirer-checkout:*
```

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=multi-acquirer-config
```

Optional package migrations:

```env
MULTI_ACQUIRER_ROUTES_ENABLED=true
MULTI_ACQUIRER_DATABASE_USE_PACKAGE_MIGRATIONS=true
```

```bash
php artisan vendor:publish --tag=multi-acquirer-migrations
php artisan migrate
```

## API (optional)

Enable routes:

```env
MULTI_ACQUIRER_ROUTES_ENABLED=true
```

Endpoint:
- `POST /api/multi-acquirer/checkout`

Payload example:

```json
{
  "amount_cents": 10000,
  "payment_method": "pix",
  "installments": 1,
  "customer": { "name": "John", "email": "john@example.com" }
}
```
