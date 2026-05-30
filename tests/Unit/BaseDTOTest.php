<?php

declare(strict_types=1);

use Splitstack\Aristotle\BaseDTO;

final class ConcreteDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly ?int $user_id = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            name: $data['name'],
            age: $data['age'],
            user_id: $data['user_id'] ?? null,
        );
    }
}

it('creates a DTO from array', function () {
    $dto = ConcreteDTO::fromArray(['name' => 'Alice', 'age' => 30]);

    expect($dto->name)->toBe('Alice')
        ->and($dto->age)->toBe(30);
});

it('converts to array', function () {
    $dto = ConcreteDTO::fromArray(['name' => 'Alice', 'age' => 30]);

    expect($dto->toArray())->toBe(['name' => 'Alice', 'age' => 30, 'user_id' => null]);
});

it('is iterable', function () {
    $dto = ConcreteDTO::fromArray(['name' => 'Bob', 'age' => 25]);
    $result = [];
    foreach ($dto as $key => $value) {
        $result[$key] = $value;
    }

    expect($result)->toHaveKeys(['name', 'age']);
});

it('casts numeric _id fields to int from validatable', function () {
    $source = new class {
        public function validated(): array
        {
            return ['name' => 'Alice', 'age' => 30, 'user_id' => '42'];
        }
    };

    $dto = ConcreteDTO::fromValidatable($source);

    expect($dto->user_id)->toBe(42)->toBeInt();
});

it('throws on mutation via array access', function () {
    $dto = ConcreteDTO::fromArray(['name' => 'Alice', 'age' => 30]);

    expect(fn () => $dto['name'] = 'Bob')
        ->toThrow(BadMethodCallException::class);
});
