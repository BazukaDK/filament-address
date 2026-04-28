# Filament DAWA

A Filament v5 plugin that integrates the Danish Address Web API ([DAWA](https://dawadocs.dataforsyningen.dk/)) into your Filament forms. Provides an autocomplete input field for Danish addresses, backed by a polymorphic `Address` model.

## Requirements

- PHP 8.2+
- Laravel 10+
- Filament 5+

## Installation

Install via Composer:

```bash
composer require bazuka/filament-dawa
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=filament-dawa-migrations
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=filament-dawa-config
```

Register the plugin in your Filament panel provider:

```php
use Bazuka\FilamentDawa\FilamentDawaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentDawaPlugin::make(),
        ]);
}
```

## Usage

### 1. Prepare your model

Add the `HasAddresses` trait to any Eloquent model that should have addresses:

```php
use Bazuka\FilamentDawa\Concerns\HasAddresses;

class Customer extends Model
{
    use HasAddresses;
}
```

This gives the model two relationships:

| Method | Returns | Description |
|--------|---------|-------------|
| `address()` | `MorphOne` | The primary (first) address |
| `addresses()` | `MorphMany` | All addresses |

### 2. Add the form field

Use `DawaInput` in any Filament form. When the user picks a suggestion, the address is automatically saved via the `HasAddresses` trait:

```php
use Bazuka\FilamentDawa\Forms\Components\DawaInput;

DawaInput::make('address')
    ->label('Address')
    ->placeholder('Start typing an address…')
    ->suggestionCount(8),
```

The field calls the DAWA autocomplete API directly from the browser as the user types (debounced at 300 ms). No API key is required.

### 3. Reading the stored address

```php
$customer = Customer::with('address')->find($id);

$customer->address->formatted_address; // "Amaliegade 18, 1256 København K"
$customer->address->street_name;       // "Amaliegade"
$customer->address->postal_code;       // "1256"
$customer->address->city;              // "København K"
$customer->address->latitude;          // 55.6810...
$customer->address->longitude;         // 12.5856...
```

## Available Address Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `dawa_id` | string (UUID) | DAWA unique address identifier |
| `formatted_address` | string | Full formatted address string |
| `street_name` | string | Street name |
| `house_number` | string | House number |
| `floor` | string\|null | Floor |
| `door` | string\|null | Door |
| `postal_code` | string | Postal code |
| `city` | string | City name |
| `municipality_code` | string | Municipality code |
| `longitude` | float\|null | WGS84 longitude |
| `latitude` | float\|null | WGS84 latitude |
| `access_address_id` | string\|null | DAWA access address reference |
| `label` | string\|null | Optional label (e.g. `billing`, `shipping`) |

## Configuration

```php
// config/dawa.php
return [
    'address_model' => \Bazuka\FilamentDawa\Models\Address::class,
];
```

You can swap in your own model as long as it extends `Bazuka\FilamentDawa\Models\Address`:

```php
'address_model' => \App\Models\Address::class,
```

## DawaService

For programmatic address lookup outside of forms:

```php
use Bazuka\FilamentDawa\Services\DawaService;

// Returns up to $count suggestions
$suggestions = DawaService::addressAutocomplete('Amalieg', 5);

// Returns the single best match, or null
$match = DawaService::bestAddressMatch('Amaliegade 18, København');
```

## License

MIT — see [LICENSE](LICENSE).
