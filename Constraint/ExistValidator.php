<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Constraint;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\ORM\Mapping\Entity;
use FRZB\Component\ExtendedValidation\CountRepositoryInterface as CountRepository;
use FRZB\Component\ExtendedValidation\Exception\NoEntityAnnotationException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ExistValidator extends ConstraintValidator
{
    private CountRepository $countRepository;
    private AnnotationReader $annotationReader;

    public function __construct(CountRepository $countRepository, AnnotationReader $annotationReader)
    {
        $this->countRepository = $countRepository;
        $this->annotationReader = $annotationReader;
    }

    /**
     * @throws NoEntityAnnotationException
     * @throws \InvalidArgumentException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Exist) {
            throw new UnexpectedTypeException($constraint, Exist::class);
        }

        if (!$value) {
            return;
        }

        if (!is_string($value) && !is_float($value) && !is_int($value) && !is_array($value)) {
            throw new \InvalidArgumentException('Value must be string, int, float, double or array');
        }

        $entityClass = $constraint->class;
        $entityClassShortName = $this->getClassShortName($entityClass);
        $entityProperty = $constraint->property;
        $entityPropertyValue = $value;
        $entityIdProperty = $constraint->idProperty;
        $entityAnnotation = $this->getEntityAnnotation($entityClass);
        $constraintValue = is_array($entityPropertyValue)
            ? implode(',', $entityPropertyValue)
            : (string) $entityPropertyValue;

        if (!$entityAnnotation instanceof Entity) {
            throw new NoEntityAnnotationException($entityClass);
        }

        $count = $this->countRepository
            ->getCountOf($entityClass, $entityProperty, $entityPropertyValue, $entityIdProperty)
        ;

        if (count(is_array($entityPropertyValue) ? $entityPropertyValue : [$entityPropertyValue]) !== $count) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ class }}', $entityClassShortName)
                ->setParameter('{{ property }}', $constraint->property)
                ->setParameter('{{ value }}', $constraintValue)
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
}
