# Aristotle

DDD entity scaffolding and base classes for Laravel applications.

Aristotle generates typed, immutable entity classes from your Eloquent models by reading the database schema and respecting your model's `$casts`. Entities implement `Arrayable`, `ArrayAccess`, and `IteratorAggregate` out of the box.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require splitstack/aristotle
```

The service provider is auto-discovered. Optionally publish the config and/or stub:

```bash
php artisan vendor:publish --tag=aristotle-config
php artisan vendor:publish --tag=aristotle-stubs
```

## Generating an entity

```bash
php artisan make:entity
```

The command will prompt you to choose a model and a domain. You can also pass everything as options:

```bash
php artisan make:entity User --model=User --domain=Auth
```

### Options

| Option         | Description                                            |
| -------------- | ------------------------------------------------------ |
| `--model=`     | Eloquent model to scaffold from                        |
| `--domain=`    | Bounded context / domain folder                        |
| `--namespace=` | Override the full namespace (skips the domain prompt)  |
| `--database=`  | Database to connect to (useful in multi-tenant setups) |
| `--force`      | Overwrite the entity if it already exists              |

The command reads the model's table columns and `$casts`, then writes a typed entity class. For example, given a `users` table with `id`, `name`, `email`, and `email_verified_at` columns, the output is:

```php
// app/Domain/Auth/Entities/UserEntity.php

namespace App\Domain\Auth\Entities;

use Carbon\CarbonInterface;
use Splitstack\Aristotle\BaseEntity;

final class UserEntity extends BaseEntity
{
    public function __construct(
        public ?int $id = null,
        public string $name,
        public string $email,
        public ?CarbonInterface $email_verified_at = null,
    ) {}
}
```

## Using an entity

```php
$user = UserEntity::fromArray($model->toArray());

$user->name;          // typed access
$user['email'];       // ArrayAccess
foreach ($user as $key => $value) { ... }   // iterable
$user->toArray();     // back to array
```

Entities are **immutable** — attempting to set or unset a property via array access throws a `BadMethodCallException`.

## Configuration

```php
// config/aristotle.php

return [
    // Root namespace for all domains
    'namespace' => 'App\\Domain',

    // Sub-folder appended after the domain name
    // e.g. "Entities" → App\Domain\Auth\Entities
    // Set to null or '' to place entities directly under the domain
    'entities_folder' => 'Entities',

    // Optional suffix appended to generated class names
    // e.g. "Entity" → UserEntity
    'entity_suffix' => env('ARISTOTLE_ENTITY_SUFFIX', 'Entity'),
];
```

## Type resolution

Column types and Eloquent casts are mapped to PHP types automatically:

| Cast / column type                            | PHP type                             |
| --------------------------------------------- | ------------------------------------ |
| `int`, `integer`                              | `int`                                |
| `bool`, `boolean`                             | `bool`                               |
| `float`, `double`, `real`, `decimal`          | `float` / `string`                   |
| `string`, `encrypted`, `hashed`               | `string`                             |
| `date`, `datetime`, `immutable_date/datetime` | `CarbonInterface`                    |
| `array`, `json`, `encrypted:array`            | `array`                              |
| `collection`, `encrypted:collection`          | `Collection`                         |
| `object`, `encrypted:object`                  | `object`                             |
| `timestamp`                                   | `int`                                |
| `AsStringable`                                | `Stringable`                         |
| `AsUri`                                       | `Uri`                                |
| `AsFluent`                                    | `Fluent`                             |
| Enum cast                                     | enum type                            |
| Custom `CastsAttributes`                      | resolved from `@implements` docblock |

Nullable columns and auto-increment columns are generated as nullable (`?Type = null`).

## Extending the stub

After publishing the stub (`vendor:publish --tag=aristotle-stubs`), edit `stubs/entity.stub`. The available placeholders are `{{ namespace }}`, `{{ imports }}`, `{{ class }}`, and `{{ properties }}`.

## License

MIT
