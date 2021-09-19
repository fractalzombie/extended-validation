<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation;

interface CountRepositoryInterface
{
    /**
     * @param class-string $class
     */
    public function getCountOf(string $class, string $property, mixed $value, string $idProperty, mixed $notId = null): int;
}
