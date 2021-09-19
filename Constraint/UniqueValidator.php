<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Constraint;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use FRZB\Component\ExtendedValidation\CountRepositoryInterface as CountRepository;
use FRZB\Component\ExtendedValidation\Exception\NoEntityAnnotationException;
use FRZB\Component\ExtendedValidation\HasRepositoryCountOfMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueValidator extends ConstraintValidator
{
    public const ALLOWED_COUNT_WHEN_CREATE = 0;
    public const ALLOWED_COUNT_WHEN_UPDATE = 1;

    private EntityManager $entityManager;
    private CountRepository $countRepository;
    private AnnotationReader $annotationReader;
    private ?Request $request;

    public function __construct(
        EntityManager $entityManager,
        CountRepository $countRepository,
        AnnotationReader $annotationReader,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->countRepository = $countRepository;
        $this->annotationReader = $annotationReader;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @throws NoEntityAnnotationException
     * @throws \InvalidArgumentException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, Unique::class);
        }

        if (!$value) {
            return;
        }

        if (!\is_string($value) && !\is_float($value) && !\is_int($value)) {
            throw new \InvalidArgumentException('Value must be string, int, float, double');
        }

        $entityClass = $constraint->class;
        $entityClassShortName = $this->getClassShortName($entityClass);
        $entityProperty = $constraint->property;
        $entityPropertyValue = $value;
        $entityIdProperty = $constraint->idProperty;
        $entityAnnotation = $this->getEntityAnnotation($entityClass);
        $entityId = $this->request?->attributes->get($constraint->idPath ?? '');

        if (!$entityAnnotation instanceof Entity) {
            throw new NoEntityAnnotationException($entityClass);
        }

        $repository = $this->getEntityRepository($entityClass);

        $count = $repository instanceof HasRepositoryCountOfMethod
            ? $repository->getCountOf($entityProperty, $entityPropertyValue, $entityIdProperty, $entityId)
            : $this->countRepository->getCountOf($entityClass, $entityProperty, $entityPropertyValue, $entityIdProperty, $entityId);

        $allowedCountWhenUpdate = null !== $entityId ? self::ALLOWED_COUNT_WHEN_CREATE : self::ALLOWED_COUNT_WHEN_UPDATE;
        $isNotUniqueWhenCreate = $count > self::ALLOWED_COUNT_WHEN_CREATE && $constraint->isType(Unique::TYPE_CREATE);
        $isNotUniqueWhenUpdate = $count > $allowedCountWhenUpdate && $constraint->isType(Unique::TYPE_UPDATE);

        if ($isNotUniqueWhenCreate || $isNotUniqueWhenUpdate) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ class }}', $entityClassShortName)
                ->setParameter('{{ property }}', $constraint->property)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation()
            ;
        }
    }

    /**
     * @param class-string $class
     */
    private function getClassShortName(string $class): string
    {
        try {
            return (new \ReflectionClass($class))->getShortName();
        } catch (\ReflectionException) {
            return 'UndefinedClass';
        }
    }

    /**
     * @param class-string $class
     */
    private function getEntityAnnotation(string $class): ?Entity
    {
        try {
            $reflectionClass = new \ReflectionClass($class);
        } catch (\ReflectionException) {
            return null;
        }

        return $this->annotationReader->getClassAnnotation($reflectionClass, Entity::class);
    }

    /**
     * @param class-string $entityClass
     */
    private function getEntityRepository(string $entityClass): ?ObjectRepository
    {
        try {
            $repository = $this->entityManager->getRepository($entityClass);
        } catch (MappingException) {
            $repository = null;
        }

        return $repository;
    }
}
