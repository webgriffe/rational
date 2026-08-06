<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

trait RationalSerializerTrait
{
    //The PHP_INT_MIN and PHP_INT_MAX values are both 19 digits long. Adding the possibility of a - sign in front of
    //each value (even though the denominator should never be negative) brings the figure to 20 chars per number.
    //Adding the separators that is 20 + 1 + 20 + 1 + 20 = 62 characters, so 64 chars should be more than enough to
    //store the concatenation of the three values
    private const int MAX_STRING_LENGTH = 64;

    private const string SEPARATOR = ':';

    protected function serialize(?Rational $rational): ?string
    {
        if (null === $rational) {
            return null;
        }

        $whole = $rational->getWholePart();
        [$num, $den] = $rational->getFractionPart();

        return (string) $whole.$this->getSeparator().(string) $num.$this->getSeparator().(string) $den;
    }

    protected function unserialize(?string $value): ?Rational
    {
        if (null === $value) {
            return null;
        }

        $parts = explode($this->getSeparator(), $value);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException(
                sprintf('Invalid serialized Rational value: expected format "whole%1$snum%1$sden", got "%2$s"', $this->getSeparator(), $value)
            );
        }
        [$whole, $num, $den] = $parts;

        //The serialize() method persists the values of a Rational object, that should be normalized. However we don't
        //know if anyone overrode that method and changed the behavior, or whether anyone did some DB operations
        //directly.
        //As such we cannot assume that the values that we read here are still normalized, and so we have to check them.
        return Rational::fromWholeAndFraction((int) $whole, (int) $num, (int) $den);
    }

    protected function getMaxStringLength(): int
    {
        return self::MAX_STRING_LENGTH;
    }

    protected function getSeparator(): string
    {
        return self::SEPARATOR;
    }
}
