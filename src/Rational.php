<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

use DivisionByZeroError;
use Webgriffe\Rational\Exception\OverflowException;

final class Rational
{
    private function __construct(
        private readonly int $whole,
        private readonly int $num = 0,
        private readonly int $den = 1)
    {
    }

    public static function zero(): static
    {
        return self::fromWhole(0);
    }

    public static function one(): static
    {
        return self::fromWhole(1);
    }

    public static function fromWhole(int $value): static
    {
        return new static($value);
    }

    /**
     * @throws OverflowException
     * @throws DivisionByZeroError
     */
    public static function fromFraction(int $num, int $den): static
    {
        return self::normalizeAllAndCreate(0, $num, $den);
    }

    /**
     * @throws OverflowException
     * @throws DivisionByZeroError
     */
    public static function fromWholeAndFraction(int $whole, int $num, int $den): static
    {
        return self::normalizeAllAndCreate($whole, $num, $den);
    }

    public function getWholePart(): int
    {
        return $this->whole;
    }

    public function getFractionPart(): array
    {
        return [$this->num, $this->den];
    }

    public function isZero(): bool
    {
        return $this->whole === 0 && $this->num === 0;
    }

    public function isPositive(): bool
    {
        return $this->whole > 0 || $this->num > 0;
    }

    public function isNegative(): bool
    {
        return $this->whole < 0 || $this->num < 0;
    }

    public function isWhole(): bool
    {
        return $this->num === 0;
    }

    public function equals(Rational $other): bool
    {
        return $this->whole === $other->whole && $this->num === $other->num && $this->den === $other->den;
    }

    /**
     * @throws OverflowException
     */
    public function add(Rational $other): static
    {
        //Given two rationals a + b/c and d + e/f (where a, b, c, d, e and f are all integers, c > 0, f > 0, a * b >= 0
        //and d * e >= 0), their sum is given by:
        //a + b/c + d + e/f
        //= (a + d) + b/c + e/f
        //= (a + d) + (b*f + e*c)/(c*e)
        $whole = gmp_add($this->whole, $other->whole);
        $num = gmp_add(
            gmp_mul($this->num, $other->den),
            gmp_mul($other->num, $this->den),
        );
        $den = gmp_mul($this->den, $other->den);

        //The fractional part may be an improper fraction. If so, extract the whole part from it
        self::extractWholePartFromFraction($whole, $num, $den);

        //Simplify the fraction
        self::simplify($num, $den);

        //Make sure that the sign of the whole part and that of the numerator do not disagree
        //Since the initial denominators cannot be zero, the new value cannot be zero either. So this call cannot
        //generate a DivisionByZeroError
        self::normalizeSigns($whole, $num, $den);

        return new static(self::toInt($whole), self::toInt($num), self::toInt($den));
    }

    /**
     * @throws OverflowException
     */
    public function sub(Rational $other): static
    {
        return $this->add($other->neg());
    }

    /**
     * @throws OverflowException
     */
    public function mul(Rational $other): static
    {
        //Given two rationals a + b/c and d + e/f (where a, b, c, d, e and f are all integers, c > 0, f > 0, a * b >= 0
        //and d * e >= 0), their product is given by:
        //(a + b/c) * (d + e/f)
        //= a*d + (a*e)/f + (d*b)/c + (b*e)/(c*f)
        //= a*d + ((a*e*c) + (d*b*f) + (b*e))/(c*f)
        $whole = gmp_mul($this->whole, $other->whole);
        $num = gmp_add(
            gmp_add(
                gmp_mul(gmp_mul($this->whole, $other->num), $this->den),
                gmp_mul(gmp_mul($other->whole, $this->num), $other->den),
            ),
            gmp_mul($this->num, $other->num),
        );
        $den = gmp_mul($this->den, $other->den);

        //If the starting data had its signs normalized, then here we can be sure that the signs are correct too.

        //The fractional part may be an improper fraction. If so, extract the whole part from it. Notice that this may
        //cause the whole part and/or the numerator to change from signed to zero or vice versa, but it cannot cause
        //either of these to invert its sign. So the signs remain consistent after this operation.
        self::extractWholePartFromFraction($whole, $num, $den);

        //Simplify the fraction
        self::simplify($num, $den);

        return new static(self::toInt($whole), self::toInt($num), self::toInt($den));
    }

    /**
     * @throws OverflowException
     */
    public function div(Rational $other): static
    {
        return $this->mul($other->recip());
    }

    /**
     * @throws OverflowException
     * @throws DivisionByZeroError
     */
    public function recip(): static
    {
        //1 / (a + b/c)
        //= 1 / ((a*c + b)/c)
        //= c/(a*c + b)
        $newDen = gmp_add(gmp_mul($this->whole, $this->den), $this->num);

        return self::normalizeAllAndCreate(0, $this->den, self::toInt($newDen));
    }

