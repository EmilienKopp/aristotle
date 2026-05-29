<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Concerns;

use ArrayIterator;
use BadMethodCallException;
use Traversable;

trait ArrayLike
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) && isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException(static::class.' properties are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException(static::class.' properties are immutable.');
    }
}
