<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class OperationsTest extends TestCase
{
    public function testAdd1()
    {
        $a = Rational::fromWholeAndFraction(2, 1, 4);
        $b = Rational::fromWholeAndFraction(7, 3, 4);
        $c = $a->add($b);
        $this->assertEquals(10, $c->getWholePart());
        $this->assertEquals([0, 1], $c->getFractionPart());
    }

    public function testAdd2()
    {
        $a = Rational::fromWholeAndFraction(-2, -1, 4);
        $b = Rational::fromWholeAndFraction(-7, -3, 4);
        $c = $a->add($b);
        $this->assertEquals(-10, $c->getWholePart());
        $this->assertEquals([0, 1], $c->getFractionPart());
    }

    public function testAdd3()
    {
        $a = Rational::fromWholeAndFraction(-2, -1, 4);
        $b = Rational::fromWholeAndFraction(7, 3, 5);
        $c = $a->add($b);
        $this->assertEquals(5, $c->getWholePart());
        $this->assertEquals([7, 20], $c->getFractionPart());
    }

    public function testSubtract1()
    {
        $a = Rational::fromWholeAndFraction(2, 3, 4);
        $b = Rational::fromWholeAndFraction(7, 1, 4);
        $c = $a->sub($b);
        $this->assertEquals(-4, $c->getWholePart());
        $this->assertEquals([-1, 2], $c->getFractionPart());
    }

    public function testMul1()
    {
        $a = Rational::fromWholeAndFraction(2, 3, 4);
        $b = Rational::fromWholeAndFraction(7, 1, 4);
        $c = $a->mul($b);
        $this->assertEquals(19, $c->getWholePart());
        $this->assertEquals([15, 16], $c->getFractionPart());
    }

    public function testMul2()
    {
        $a = Rational::fromWholeAndFraction(-5, -3, 7);
        $b = Rational::fromWholeAndFraction(7, 1, 4);
        $c = $a->mul($b);
        $this->assertEquals(-39, $c->getWholePart());
        $this->assertEquals([-5, 14], $c->getFractionPart());
    }

    public function testMul3()
    {
        $a = Rational::fromWholeAndFraction(-5, -3, 7);
        $b = Rational::fromWholeAndFraction(-17, -9, 10);
        $c = $a->mul($b);
        $this->assertEquals(97, $c->getWholePart());
        $this->assertEquals([6, 35], $c->getFractionPart());
    }

    public function testAddOverflow1()
    {
        $a = Rational::fromWholeAndFraction(9000000000000000000, 15398197, 25526789);
        $b = Rational::fromWholeAndFraction(1000000000000000000, 42489019, 47777057);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Overflow error: value 10000000000000000001 is too large to be represented by a PHP integer');

        $a->add($b);
    }

    public function testAddOverflow2()
    {
        $a = Rational::fromWholeAndFraction(2, 1000000000000000000, 4087722194471772533);
        $b = Rational::fromWholeAndFraction(3, 1000000000000000000, 6615500653910192833);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Overflow error: value 10703222848381965366000000000000000000 is too large to be represented by a PHP integer');

        $a->add($b);
    }

    public function testAddOverflow3()
    {
        $a = Rational::fromWholeAndFraction(2, 1, 752128792922579);
        $b = Rational::fromWholeAndFraction(3, 1, 167426936962477);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Overflow error: value 125926620000312551413689068183 is too large to be represented by a PHP integer');

        $a->add($b);
    }

    public function testAddNoOverflowOnLargeIntermediateResult()
    {
        $a = Rational::fromWholeAndFraction(2, 900000000, 956746069);
        $b = Rational::fromWholeAndFraction(3, 9000000000, 9595665337);
        $c = $a->add($b);
        $this->assertEquals(6, $c->getWholePart());
        $this->assertEquals([8066198333685689747, 9180615090614310253], $c->getFractionPart());
    }
}
