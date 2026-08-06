<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class InitializationTest extends TestCase
{
    public function testInitializeZeroInteger(): void
    {
        $q = Rational::fromWhole(0);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializePositiveInteger(): void
    {
        $q = Rational::fromWhole(5);
        $this->assertEquals(5, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializeNegativeInteger(): void
    {
        $q = Rational::fromWhole(-5);
        $this->assertEquals(-5, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testZeroDenominatorThrowsError(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        Rational::fromFraction(3, 0);
    }

    public function testInitializeZeroFraction(): void
    {
        $q = Rational::fromFraction(0, 1);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializePositiveFraction(): void
    {
        $q = Rational::fromFraction(2, 3);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([2, 3], $q->getFractionPart());
    }

    public function testInitializePositiveApparentFraction(): void
    {
        $q = Rational::fromFraction(9, 3);
        $this->assertEquals(3, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializePositiveImproperFraction(): void
    {
        $q = Rational::fromFraction(9, 4);
        $this->assertEquals(2, $q->getWholePart());
        $this->assertEquals([1, 4], $q->getFractionPart());
    }

    public function testInitializePositiveImproperSimplifiableFraction(): void
    {
        $q = Rational::fromFraction(18, 8);
        $this->assertEquals(2, $q->getWholePart());
        $this->assertEquals([1, 4], $q->getFractionPart());
    }

    public function testInitializeNegativeFraction(): void
    {
        $q = Rational::fromFraction(-2, 3);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([-2, 3], $q->getFractionPart());
    }

    public function testInitializeNegativeApparentFraction(): void
    {
        $q = Rational::fromFraction(-9, 3);
        $this->assertEquals(-3, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializeNegativeImproperFraction(): void
    {
        $q = Rational::fromFraction(-9, 4);
        $this->assertEquals(-2, $q->getWholePart());
        $this->assertEquals([-1, 4], $q->getFractionPart());
    }

    public function testInitializeNegativeImproperSimplifiableFraction(): void
    {
        $q = Rational::fromFraction(-18, 8);
        $this->assertEquals(-2, $q->getWholePart());
        $this->assertEquals([-1, 4], $q->getFractionPart());
    }

    public function testNegativeDenominatorIsNotAllowed(): void
    {
        $q = Rational::fromFraction(8, -3);
        $this->assertEquals(-2, $q->getWholePart());
        $this->assertEquals([-2, 3], $q->getFractionPart());
    }

    public function testCreateFromWholeAndFractionWithDisagreeingSigns1(): void
    {
        $q = Rational::fromWholeAndFraction(-2, 2, 3);
        $this->assertEquals(-1, $q->getWholePart());
        $this->assertEquals([-1, 3], $q->getFractionPart());
    }

    public function testCreateFromWholeAndFractionWithDisagreeingSigns2(): void
    {
        $q = Rational::fromWholeAndFraction(3, -5, 8);
        $this->assertEquals(2, $q->getWholePart());
        $this->assertEquals([3, 8], $q->getFractionPart());
    }
}
