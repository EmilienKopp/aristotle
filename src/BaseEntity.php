<?php

declare(strict_types=1);

namespace Splitstack\Aristotle;

use ArrayIterator;
use Splitstack\Aristotle\Concerns\ArrayLike;
use Splitstack\Aristotle\Contracts\Entity;
use Traversable;

abstract class BaseEntity implements Entity
{
    use ArrayLike;

    public function __construct(mixed ...$data)
    {
        foreach (array_keys(get_object_vars($this)) as $property) {
            if (array_key_exists($property, $data)) {
                $this->{$property} = $data[$property];
            }
        }
    }

    public static function fromArray(array $data): static
    {
        $data = array_filter(
            $data,
            static fn (string $key): bool => property_exists(static::class, $key),
            ARRAY_FILTER_USE_KEY,
        );

        return new static(...$data);
    }

    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }
}
