<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

use DivisionByZeroError;
use Webgriffe\Rational\Exception\OverflowException;
use NumberFormatter;

class Rational
{
    public const TO_DECIMAL_CEIL = 10;
    public const TO_DECIMAL_ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    public const TO_DECIMAL_ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    public const TO_DECIMAL_FLOOR = -10;

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

    public function isInteger(): bool
    {
        return $this->isWhole();
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

    public function compare(self $other): int
    {
        //Compare the whole parts. If they differ, then we already have the result of the comparison.
        //If they are equal, then we have to compare the fractions:
        //  a/b <? c/d
        //We know that b and d are both positive, so b*d is also positive. Multiply both sides by "b*d":
        //  a*b*d/b <? c*b*d/d
        //Since b and d non-zero, we can simplify:
        //  a*d <? c*b
        //This allows us to compare the fractions without divisions, thus avoiding possible rounding errors.
        return ($this->whole <=> $other->whole) ?:
            gmp_cmp(
                gmp_mul($this->num, $other->den),
                gmp_mul($other->num, $this->den),
            );
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

    /**
     * If you are SURE that the value is an integer, you can use this method to convert the rational to a native PHP
     * integer.
     */
    public function toIntExact(): int
    {
        if (!$this->isInteger()) {
            throw new \RuntimeException('Cannot convert a non-integer rational to an integer');
        }

        return $this->whole;
    }

    /**
     * Generates a string representing this rational number. The decimal separator is always "." and there is no
     * thousand separator.
     *
     * @param int $maxDecimals The maximum number of decimal digits that the result can have
     * @param int $minDecimals The minimum number of decimal digits that the result can have
     * @param int $algorithm The algorithm to use for rounding if the value cannot be exactly represented with the
     *                       specified constraints on the number of decimals. One of the TO_DECIMAL_* constants:
     *                       TO_DECIMAL_CEIL rounds toward positive infinity
     *                       TO_DECIMAL_FLOOR rounds toward negative infinity
     *                       TO_DECIMAL_ROUND_HALF_UP Performs standard rounding. If the value is exactly halfway
     *                                                between two admissible values in the result (such as 2.75 when
     *                                                only one decimal place is requested), then it is rounded away
     *                                                from zero. @see round() with PHP_ROUND_HALF_UP
     *                       TO_DECIMAL_ROUND_HALF_DOWN Performs standard rounding. If the value is exactly halfway
     *                                                  between two admissible values in the result (such as 2.75 when
     *                                                  only one decimal place is requested), then it is rounded toward
     *                                                  zero. @see round() with PHP_ROUND_HALF_DOWN
     */
    public function toDecimalString(
        int $maxDecimals = 0,
        int $minDecimals = 0,
        int $algorithm = self::TO_DECIMAL_ROUND_HALF_UP
    ): string {
        if ($minDecimals < 0) {
            throw new \InvalidArgumentException('The number of decimals cannot be negative');
        }

        if ($maxDecimals < $minDecimals) {
            throw new \InvalidArgumentException('The minimum number of decimals cannot be larger than the maximum number of decimals');
        }

        //Calculate the value of the rational after multiplying both the whole part and the numerator by 10^$maxDecimals
        //This has the effect of shifting the decimal separator right by $maxDecimals digits
        $scaledNumerator = gmp_mul(
            gmp_add(
                gmp_mul($this->whole, $this->den),
                $this->num
            ),
            gmp_pow(10, $maxDecimals)
        );

        //Round the result as requested
        switch ($algorithm) {
            case self::TO_DECIMAL_CEIL:
                $rounded = gmp_div_q($scaledNumerator, $this->den, GMP_ROUND_PLUSINF);

                break;
            case self::TO_DECIMAL_FLOOR:
                $rounded = gmp_div_q($scaledNumerator, $this->den, GMP_ROUND_MINUSINF);

                break;
            case self::TO_DECIMAL_ROUND_HALF_UP:
            case self::TO_DECIMAL_ROUND_HALF_DOWN:
                [$rounded, $remainder] = gmp_div_qr($scaledNumerator, $this->den);

                //Take the remainder, make it positive, double it and compare it to the denominator.
                //It's equivalent to checking if it is less than or more than halfway to the next number.
                //The final <=> operator is used because the result of gmp_cmp() is not guaranteed to be exactly -1 or
                //1, but may be any positive or negative number
                switch (gmp_cmp(gmp_mul(gmp_abs($remainder), 2), $this->den) <=> 0) {
                    case -1:
                        //We're less than halfway to the next number. Round toward zero.
                        break;
                    case 0:
                        //We're exactly halfway to the next number. Round according to the algorithm
                        if ($algorithm === self::TO_DECIMAL_ROUND_HALF_DOWN) {
                            break;
                        }

                        //Intentional fallthrough
                    case 1:
                        //We're more than halfway to the next number. Round away from zero.
                        $rounded = gmp_add($rounded, gmp_sign($scaledNumerator));

                        break;
                }

                break;
            default:
                throw new \InvalidArgumentException('Invalid rounding algorithm');
        }

        //Convert the result to a string, ignoring the sign. That will be added back later
        $result = gmp_strval(gmp_abs($rounded));

        if ($maxDecimals > 0) {
            //Extract up to the maximum number of decimals from the end of the result string.
            //Notice that the result may be shorter than $maxDecimals if the value is something like 0.0001234. If that
            //happens, pad the string with zeros to make sure that we have all the zeros to the left to get all the way
            //to the decimal separator.
            $decimalPart = str_pad(substr($result, -$maxDecimals), $maxDecimals, '0', STR_PAD_LEFT);

            //Leave the minimum number of trailing zeros necessary to meet the minimum number of decimals requested.
            $decimalPart = str_pad(rtrim($decimalPart, '0'), $minDecimals, '0');

            //Extract the whole part, if any. If there is no whole part, it means that the value is something like
            //0.0001234, so put a single zero in the integer part
            $result = str_pad(substr($result, 0, -$maxDecimals), 1, '0');

            //Combine the integer and decimal parts using . as a separator
            if ($decimalPart !== '') {
                $result .= '.' . $decimalPart;
            }
        }

        //Add the sign, if necessary. Notice that the rounding may make the sign disappear, so we cannot rely on the
        //sign of the initial value
        if (gmp_cmp($rounded, 0) < 0) {
            $result = '-' . $result;
        }

        return $result;
    }

    public function formatByNumberFormatter(NumberFormatter $formatter, int $type = NumberFormatter::TYPE_DEFAULT): string
    {
        return $formatter->format($this->getApproximateFloat(), $type);
    }

    public function formatCurrencyByNumberFormatter(NumberFormatter $formatter, string $ISO4217CurrencyCode): string
    {
        return $formatter->formatCurrency($this->getApproximateFloat(), $ISO4217CurrencyCode);
    }

    /**
     * @deprecated Use toDecimalString instead
     */
    public function format(
        int $maxDecimals,
        int $minDecimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ','
    ): string {
        $string = $this->toDecimalString($maxDecimals, $minDecimals);

        if ($decimalSeparator !== '.') {
            $string = str_replace('.', $decimalSeparator, $string);
        }

        if ($thousandsSeparator !== '') {
            $quotedDecimalSeparator = preg_quote($decimalSeparator, '/');
            preg_match("/^(-?)(\d*)({$quotedDecimalSeparator}\d*)?$/", $string, $matches);

            [, $sign, $whole, $decimalWithSeparator] = array_pad($matches, 4, '');

            //We must split this string in groups of 3 characters. However, this split must be done from the right to
            //the left. To do so, we reverse the string, split it into 3-char-long chunks, insert the (reversed)
            //separator between the chunks, combine everything back into one string and reverse the result to get the
            //correct string
            $whole = strrev(implode(strrev($thousandsSeparator), str_split(strrev($whole), 3)));

            //Finally build the number back by combining all the parts
            $string = $sign . $whole . $decimalWithSeparator;
        }

        return $string;
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
        //If the fraction is an improper fraction (|num| > den), then extract the whole part of that and add it to the
        //actual whole part
        $additionalWholePart = gmp_div($num, $den);
        if (gmp_cmp($additionalWholePart, 0) !== 0) {
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
        //The denominator can only be positive. Obviously it cannot be zero.
        //If it is negative, then change sign to both the numerator and denominator so that the overall value does not
        //change.
        $denSign = gmp_cmp($den, 0);
        if ($denSign === 0) {
            throw new DivisionByZeroError();
        } elseif ($denSign < 0) {
            $num = gmp_neg($num);
            $den = gmp_neg($den);
        }

        //Make sure that the signs of $whole and $num agree.
        $wholeSign = gmp_cmp($whole, 0);
        $numSign = gmp_cmp($num, 0);
        if ($wholeSign > 0 && $numSign < 0) {
            $whole = gmp_sub($whole, 1);
            $num = gmp_add($num, $den);
        } elseif ($wholeSign < 0 && $numSign > 0) {
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
        //to the value that caused the error. Perhaps this can be calculated by computing the continued fraction of
        //the value that generated the overflow and stopping at the last fraction that can still be represented with
        //integers.
        //@see https://en.wikipedia.org/wiki/Continued_fraction
        throw new OverflowException($gmpNumber);
    }

    /**
     * This is only intended to be used internally. It must NOT be made public.
     * If you need to convert a rational to a float, you're probably doing something wrong
     */
    private function getApproximateFloat(): float
    {
        return $this->whole + (((float)$this->num) / $this->den);
    }
}
