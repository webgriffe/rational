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

    public function testAbs1()
    {
        $a = Rational::fromWhole(3);
        $b = $a->abs();
        $this->assertEquals(3, $b->getWholePart());
        $this->assertEquals([0, 1], $b->getFractionPart());
    }

    public function testAbs2()
    {
        $a = Rational::fromWhole(-17);
        $b = $a->abs();
        $this->assertEquals(17, $b->getWholePart());
        $this->assertEquals([0, 1], $b->getFractionPart());
    }

    public function testAbs3()
    {
        $a = Rational::fromWholeAndFraction(19, 5, 7);
        $b = $a->abs();
        $this->assertEquals(19, $b->getWholePart());
        $this->assertEquals([5, 7], $b->getFractionPart());
    }

    public function testAbs4()
    {
        $a = Rational::fromWholeAndFraction(-23, -3, 11);
        $b = $a->abs();
        $this->assertEquals(23, $b->getWholePart());
        $this->assertEquals([3, 11], $b->getFractionPart());
    }

    public function testChainOfOperations()
    {
        //Creates a zero value
        $r0 = Rational::zero();

        //Creates a one value
        $r1 = Rational::one();

        //Creates a whole number
        $r2 = Rational::fromWhole(-2);

        //Creates a variable that stores exactly ⅔ (two thirds), roughly 0.666666...
        $r3 = Rational::fromFraction(2, 3);

        //Creates a variable that stores exactly 4 + ⅑ (one ninth), roughly 7.111111...
        $r4 = Rational::fromWholeAndFraction(4, 1, 9);

        //Adds $r1 and $r2 so that $r5 equals -1
        $r5 = $r1->add($r2);
        $this->assertTrue($r5->isWhole());
        $this->assertFalse($r5->isPositive());
        $this->assertFalse($r5->isZeroOrPositive());
        $this->assertTrue($r5->isNegative());
        $this->assertTrue($r5->isZeroOrNegative());
        $this->assertFalse($r5->isZero());
        $this->assertEquals(-1, $r5->getWholePart());
        $this->assertEquals([0, 1], $r5->getFractionPart());

        //Adds $r3 to $r5: -1 + ⅔ = -⅓
        $r6 = $r5->add($r3);
        $this->assertFalse($r6->isWhole());
        $this->assertFalse($r6->isPositive());
        $this->assertFalse($r6->isZeroOrPositive());
        $this->assertTrue($r6->isNegative());
        $this->assertTrue($r6->isZeroOrNegative());
        $this->assertFalse($r6->isZero());
        $this->assertEquals(0, $r6->getWholePart());
        $this->assertEquals([-1, 3], $r6->getFractionPart());

        //Subtracts $r6 from $r2: -2 - (-⅓) = -2 + ⅓ = -1 - ⅔
        $r7 = $r2->sub($r6);
        $this->assertFalse($r7->isWhole());
        $this->assertFalse($r7->isPositive());
        $this->assertFalse($r7->isZeroOrPositive());
        $this->assertTrue($r7->isNegative());
        $this->assertTrue($r7->isZeroOrNegative());
        $this->assertFalse($r7->isZero());
        $this->assertEquals(-1, $r7->getWholePart());
        $this->assertEquals([-2, 3], $r7->getFractionPart());

        //Multiply $r7 by $r4: (-1 - ⅔) * (4 + ⅑)
        //= -4 - 1/9 - 8/3 - 2/27
        //= -4 - 3/27 - 72/27 - 2/27
        //= -4 - 77/27
        //= -4 - 2 - 23/27
        //= -6 - 23/27
        $r8 = $r7->mul($r4);
        $this->assertFalse($r8->isWhole());
        $this->assertFalse($r8->isPositive());
        $this->assertFalse($r8->isZeroOrPositive());
        $this->assertTrue($r8->isNegative());
        $this->assertTrue($r8->isZeroOrNegative());
        $this->assertFalse($r8->isZero());
        $this->assertEquals(-6, $r8->getWholePart());
        $this->assertEquals([-23, 27], $r8->getFractionPart());

        //Divide $r8 by $r3: (-6 - 23/27) / (2/3)
        //= (-6 - 23/27) * (3/2)
        //= -9 - 23/18
        //= -9 - 1 - 5/18
        //= -10 - 5/18
        $r9 = $r8->div($r3);
        $this->assertFalse($r9->isWhole());
        $this->assertFalse($r9->isPositive());
        $this->assertFalse($r9->isZeroOrPositive());
        $this->assertTrue($r9->isNegative());
        $this->assertTrue($r9->isZeroOrNegative());
        $this->assertFalse($r9->isZero());
        $this->assertEquals(-10, $r9->getWholePart());
        $this->assertEquals([-5, 18], $r9->getFractionPart());

        //Compute the reciprocal of $r9: 1/(-10 - 5/18)
        //= 1/((-180 - 5)/18)
        //= 1/(-185/18)
        //= 18/-185
        //= -18/185
        $r10 = $r9->recip();
        $this->assertFalse($r10->isWhole());
        $this->assertFalse($r10->isPositive());
        $this->assertFalse($r10->isZeroOrPositive());
        $this->assertTrue($r10->isNegative());
        $this->assertTrue($r10->isZeroOrNegative());
        $this->assertFalse($r10->isZero());
        $this->assertEquals(0, $r10->getWholePart());
        $this->assertEquals([-18, 185], $r10->getFractionPart());

        //$r11 = $r10 + $r1: -18/185 + 1
        //= -18/185 + 185/185
        //= 167/185
        $r11 = $r10->add($r1);
        $this->assertFalse($r11->isWhole());
        $this->assertTrue($r11->isPositive());
        $this->assertTrue($r11->isZeroOrPositive());
        $this->assertFalse($r11->isNegative());
        $this->assertFalse($r11->isZeroOrNegative());
        $this->assertFalse($r11->isZero());
        $this->assertEquals(0, $r11->getWholePart());
        $this->assertEquals([167, 185], $r11->getFractionPart());

        //$r12 = $r11 - $r10: 167/185 - (-18/185)
        //= 167/185 + 18/185
        //= 185/185
        //= 1
        $r12 = $r11->sub($r10);
        $this->assertTrue($r12->isWhole());
        $this->assertTrue($r12->isPositive());
        $this->assertTrue($r12->isZeroOrPositive());
        $this->assertFalse($r12->isNegative());
        $this->assertFalse($r12->isZeroOrNegative());
        $this->assertFalse($r12->isZero());
        $this->assertEquals(1, $r12->getWholePart());
        $this->assertEquals([0, 1], $r12->getFractionPart());
        $this->assertTrue($r12->equals($r1));
        $this->assertTrue($r1->equals($r12));

        //$r13 = $r12 - $r1: 1 - 1 = 0
        $r13 = $r12->sub($r1);
        $this->assertTrue($r13->isWhole());
        $this->assertFalse($r13->isPositive());
        $this->assertTrue($r13->isZeroOrPositive());
        $this->assertFalse($r13->isNegative());
        $this->assertTrue($r13->isZeroOrNegative());
        $this->assertTrue($r13->isZero());
        $this->assertEquals(0, $r13->getWholePart());
        $this->assertEquals([0, 1], $r13->getFractionPart());
        $this->assertTrue($r13->equals($r0));
        $this->assertTrue($r0->equals($r13));
    }
}
