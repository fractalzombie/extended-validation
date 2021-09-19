<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Tests\Fixtures\Entity;

use Doctrine\ORM\EntityRepository;
use FRZB\Component\ExtendedValidation\HasRepositoryCountOfMethod;

/**
 * @template-extends EntityRepository<Employee>
 *
 * @codeCoverageIgnore
 */
class EmployeeRepository extends EntityRepository implements HasRepositoryCountOfMethod
{
    public function getCountOf(string $property, mixed $value, string $idProperty, mixed $notId = null): int
    {
        try {
            return random_int(0, 1);
        } catch (\Exception) {
            return rand(0, 1);
        }
    }
}
