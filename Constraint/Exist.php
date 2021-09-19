<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Exist extends Constraint
{
    public string $message = '{{ class }} with {{ property }}: {{ value }} not exists';
    /** @var class-string */
    public string $class;
    public string $property;
    public string $idProperty;

    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        string $property,
        string $idProperty = 'id',
        mixed $options = null,
        array $groups = null,
        mixed $payload = null
    ) {
        $this->class = $class;
        $this->property = $property;
        $this->idProperty = $idProperty;
        parent::__construct($options, $groups, $payload);
    }
}
