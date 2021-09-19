<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Tests\Constraint;

use FRZB\Component\ExtendedValidation\Constraint\Unique;
use FRZB\Component\ExtendedValidation\Tests\Fixtures\Entity\Employee;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \FRZB\Component\ExtendedValidation\Constraint\Unique
 */
class UniqueTest extends TestCase
{
    public function testConstructor(): void
    {
        $constraint = new Unique(Employee::class, 'email', 'id', 'uid', Unique::TYPE_UPDATE);

        self::assertEquals(Employee::class, $constraint->class);
        self::assertEquals('email', $constraint->property);
        self::assertEquals('id', $constraint->idProperty);
        self::assertEquals('uid', $constraint->idPath);
        self::assertEquals(Unique::TYPE_UPDATE, $constraint->type);
    }

    public function testConstructorWhenInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Unique(class: Employee::class, property: 'email', type: 'UndefinedType');
    }
}
