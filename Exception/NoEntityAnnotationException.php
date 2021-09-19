<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class NoEntityAnnotationException extends \RuntimeException
{
    /**
     * @param class-string $class
     */
    #[Pure]
    public function __construct(string $class, int $code = 0, Throwable $previous = null)
    {
        $message = \sprintf('Class "%s" has no Doctrine\ORM\Mapping\Entity annotation', $class);
        parent::__construct($message, $code, $previous);
    }
}
