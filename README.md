# Filament Address

A Filament v5 plugin that integrates the Danish [Adressevælger API](https://adressevaelger.dk) into your Filament forms. Provides an autocomplete input field for Danish addresses, backed by a polymorphic `Address` model.

## Requirements

- PHP 8.2+
- Laravel 11.28+
- Filament 5+

## Installation

Install via Composer:

```bash
composer require bazuka/filament-address
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=filament-address-migrations
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=filament-address-config
```

Add your API token to `.env`:

```env
FILAMENT_ADDRESS_API_TOKEN=your-token-here
```

Obtain a token from [adressevaelger.dk](https://adressevaelger.dk) or your administrator.

### Custom Theme (required)

This plugin uses Tailwind CSS utility classes, so your Filament panel must have a custom theme. If you haven't created one yet:

```bash
php artisan make:filament-theme
```

Follow the output instructions to register the theme in your panel provider, then add the plugin's views as a Tailwind source in the generated theme CSS file (e.g. `resources/css/filament/admin/theme.css`):

```css
@source '../../../../vendor/bazuka/filament-address/resources/views/**/*';
```

Then rebuild your assets:

```bash
npm run build
```

### Register the plugin in your Filament panel provider:

```php
use Bazuka\FilamentAddress\FilamentAddressPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentAddressPlugin::make(),
        ]);
}
```

## Usage

### 1. Prepare your model

Add the `HasAddresses` trait to any Eloquent model that should have addresses:

```php
use Bazuka\FilamentAddress\Concerns\HasAddresses;

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

Use `AddressInput` in any Filament form. When the user picks a suggestion, the address is automatically saved via the `HasAddresses` trait:

```php
use Bazuka\FilamentAddress\Forms\Components\AddressInput;

AddressInput::make('address')
    ->label('Address')
    ->placeholder('Start typing an address…')
    ->suggestionCount(8),
```

The field calls the Adressevælger API directly from the browser as the user types (debounced at 300 ms).

### 3. Reading the stored address

```php
$customer = Customer::with('address')->find($id);

$customer->address->formatted_address; // "Amaliegade 18, 1256 København K"
$customer->address->street_name;       // "Amaliegade"
$customer->address->postal_code;       // "1256"
$customer->address->city;              // "København K"
```

## Available Address Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `source_id` | string (UUID) | Adressevælger unique address identifier |
| `formatted_address` | string | Full formatted address string |
| `street_name` | string | Street name |
| `house_number` | string | House number |
| `floor` | string\|null | Floor |
| `door` | string\|null | Door |
| `postal_code` | string | Postal code |
| `city` | string | City name |
| `municipality_code` | string | Municipality code |
| `longitude` | float\|null | Longitude |
| `latitude` | float\|null | Latitude |
| `access_address_id` | string\|null | Associated house number identifier |
| `label` | string\|null | Optional label (e.g. `billing`, `shipping`) |

## Configuration

```php
// config/filament-address.php
return [
    'address_model' => \Bazuka\FilamentAddress\Models\Address::class,
    'api_token'     => env('FILAMENT_ADDRESS_API_TOKEN', ''),
];
```

You can swap in your own model as long as it extends `Bazuka\FilamentAddress\Models\Address`:

```php
'address_model' => \App\Models\Address::class,
```

## AddressService

For programmatic address lookup outside of forms:

```php
use Bazuka\FilamentAddress\Services\AddressService;

// Returns up to $count suggestions from the search endpoint
$suggestions = AddressService::addressAutocomplete('Amalieg', 5);

// Fetches the full address record for a given address ID
$adresse = AddressService::addressById('d632f4fc-8190-4462-846d-47391e9a9afe');

// Returns the full address record for the single best text match, or null
$match = AddressService::bestAddressMatch('Amaliegade 18, København');
```

## License

MIT — see [LICENSE](LICENSE).
