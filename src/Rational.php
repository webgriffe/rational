<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

use DivisionByZeroError;
use Webgriffe\Rational\Exception\OverflowException;
use NumberFormatter;

class Rational
{
    private function __construct(
        private readonly int $whole,
        private readonly int $num = 0,
        private readonly int $den = 1,
    ) {
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

    /**
     * @return int[]
     */
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

    public function isZeroOrPositive(): bool
    {
        return !$this->isNegative();
    }

    public function isNegative(): bool
    {
        return $this->whole < 0 || $this->num < 0;
    }

    public function isZeroOrNegative(): bool
    {
        return !$this->isPositive();
    }

    public function isWhole(): bool
    {
        return $this->num === 0;
    }

    public function equals(self $other): bool
    {
        //Values are always stored as fully simplified, so there should be no issue like comparing 1/2 to 2/4, as the
        //2/4 would have been simplified to 1/2. So just compare the single values.
        return $this->whole === $other->whole && $this->num === $other->num && $this->den === $other->den;
    }

    /**
     * @throws OverflowException
     */
    public function add(self $other): static
    {
        //Given two rationals a + b/c and d + e/f (where a, b, c, d, e and f are all integers, c > 0, f > 0, a * b >= 0
        //and d * e >= 0), their sum is given by:
        //a + b/c + d + e/f
        //= a + d + b/c + e/f
        //= a + d + (b*f + e*c)/(c*e)
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
    public function sub(self $other): static
    {
        return $this->add($other->neg());
    }

    /**
     * @throws OverflowException
     */
    public function mul(self $other): static
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
    public function div(self $other): static
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

    public function formatByNumberFormatter(NumberFormatter $formatter, int $type = NumberFormatter::TYPE_DEFAULT): string
    {
        return $formatter->format($this->getApproximateFloat(), $type);
    }

    public function formatCurrencyByNumberFormatter(NumberFormatter $formatter, string $ISO4217CurrencyCode): string
    {
        return $formatter->formatCurrency($this->getApproximateFloat(), $ISO4217CurrencyCode);
    }

    public function format(
        int $maxDecimals,
        int $minDecimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ','
    ): string {
        if ($minDecimals < 0) {
            throw new \InvalidArgumentException('The number of decimals cannot be negative');
        }

        if ($maxDecimals < $minDecimals) {
            throw new \InvalidArgumentException('The minimum number of decimals cannot be larger than the maximum number of decimals');
        }

        $whole = $this->whole;

        $rounded = round((float)$this->num / (float)$this->den, $maxDecimals);
        //If the fractional part is very close to +1 or -1, then the rounding operation could force it into positive or
        //negative unity. In that case add that to the whole part
        if ($rounded === 1.0 || $rounded === -1.0) {
            //Casting to int is safe since we know that this float represents an integer.
            $whole += (int) $rounded;
            $rounded = 0.0;
        }

        $sign = '';
        $decimalPart = '';
        if ($rounded !== 0.0) {
            //Remove the initial part, leaving only the row of decimal digits
            //Starting from PHP 8.0, float-to-string casts are locale-independent. So we can be sure that the resulting
            //string uses the period as the decimal separator and no thousands separator
            $matches = [];
            preg_match("/^(-?)0\.(.*)$/", (string) $rounded, $matches);
            $sign = $matches[1];
            $decimalPart = $matches[2];
        }

        $decimalPart = str_pad($decimalPart, $minDecimals, '0', STR_PAD_RIGHT);

        //This is used only to format the integer part with the thousand separator, if any
        $result = number_format($whole, 0, $decimalSeparator, $thousandsSeparator);
        if ($decimalPart) {
            $result .= $decimalSeparator . $decimalPart;
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

        //It would be nice to suggest the rational number that can still be represented with integers that is closest
        //to the value that caused the error . Perhaps this can be calculated by computing the continued fraction of
        //the value that generated the overflow and stopping at the last fraction that can still be represented with
        //integers.
        //@see https://en.wikipedia.org/wiki/Continued_fraction
        throw new OverflowException($gmpNumber);
    }

    /**
     * This is only intended to be used internally. It must not be made public.
     * If you need to convert a rational to a float, you're probably doing something wrong
     */
    private function getApproximateFloat(): float
    {
        return $this->whole + (((float)$this->num) / $this->den);
    }
}
