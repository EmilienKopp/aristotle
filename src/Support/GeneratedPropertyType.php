<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Support;

final readonly class GeneratedPropertyType
{
    /**
     * @param  list<string>  $imports
     */
    public function __construct(
        public string $type,
        public array $imports = [],
    ) {}
}
