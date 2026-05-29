<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Contracts;

use ArrayAccess;

/**
 * @extends ArrayAccess<int|string, mixed>
 */
interface DTO extends ArrayAccess
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
