<?php

declare(strict_types=1);

namespace Webgriffe\Rational;

trait RationalSerializerTrait
{
    private function serialize(?Rational $rational): ?string
    {
        if (null === $rational) {
            return null;
        }

        [$num, $den] = $rational->getFractionPart();
        $whole = $rational->getWholePart();

        return (string) $whole.':'.(string) $num.':'.(string) $den;
    }

    private function unserialize(?string $value): ?Rational
    {
        if (null === $value) {
            return null;
        }

        [$whole, $num, $den] = explode(':', $value);

        return Rational::fromWholeAndFraction((int) $whole, (int) $num, (int) $den);
    }

    private function getMaxStringLength(): int
    {
        return 64;
    }
}
