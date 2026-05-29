<?php

declare(strict_types=1);

namespace Splitstack\Aristotle;

use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use Splitstack\Aristotle\Concerns\ImmutableArrayable;
use Splitstack\Aristotle\Contracts\DTO;
use Splitstack\Aristotle\Contracts\HasValidatedData;
use Traversable;

/**
 * @implements IteratorAggregate<string, mixed>
 */
abstract class BaseDTO implements DTO, IteratorAggregate
{
    use ImmutableArrayable;

    /** @param array<string, mixed> $data */
    abstract public static function fromArray(array $data): static;

    final public static function fromValidatable(HasValidatedData $source, int|string|null $id = null): static
    {
        $data = (array) $source->validated();
        $data['id'] ??= $id;

        foreach ($data as $key => $value) {
            if (str_ends_with((string) $key, '_id') && is_numeric($value)) {
                $data[$key] = (int) $value;
            }
        }

        return static::fromArray($data);
    }

    /**
     * @param  Arrayable<int|string, mixed>  $entity
     */
    final public static function fromEntity(Arrayable $entity): static
    {
        return static::fromArray($entity->toArray());
    }

    /** @return array<string, mixed> */
    final public function toArray(): array
    {
        return get_object_vars($this);
    }

    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }
}