    /**
     * @throws OverflowException
     */
    public function neg(): static
    {
        if ($this->whole === PHP_INT_MIN) {
            throw new OverflowException(gmp_neg(gmp_init($this->whole)));
        } elseif ($this->num === PHP_INT_MIN) {
            throw new OverflowException(gmp_neg(gmp_init($this->num)));
        }

        //No need to normalize again, just invert the whole part and the numerator
        return new static(-$this->whole, -$this->num, $this->den);
    }

    /**
     * @throws OverflowException
     */
    public function abs(): static
    {
        if ($this->whole === PHP_INT_MIN) {
            throw new OverflowException(gmp_neg(gmp_init($this->whole)));
        } elseif ($this->num === PHP_INT_MIN) {
            throw new OverflowException(gmp_neg(gmp_init($this->num)));
        }

        //No need to normalize again, just extract the absolute value of both the whole part and the numerator
        return new static(abs($this->whole), abs($this->num), $this->den);
    }

    public function format(int $decimals, ?string $decimal_separator = null, ?string $thousands_separator = null): string
    {
        if ($decimals < 0) {
            throw new \RuntimeException('The number of decimals cannot be negative');
        }

        $whole = $this->whole;

        $rounded = round((float)$this->num / (float)$this->den, $decimals);
        if ($rounded === 1.0) {
            ++$whole;
            $rounded = 0.0;
        } elseif ($rounded === -1.0) {
            --$whole;
            $rounded = 0.0;
        }

        $sign = '';
        $decimalPart = '';
        if ($rounded !== 0.0) {
            //Remove the initial part, leaving only the row of decimal digits
            $matches = [];
            preg_match("/^(-?)0\.(.*)$/", (string) $rounded, $matches);
            $sign = $matches[1];
            $decimalPart = $matches[2];
        }

        $result = number_format($whole, 0, $decimal_separator, $thousands_separator);
        if ($decimalPart) {
            $result .= ($decimal_separator ?: '.') . $decimalPart;
        }

        if ($whole === 0) {
            //If the whole part has no sign, then the sign of the decimal part dominates
            $result = $sign . $result;
        }

        return $result;
    }

    /**
     * @throws OverflowException
     * @throws DivisionByZeroError
     */
    private static function normalizeAllAndCreate(int $whole, int $num = 0, int $den = 1): self
    {
        //The denominator can only be positive. Obviously it cannot be zero. If it is negative, then change sign to
        //both the numerator and denominator
        if ($den === 0) {
            throw new DivisionByZeroError();
        } elseif ($den < 0) {
            $num = -$num;
            $den = -$den;
        }

        $whole = gmp_init($whole);
        $num = gmp_init($num);
        $den = gmp_init($den);

        self::extractWholePartFromFraction($whole, $num, $den);

        //Simplify the fraction, if possible
        self::simplify($num, $den);

        //Make sure that the sign of the whole part and that of the numerator do not disagree
        self::normalizeSigns($whole, $num, $den);

        return new static(self::toInt($whole), self::toInt($num), self::toInt($den));
    }

    private static function extractWholePartFromFraction(\GMP& $whole, \GMP& $num, \GMP& $den): void
    {
        //If the fraction is an improper fraction (|num| > den), then compute the whole part of that and add it to the
        //actual whole part
        $additionalWholePart = gmp_div($num, $den);
        if (gmp_cmp($additionalWholePart, 0) !== 0)
        {
            $whole = gmp_add($whole, $additionalWholePart);
            $num = gmp_sub($num, gmp_mul($additionalWholePart, $den));
        }
    }

    private static function simplify(\GMP& $num, \GMP& $den): void
    {
        //Simplify the fraction, if possible
        $gcd = gmp_gcd($num, $den);
        if (gmp_cmp($gcd, 1) > 0) {
            $num = gmp_div($num, $gcd);
            $den = gmp_div($den, $gcd);
        }
    }

    /**
     * @throws DivisionByZeroError
     */
    private static function normalizeSigns(\GMP& $whole, \GMP& $num, \GMP& $den): void
    {
        //The denominator can only be positive. Obviously it cannot be zero. If it is negative, then change sign to
        //both the numerator and denominator
        if (gmp_cmp($den, 0) === 0) {
            throw new DivisionByZeroError();
        } elseif (gmp_cmp($den, 0) < 0) {
            $num = gmp_neg($num);
            $den = gmp_neg($den);
        }

        //Make sure that the signs of $whole and $num agree.
        if (gmp_cmp($whole, 0) > 0 && gmp_cmp($num, 0) < 0) {
            $whole = gmp_sub($whole, 1);
            $num = gmp_add($num, $den);
        } elseif (gmp_cmp($whole, 0) < 0 && gmp_cmp($num, 0) > 0) {
            $whole = gmp_add($whole, 1);
            $num = gmp_sub($num, $den);
        }
    }

    /**
     * @throws OverflowException
     */
    private static function toInt(\GMP $gmpNumber): int
    {
        if (gmp_cmp(PHP_INT_MIN, $gmpNumber) <= 0 && gmp_cmp($gmpNumber, PHP_INT_MAX) <= 0) {
            return gmp_intval($gmpNumber);
        }

        throw new OverflowException($gmpNumber);
    }
}
