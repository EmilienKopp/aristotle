<?php

declare(strict_types=1);

use Splitstack\Aristotle\BaseEntity;

final class ConcreteEntity extends BaseEntity
{
    public string $name = '';
    public int $age = 0;
    public ?string $email = null;
}

it('creates an entity from named arguments', function () {
    $entity = new ConcreteEntity(name: 'Alice', age: 30);

    expect($entity->name)->toBe('Alice')
        ->and($entity->age)->toBe(30);
});

it('creates an entity from array', function () {
    $entity = ConcreteEntity::fromArray(['name' => 'Bob', 'age' => 25]);

    expect($entity->name)->toBe('Bob')
        ->and($entity->age)->toBe(25);
});

it('ignores unknown keys when creating from array', function () {
    $entity = ConcreteEntity::fromArray(['name' => 'Bob', 'age' => 25, 'unknown' => 'value']);

    expect($entity)->toHaveProperty('name', 'Bob')
        ->and(isset($entity['unknown']))->toBeFalse();
});

it('converts to array', function () {
    $entity = ConcreteEntity::fromArray(['name' => 'Alice', 'age' => 30]);

    expect($entity->toArray())->toHaveKeys(['name', 'age', 'email']);
});

it('is iterable', function () {
    $entity = ConcreteEntity::fromArray(['name' => 'Alice', 'age' => 30]);
    $keys = [];
    foreach ($entity as $key => $value) {
        $keys[] = $key;
    }

    expect($keys)->toContain('name', 'age');
});

it('throws on array write access', function () {
    $entity = ConcreteEntity::fromArray(['name' => 'Alice', 'age' => 30]);

    expect(fn () => $entity['name'] = 'Bob')
        ->toThrow(BadMethodCallException::class);
});
