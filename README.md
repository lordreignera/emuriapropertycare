# ETOGO Property Care

ETOGO is a Laravel application for regenerative property care workflows. It supports client property registration, property facts and diagnosis invoices, inspections, PHAR findings, client quotations, work payments, trade partner onboarding, tool assignments, and vendor-neutral digital twin/Matterport evidence.

## Stack

- Laravel 12, PHP 8.2
- Jetstream, Fortify, Sanctum, Livewire
- Spatie roles and permissions
- Laravel Cashier and Stripe
- Vite, Tailwind CSS, Three.js
- DomPDF and PhpSpreadsheet

## Local Setup

1. Install PHP and Composer dependencies:

   ```bash
   composer install
   ```

2. Install frontend dependencies:

   ```bash
   npm install
   ```

3. Copy and configure the environment:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Run migrations and seeders:

   ```bash
   php artisan migrate --seed
   ```

5. Build or run assets:

   ```bash
   npm run build
   npm run dev
   ```

6. Start the app:

   ```bash
   php artisan serve
   ```

## Stripe Notes

Stripe keys are read from `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET`. Subscription checkout requires each tier to have `stripe_price_id_monthly` and `stripe_price_id_annual` populated. Use `Database\Seeders\StripeProductSeeder` or update tiers manually before enabling subscription checkout.

Inspection diagnosis payments currently use the intentional test charge configured in the diagnosis pricing flow while the project is still in testing.

## Digital Twin Storage

ETOGO does not convert point clouds or 3D capture files inside the Laravel app. Store heavy source files, converted tiles, and browser-ready twin assets in a cloud provider such as AWS S3, CloudFront, Azure Blob Storage, Matterport, or another capture platform. The app stores the property twin metadata, issue markers, provider IDs, uploaded cloud-disk paths, and external cloud URLs.

AWS S3 is supported by the current dependencies through `league/flysystem-aws-s3-v3`. To use Azure Blob Storage as the Laravel filesystem disk, add the appropriate Flysystem Azure adapter and configure `FILESYSTEM_DISK` for that disk.

## Testing

Run the test suite with Xdebug disabled for much faster local runs:

```bash
XDEBUG_MODE=off php artisan test
```

On Windows PowerShell:

```powershell
$env:XDEBUG_MODE='off'; php artisan test
```
