<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

class ReadmeTest extends TestCase
{
    public function testReadmeExample()
    {
        $r1 = Rational::one();

        $r2 = Rational::fromWhole(-2);

        $r3 = Rational::fromFraction(2, 3);

        $r4 = Rational::fromWholeAndFraction(4, 1, 9);

        $r5 = $r1->add($r2);

        $r6 = $r5->add($r3);

        $r7 = $r2->sub($r6);

        $r8 = $r7->mul($r4);

        $r9 = $r8->div($r3);

        $r10 = $r9->recip();

        $r11 = $r10->add($r1);

        $this->assertEquals('0.903', $r11->toDecimalString(3));

        $this->assertEquals('0.90', $r11->round(2)->toDecimalString(2, 2));

        $r12 = $r11->sub($r10);

        $this->assertTrue($r12->equals(Rational::one()));
    }
}
