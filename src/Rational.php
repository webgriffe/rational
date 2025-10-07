<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

use DivisionByZeroError;
use Webgriffe\Rational\Exception\OverflowException;
use NumberFormatter;
use Webgriffe\Rational\Exception\UnderflowException;
use Webmozart\Assert\Assert;

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
        //Since b and d are non-zero, we can simplify:
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
        //= a + d + (b*f + e*c)/(c*f)
        $whole = gmp_add($this->whole, $other->whole);  //a+d
        $num = gmp_add(
            gmp_mul($this->num, $other->den),           //b*f
            gmp_mul($other->num, $this->den),           //e*c
        );
        $den = gmp_mul($this->den, $other->den);        //c*f

        //The fractional part may be an improper fraction. If so, extract the whole part from it
        self::extractWholePartFromFraction($whole, $num, $den);

        //Simplify the fraction
        self::simplify($num, $den);

        //Make sure that the sign of the whole part and that of the numerator do not disagree
        //Since the initial denominators cannot be zero, the new value cannot be zero either. So this call cannot
        //generate a DivisionByZeroError
        self::normalizeSigns($whole, $num, $den);

        return self::createNew($whole, $num, $den);
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
        $whole = gmp_mul($this->whole, $other->whole);  //a*d
        $num = gmp_add(
            gmp_add(
                gmp_mul(gmp_mul($this->whole, $other->num), $this->den),    //a*e*c
                gmp_mul(gmp_mul($other->whole, $this->num), $other->den),   //d*b*f
            ),
            gmp_mul($this->num, $other->num),           //b*e
        );
        $den = gmp_mul($this->den, $other->den);        //c*f

        //If the starting data had its signs normalized, then here we can be sure that the signs are correct too.

        //The fractional part may be an improper fraction. If so, extract the whole part from it. Notice that this may
        //cause the whole part and/or the numerator to change from nonzero to zero or vice versa, but it cannot cause
        //either of these to invert its sign. So the signs remain consistent after this operation.
        self::extractWholePartFromFraction($whole, $num, $den);

        //Simplify the fraction
        self::simplify($num, $den);

        return self::createNew($whole, $num, $den);
    }

    /**
     * @throws OverflowException
     */
    public function div(self $other): static
    {
        //Given two rationals a + b/c and d + e/f (where a, b, c, d, e and f are all integers, c > 0, f > 0, a * b >= 0
        //and d * e >= 0)
        //(a + b/c) / (d + e/f)
        //= (a + b/c) / ((f*d + e)/f)
        //= (a + b/c) * (f/(f*d + e))
        //= (a*c + b)/c * f/(f*d + e)
        //= (a*c + b)*f / (f*d + e)*c
        $newNum = gmp_mul(
            gmp_add(
                gmp_mul($this->whole, $this->den),  //a*c
                $this->num,                         //b
            ),
            $other->den,                            //f
        );
        $newDen = gmp_mul(
            gmp_add(
                gmp_mul($other->whole, $other->den),    //f*d
                $other->num,                            //e
            ),
            $this->den,                                 //c
        );

        return self::normalizeAllAndCreate(0, $newNum, $newDen);
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

        return self::normalizeAllAndCreate(0, $this->den, $newDen);
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
    private static function normalizeAllAndCreate(int|\GMP $whole, int|\GMP $num = 0, int|\GMP $den = 1): self
    {
        //The denominator can only be positive. Obviously it cannot be zero. If it is negative, then change sign to
        //both the numerator and denominator
        if ($den === 0) {
            throw new DivisionByZeroError();
        } elseif ($den < 0) {
            $num = -$num;
            $den = -$den;
        }

        self::extractWholePartFromFraction($whole, $num, $den);

        //Simplify the fraction, if possible
        self::simplify($num, $den);

        //Make sure that the sign of the whole part and that of the numerator do not disagree
        self::normalizeSigns($whole, $num, $den);

        return self::createNew($whole, $num, $den);
    }

    private static function extractWholePartFromFraction(int|\GMP& $whole, int|\GMP& $num, int|\GMP& $den): void
    {
        //If the fraction is an improper fraction (|num| > den), then extract the whole part of that and add it to the
        //actual whole part
        $additionalWholePart = (int) ($num / $den);
        if ($additionalWholePart != 0) {
            $whole += $additionalWholePart;
            $num -= ($additionalWholePart * $den);
        }
    }

    private static function simplify(int|\GMP& $num, int|\GMP& $den): void
    {
        //Simplify the fraction, if possible
        if (is_int($num) && is_int($den)) {
            $gcd = self::gcdInt($num, $den);
        } else {
            $gcd = gmp_gcd($num, $den);
        }

        if ($gcd > 1) {
            $num /= $gcd;
            $den /= $gcd;
        }
    }

    /**
     * @throws DivisionByZeroError
     */
    private static function normalizeSigns(int|\GMP& $whole, int|\GMP& $num, int|\GMP& $den): void
    {
        //The denominator can only be positive. Obviously it cannot be zero.
        //If it is negative, then change sign to both the numerator and denominator so that the overall value does not
        //change.
        if ($den == 0) {
            throw new DivisionByZeroError();
        } elseif ($den < 0) {
            $num = -$num;
            $den = -$den;
        }

        //Make sure that the signs of $whole and $num agree.
        if ($whole > 0 && $num < 0) {
            $whole -= 1;
            $num += $den;
        } elseif ($whole < 0 && $num > 0) {
            $whole += 1;
            $num -= $den;
        }
    }

    private static function createNew(int|\GMP $whole, int|\GMP $num, int|\GMP $den): static
    {
        $intWhole = self::toInt($whole);

        try {
            $intNum = self::toInt($num);
            $intDen = self::toInt($den);
        } catch (OverflowException) {
            //If the numerator or denominator get too large, it is not an overflow, it is technically an underflow,
            //meaning a value that is more precise than what can be represented with a fraction of two integers.
            //We want to find another fraction that can be represented by two integers and whose value is as close as
            //possible to the exact value.
            $approximate = self::getClosestFractionRepresentableByIntegers($num, $den);
            throw new UnderflowException($den, new static($intWhole, $approximate['num'], $approximate['den']));
        }

        return new static($intWhole, $intNum, $intDen);
    }

    /**
     * @throws OverflowException
     */
    private static function toInt(int|\GMP $number): int
    {
        if (is_int($number)) {
            return $number;
        }

        if (gmp_cmp(PHP_INT_MIN, $number) <= 0 && gmp_cmp($number, PHP_INT_MAX) <= 0) {
            return gmp_intval($number);
        }

        //It would be nice to suggest the rational number that can still be represented with integers that is closest
        //to the value that caused the error. Perhaps this can be calculated by computing the continued fraction of
        //the value that generated the overflow and stopping at the last fraction that can still be represented with
        //integers.
        //@see https://en.wikipedia.org/wiki/Continued_fraction
        throw new OverflowException($number);
    }

    private static function gcdInt(int $a, int $b): int
    {
        while ($b != 0) {
            $m = $a % $b;
            $a = $b;
            $b = $m;
        }

        return abs($a);
    }

    /**
     * This is only intended to be used internally. It must NOT be made public.
     * If you need to convert a rational to a float, you're probably doing something wrong.
     * Consider using one of the formatting functions instead
     */
    private function getApproximateFloat(): float
    {
        return $this->whole + (((float) $this->num) / $this->den);
    }

    /**
     * Given a proper fraction n/d (i.e. one where |n| < |d|), compute the proper fraction n'/d' where both n' and d'
     * are representable by PHP integers and whose value is closest to that of the original fraction.
     *
     * @return array{num: int, den: int}
     */
    private static function getClosestFractionRepresentableByIntegers(\GMP $num, \GMP $den): array
    {
        Assert::true(gmp_cmp($den, 0) > 0);
        Assert::true(gmp_cmp(gmp_abs($num), $den) < 0);

        //We want to compute the continued fraction of the number that caused the underflow and use it to compute the
        //closest approximation that can be represented with the size of integers that we have available.
        //In the general case the continued fraction of a real number may be infinite. But here we're dealing with
        //rational numbers, so the continued fractions are finite. Which in turn means that this algorithm always
        //terminates.
        //See https://en.wikipedia.org/wiki/Simple_continued_fraction#Calculating_continued_fraction_representations
        $n = gmp_abs($num);
        $d = $den;
        $continuedFraction = [];
        $bestConvergent = null;
        while (true) {
            //Extend the continued fraction representation with the whole part of the current value
            $wholePart = gmp_div_q($n, $d);
            $continuedFraction[] = $wholePart;

            //Try to improve the best convergent given the new continued fraction
            $newBestConvergent = self::selectBestConvergent(
                $continuedFraction,
                gmp_abs($num),
                $den,
                $bestConvergent,
                gmp_cmp($num, 0) >= 0 ? gmp_init(PHP_INT_MAX) : gmp_abs(gmp_init(PHP_INT_MIN)),
            );
            if (null !== $newBestConvergent) {
                $bestConvergent = $newBestConvergent;
            }

            //Subtract the whole part from the fraction
            $n = gmp_sub($n, gmp_mul($wholePart, $d));

            //If the value becomes zero, we are done
            if (gmp_cmp($n, 0) === 0) {
                break;
            }

            //Since we subtracted the whole part from the fraction, the remaining fraction must be less than 1
            Assert::true(gmp_cmp($n, $d) < 0);

            //Simplify and take the reciprocal fraction (i.e. swap the numerator and denominator)
            //We just tested for the case of n === 0, so the new denominator will certainly not be zero
            $gcd = gmp_gcd($n, $d);
            $t = gmp_div($n, $gcd);
            $n = gmp_div($d, $gcd);
            $d = $t;

            Assert::true(gmp_cmp($d, 0) > 0);
        }

        //If all fails, just return zero ( 0/1 )
        //Not sure if this can happen in practice, but handling it is just a couple extra lines of code...
        $bestNum = 0;
        $bestDen = 1;

        if (null !== $bestConvergent) {
            //Don't forget about the sign: if the original value was negative, flip thw numerator sign
            if (gmp_cmp($num, 0) < 0) {
                $bestConvergent['num'] = gmp_neg($bestConvergent['num']);
            }

            $bestNum = gmp_intval($bestConvergent['num']);
            $bestDen = gmp_intval($bestConvergent['den']);
        }

        return ['num' => $bestNum, 'den' => $bestDen];
    }

    /**
     * This method returns the best convergent for the specified continued fraction that is acceptable.
     * A fraction is deemed acceptable if the denominator is not greater than $largestAllowedDenominator
     * @see https://en.wikipedia.org/wiki/Simple_continued_fraction#Best_rational_approximations
     *
     * @param \GMP[] $continuedFraction
     * @param null|array{num: \GMP, den: \GMP} $previousBest
     *
     * @return null|array{num: \GMP, den: \GMP}
     */
    private static function selectBestConvergent(
        array $continuedFraction,
        \GMP $originalN,
        \GMP $originalD,
        ?array $previousBest,
        \GMP $largestAllowedDenominator,
    ): ?array {
        Assert::notEmpty($continuedFraction);

        //Is the full fraction acceptable? If so, that is guaranteed to be the one closest to the original value, at
        //least for this continued fraction. So we can just return it
        $fraction = self::getFraction($continuedFraction);
        if (self::fractionIsAcceptable($fraction, $largestAllowedDenominator)) {
            return $fraction;
        }

        $lastIndex = count($continuedFraction) - 1;
        Assert::greaterThanEq($lastIndex, 0);
        $last = $continuedFraction[$lastIndex];

        //If the last digit is even, the fraction generated by half its value is only admissible if it is strictly
        //better (i.e. closer to the target value) than the previous one
        $first = gmp_div_q($last, 2, GMP_ROUND_PLUSINF);
        if (null !== $previousBest && gmp_div_r($last, 2) == 0 && gmp_cmp($last, 2) >= 0) {
            $continuedFraction[$lastIndex] = $first;
            $fraction = self::getFraction($continuedFraction);

            //In order to compare $fraction and $previousBest to the target value, convert them all to a common
            //denominator
            $lcmD = gmp_lcm($originalD, gmp_lcm($previousBest['den'], $fraction['den']));

            $lcmOriginalN = gmp_mul(gmp_div($lcmD, $originalD), $originalN);
            $lcmFractionN = gmp_mul(gmp_div($lcmD, $fraction['den']), $fraction['num']);
            $lcmPreviousValueN = gmp_mul(gmp_div($lcmD, $previousBest['den']), $previousBest['num']);

            //Which value has a numerator that is closest to the numerator of the original value?
            $comparison = gmp_cmp(
                gmp_abs(gmp_sub($lcmFractionN, $lcmOriginalN)),
                gmp_abs(gmp_sub($lcmPreviousValueN, $lcmOriginalN)),
            );

            if ($comparison >= 0) {
                //The fraction generated by half the last digit is not better than the previous best.
                //So adjust the first element for the binary search algorithm
                $first = gmp_add($first, 1);
            }
        }

        //Test whether the first fraction is acceptable.
        //If it is not, then it means that no value is acceptable and we are done
        $continuedFraction[$lastIndex] = $first;
        $fraction = self::getFraction($continuedFraction);
        if (!self::fractionIsAcceptable($fraction, $largestAllowedDenominator)) {
            return null;
        }

        //Now we know that the first fraction is acceptable and the last one is not.

        //The naïve algorithm iterates on all values between $first and $last (included). But $last may be a LARGE
        //value, and we are only interested in the largest fraction that we can represent with PHP integers.
        //Since we already know that the last value is too large (we tested it with the previous if() statement), we can
        //use a binary search to efficiently reduce the range and then do a linear search for the last few steps.

        //Binary search
        //The loop invariant is that the $first value always produces a fraction that is acceptable, while the $last
        //value always produces a fraction that is not acceptable
        while (gmp_cmp(gmp_sub($last, $first), 16) > 0) {
            //We are using arbitrary precision numbers, so we can safely calculate the midpoint by summing the two
            //endpoints and halving, without fearing an overflow. And since we stop before the range gets too small,
            //we do not have to concern with edge cases that arise when the interval gets very small
            $mid = gmp_div_q(gmp_add($first, $last), 2);
            $continuedFraction[$lastIndex] = $mid;
            $fraction = self::getFraction($continuedFraction);
            if (self::fractionIsAcceptable($fraction, $largestAllowedDenominator)) {
                //The fraction is acceptable, so the upper half of the range is where the best value must be
                $first = $mid;
            } else {
                //The fraction is not acceptable, so search in the lower half
                $last = $mid;
            }
        }

        //Linear search in the last remaining values.
        //Search in reverse order, as we are interested in the largest value that produces an acceptable fraction
        for ($i = $last; gmp_cmp($i, $first) >= 0; $i = gmp_sub($i, 1)) {
            $continuedFraction[$lastIndex] = $i;
            $fraction = self::getFraction($continuedFraction);
            if (self::fractionIsAcceptable($fraction, $largestAllowedDenominator)) {
                return $fraction;
            }
        }

        //We know that at least the fraction generated by $first is acceptable, so at the very least the last iteration
        //of the above loop should enter the if() statement and return a value. As such we should never get here.
        throw new \RuntimeException('This should be unreachable!');
    }

    /**
     * This computes the value of a simple continued fraction a0 + 1/(a1 + 1/(a2 + 1/(a3 + 1/(...)))) where the coefficients
     * a0, a1, a2, a3, ... are contained in the provided array
     *
     * @see https://en.wikipedia.org/wiki/Simple_continued_fraction
     *
     * @param \GMP[] $continuedFraction
     *
     * @return array{num: \GMP, den: \GMP}
     */
    private static function getFraction(array $continuedFraction): array
    {
        Assert::notEmpty($continuedFraction);

        $n = $continuedFraction[count($continuedFraction) - 1];
        $d = gmp_init(1);

        for ($i = count($continuedFraction) - 2; $i >= 0; --$i) {
            Assert::true(gmp_cmp($n, 0) !== 0);

            //Reciprocate the fraction
            $t = $n;
            $n = $d;
            $d = $t;

            //Add the next value
            $n = gmp_add($n, gmp_mul($continuedFraction[$i], $d));
        }

        //I'm not sure whether the above code is guaranteed to generate a fraction that is already simplified.
        //So, just to be sure, try to simplify
        $gcd = gmp_gcd($n, $d);
        if (gmp_cmp($gcd, 1) > 0) {
            $n = gmp_div($n, $gcd);
            $d = gmp_div($d, $gcd);
        }

        return ['num' => $n, 'den' => $d];
    }

    /**
     * A fraction is deemed acceptable if the denominator is not greater than $largestAllowedDenominator
     *
     * @param array{num: \GMP, den: \GMP} $fraction
     *
     * @return bool
     */
    private static function fractionIsAcceptable(array $fraction, \GMP $largestAllowedDenominator): bool
    {
        return gmp_cmp($fraction['den'], $largestAllowedDenominator) <= 0;
    }
}
