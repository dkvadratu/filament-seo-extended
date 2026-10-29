# Filament SEO Extended

A reusable Filament 5 extension for [Ralph J. Smit's Laravel SEO](https://github.com/ralphjsmit/laravel-seo). It adds image upload, canonical URL, robots, and custom JSON-LD to an SEO form. RalphJSmit remains responsible for generating SEO tags.

## Installation

After publishing the repository on GitHub, add it to the application and install it. Replace the URL if your repository has a different name:

```bash
composer config repositories.filament-seo-extended vcs https://github.com/dkvadratu/filament-seo-extended.git
composer require dkvadratu/filament-seo-extended:dev-main
```

Once you have created a release tag, use that version instead of `dev-main`. If the package is published on Packagist, the repository configuration is unnecessary:

```bash
composer require dkvadratu/filament-seo-extended
```

It installs `ralphjsmit/laravel-seo`. The Filament form extension is provided by this package, so `ralphjsmit/laravel-filament-seo` is not required. PHP 8.2+, Laravel 12 or 13, and Filament 5 are required by the selected dependency versions. Composer auto-discovers the service provider.

**For a new site:** publish and run the upstream migration first. Skip these two commands if your application already has the RalphJSmit `seo` table; do not publish a second create-table migration.

```bash
php artisan vendor:publish --tag=seo-migrations
php artisan migrate
```

Then, for both new and existing sites, publish and run this package's migration:

```bash
php artisan vendor:publish --tag=filament-seo-extended-migrations
php artisan migrate
```

The migration adds nullable `schema_json` to the existing `seo` table if absent. It does not copy, overwrite, or remove existing SEO records. The migration fails with a clear message if the upstream table is missing. Its rollback intentionally leaves the column and its contents in place.

Publish the upstream config if your project does not already have `config/seo.php`:

```bash
php artisan vendor:publish --tag=seo-config
```

In `config/seo.php`, set the SEO model to this package's subclass:

```php
'model' => \Dkvadratu\FilamentSeoExtended\Models\Seo::class,
```

This is required for custom JSON-LD to render. If you already use a custom SEO model, extend `Dkvadratu\FilamentSeoExtended\Models\Seo` in that model and keep your existing configured model instead; reconcile any overridden `prepareForUsage()` implementation.

Optional package-specific configuration can be published without overwriting `config/seo.php`:

```bash
php artisan vendor:publish --tag=filament-seo-extended-config
php artisan config:clear
```

## Usage

Models continue to use RalphJSmit's `HasSEO`, and Blade continues to use `seo()->for($model)`. See the [upstream documentation](https://github.com/ralphjsmit/laravel-seo) for complete setup.

In a Filament form schema class:

```php
use Dkvadratu\FilamentSeoExtended\Filament\HasExtendedSeo;

class PageForm
{
    use HasExtendedSeo;

    // Include in your form schema:
    // self::extendedSeo(),
    // Or select fields: self::extendedSeo(['title', 'canonical_url', 'image', 'schema_json']),
}
```

The form reads and saves the existing `seo()` relationship. The image defaults to the `public` disk and `seo` directory; configure these in `config/filament-seo-extended.php`. Configure your public disk and storage link before using uploads. The stored public path follows RalphJSmit's image-path convention.

Enter a JSON object or an array of objects in **Custom JSON-LD**. Valid manual data replaces the model's automatic RalphJSmit `SchemaCollection` for that model. Leave it blank to keep the upstream dynamic schema. JSON syntax and object shape are checked, but schema.org properties and search engine eligibility are not validated. RalphJSmit safely encodes the generated JSON-LD.

## Default schemas and existing Blade blocks

The package cannot automatically suppress scripts emitted independently by a Blade component. Digisimo's Reviews block emits its own JSON-LD, and the layout emits Organization separately. To make a specific default conditional, pass the relevant model to the view or component and guard its existing schema block:

```blade
@if (! \Dkvadratu\FilamentSeoExtended\Schema::hasManual($page))
    {{-- Render this page's default review/organization JSON-LD here. --}}
@endif
```

This is intentionally opt-in: decide which defaults the editor's manual JSON replaces, and keep FAQ or other independent blocks when appropriate. `hasManual` checks the specified model's SEO relationship. Eager-load `seo` if calling this for multiple models. An invalid or empty stored JSON value does not suppress defaults. Ensure the model is actually passed into nested rich-content views; the package does not do this automatically.

For a reusable Organization schema, optionally publish the package config and set `organization.logo`, `email`, `telephone`, `description`, and `same_as`. Name and URL default to `APP_NAME` and `APP_URL`. You can use `Schema::organization()` in a RalphJSmit `SchemaCollection` or safely encode it in a Blade `@php` block. It is not emitted automatically; avoid duplicate Organization markup if your project already renders one.

## Details

- `config/seo.php` remains RalphJSmit's configuration. This package has its own config to avoid overwriting it.
- The extension uses the upstream model's existing table and fields; it does not migrate or duplicate existing records.
- The manual schema takes precedence over the dynamic schema returned through `getDynamicSEOData()` only. Separately rendered page blocks remain under the application's control.
- Deleting uploaded SEO images from the form deletes the underlying file. Avoid sharing a file between records if deletion is enabled.
- Database and file backups are recommended before deploying migrations. Validate generated markup with Google's Rich Results Test or Schema Markup Validator.
