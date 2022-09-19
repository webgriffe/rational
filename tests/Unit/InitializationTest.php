<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class InitializationTest extends TestCase
{
    public function testInitializeZeroInteger()
    {
        $q = Rational::fromWhole(0);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializePositiveInteger()
    {
        $q = Rational::fromWhole(5);
        $this->assertEquals(5, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializeNegativaInteger()
    {
        $q = Rational::fromWhole(-5);
        $this->assertEquals(-5, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testZeroDenominatorThrowsError()
    {
        $this->expectException(\DivisionByZeroError::class);
        Rational::fromFraction(3, 0);
    }

    public function testInitializeZeroFraction()
    {
        $q = Rational::fromFraction(0, 1);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializePositiveFraction()
    {
        $q = Rational::fromFraction(2, 3);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([2, 3], $q->getFractionPart());
    }

    public function testInitializePositiveApparentFraction()
    {
        $q = Rational::fromFraction(9, 3);
        $this->assertEquals(3, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializePositiveImproperFraction()
    {
        $q = Rational::fromFraction(9, 4);
        $this->assertEquals(2, $q->getWholePart());
        $this->assertEquals([1, 4], $q->getFractionPart());
    }

    public function testInitializePositiveImproperSimplifiableFraction()
    {
        $q = Rational::fromFraction(18, 8);
        $this->assertEquals(2, $q->getWholePart());
        $this->assertEquals([1, 4], $q->getFractionPart());
    }

    public function testInitializeNegativaFraction()
    {
        $q = Rational::fromFraction(-2, 3);
        $this->assertEquals(0, $q->getWholePart());
        $this->assertEquals([-2, 3], $q->getFractionPart());
    }

    public function testInitializeNegativaApparentFraction()
    {
        $q = Rational::fromFraction(-9, 3);
        $this->assertEquals(-3, $q->getWholePart());
        $this->assertEquals([0, 1], $q->getFractionPart());
    }

    public function testInitializeNegativaImproperFraction()
    {
        $q = Rational::fromFraction(-9, 4);
        $this->assertEquals(-2, $q->getWholePart());
        $this->assertEquals([-1, 4], $q->getFractionPart());
    }

    public function testInitializeNegativaImproperSimplifiableFraction()
    {
        $q = Rational::fromFraction(-18, 8);
        $this->assertEquals(-2, $q->getWholePart());
        $this->assertEquals([-1, 4], $q->getFractionPart());
    }

    public function testNegativeDenominatorIsNotAllowed()
    {
        $q = Rational::fromFraction(8, -3);
        $this->assertEquals(-2, $q->getWholePart());
        $this->assertEquals([-2, 3], $q->getFractionPart());
    }

    public function testCreateFromWholeAndFractionWithDisagreeingSigns1()
    {
        $q = Rational::fromWholeAndFraction(-2, 2, 3);
        $this->assertEquals(-1, $q->getWholePart());
        $this->assertEquals([-1, 3], $q->getFractionPart());
    }

    public function testCreateFromWholeAndFractionWithDisagreeingSigns2()
    {
        $q = Rational::fromWholeAndFraction(3, -5, 8);
        $this->assertEquals(2, $q->getWholePart());
        $this->assertEquals([3, 8], $q->getFractionPart());
    }

    public function testExtend()
    {
        //Define a class that extends Rational
        $s = $this->getMockBuilder(Rational::class)->onlyMethods([])->disableOriginalConstructor()->getMock();

        //Use one of the static methods to get an instance of that class
        $c = $s::one();

        //Do an operation on that class that extends Rational
        $one = Rational::one();
        $result = $c->add($one);

        //Check that the result is the same type as the initial object
        $this->assertInstanceOf(Rational::class, $result);
        $this->assertEquals(get_class($c), get_class($result));
        $this->assertNotEquals(get_class($one), get_class($result));
    }
}
