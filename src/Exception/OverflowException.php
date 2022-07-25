<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Exception;

final class OverflowException extends \RuntimeException
{
    public function __construct(\GMP $value, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            'Overflow error: value ' . gmp_strval($value) . ' is too large to be represented by a PHP integer',
            $code,
            $previous
        );
    }
}
