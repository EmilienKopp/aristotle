<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Concerns;

use BadMethodCallException;

trait ImmutableArrayable
{
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException(static::class.' is immutable and cannot be modified.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException(static::class.' is immutable and cannot be modified.');
    }
}
