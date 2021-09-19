<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Unique extends Constraint
{
    public const TYPE_CREATE = 'create';
    public const TYPE_UPDATE = 'update';

    public string $message = '{{ class }} with {{ property }}: {{ value }} already exists';
    /** @var class-string */
    public string $class;
    public string $property;
    public string $idProperty;
    public ?string $idPath;
    public string $type;

    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        string $property,
        string $idProperty = 'id',
        ?string $idPath = 'uid',
        string $type = self::TYPE_CREATE,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        if (!\in_array($type, [self::TYPE_CREATE, self::TYPE_UPDATE], true)) {
            $message = sprintf(
                'Property "%s" in class "%s" has not valid value "%s", allowed "%s"',
                $property,
                self::class,
                $type,
                implode(', ', [self::TYPE_CREATE, self::TYPE_UPDATE])
            );

            throw new \InvalidArgumentException($message);
        }

        $this->class = $class;
        $this->property = $property;
        $this->type = $type;
        $this->idPath = $idPath;
        $this->idProperty = $idProperty;

        parent::__construct($options, $groups, $payload);
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }
}
