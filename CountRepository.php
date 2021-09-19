<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation;

use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * @codeCoverageIgnore
 */
class CountRepository implements CountRepositoryInterface
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param class-string $class
     */
    public function getCountOf(
        string $class,
        string $property,
        mixed $value,
        string $idProperty,
        mixed $notId = null
    ): int {
        $qb = $this->entityManager->createQueryBuilder();

        if (is_array($value)) {
            $value = array_map(static fn ($v) => is_string($v) ? strtolower($v) : $v, $value);
            $whereExpression = $qb->expr()->in("lower(entity.{$property})", ':value');
        } else {
            $whereExpression = $qb->expr()->eq("lower(entity.{$property})", 'lower(:value)');
        }

        $qb->select($qb->expr()->count("entity.{$idProperty}"))
            ->from($class, 'entity')
            ->where($whereExpression)
            ->setParameter('value', $value)
        ;

        if ($notId) {
            $notIdWhere = $qb->expr()->neq("entity.{$idProperty}", ':notId');
            $qb->andWhere($notIdWhere)->setParameter('notId', $notId);
        }

        try {
            $count = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException) {
            $count = 0;
        } catch (NonUniqueResultException) {
            $count = 1;
        }

        return (int) $count;
    }
}
