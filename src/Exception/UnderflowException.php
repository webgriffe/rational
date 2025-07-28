<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Exception;

use Webgriffe\Rational\Rational;

final class UnderflowException extends \RuntimeException
{
    private $closestApproximation;

    public function __construct(\GMP $value, Rational $closestApproximation, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            'Underflow error: value ' . gmp_strval($value) . ' is too large to be represented by a PHP integer',
            $code,
            $previous
        );

        $this->closestApproximation = $closestApproximation;
    }

    public function getClosestApproximation(): Rational
    {
        return $this->closestApproximation;
    }
}
