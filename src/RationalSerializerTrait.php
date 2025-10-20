<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

trait RationalSerializerTrait
{
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

        [$whole, $num, $den] = explode($this->getSeparator(), $value);

        return Rational::fromWholeAndFraction((int) $whole, (int) $num, (int) $den);
    }

    protected function getMaxStringLength(): int
    {
        //The PHP_INT_MIN and PHP_INT_MAX values are both 19 digits long. Adding the possibility of a - sign in front of
        //each value (even though the denominator should never be negative) brings the figure to 20 chars per number.
        //Adding the separators that is 20 + 1 + 20 + 1 + 20 = 62 characters, so 64 chars should be more than enough to
        //store the concatenation of the three values

        //@TODO: make this a constant (and maybe deprecate this method) after we drop support for PHP versions that do
        //      not support constants in traits
        return 64;
    }

    /**
     * @internal
     */
    private function getSeparator(): string
    {
        //@TODO: make this a constant and remove this method after we drop support for PHP versions that do not support
        //      constants in traits
        return ':';
    }
}
