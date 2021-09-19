<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation;

interface HasRepositoryCountOfMethod
{
    public function getCountOf(string $property, mixed $value, string $idProperty, mixed $notId = null): int;
}
