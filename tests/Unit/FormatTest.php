<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class FormatTest extends TestCase
{
    public function testFormat1()
    {
        $r = Rational::fromWhole(5);
        $this->assertSame('5', $r->format(3));
    }

    public function testFormat2()
    {
        $r = Rational::fromWhole(-15);
        $this->assertSame('-15', $r->format(0));
    }

    public function testFormat3()
    {
        $r = Rational::fromWhole(1234567);
        $this->assertSame('1,234,567', $r->format(0));
    }

    public function testFormat4()
    {
        $r = Rational::fromWhole(-1234567);
        $this->assertSame('-1,234,567', $r->format(0));
    }

    public function testFormat5()
    {
        $r = Rational::fromWhole(1234567);
        $this->assertSame('1 234 567', $r->format(0, '.', ' '));
    }

    public function testFormat6()
    {
        $r = Rational::fromWholeAndFraction(0, 2, 3);
        $this->assertSame('1', $r->format(0));
    }

    public function testFormat7()
    {
        $r = Rational::fromWholeAndFraction(0, -2, 3);
        $this->assertSame('-1', $r->format(0));
    }

    public function testFormat8()
    {
        $r = Rational::fromWholeAndFraction(0, 2, 3);
        $this->assertSame('0.7', $r->format(1));
    }

    public function testFormat9()
    {
        $r = Rational::fromWholeAndFraction(0, 2, 3);
        $this->assertSame('0.6666667', $r->format(7));
    }

    public function testFormat10()
    {
        $r = Rational::fromWholeAndFraction(0, -2, 3);
        $this->assertSame('-0.6666667', $r->format(7));
    }

    public function testFormat11()
    {
        $r = Rational::fromWholeAndFraction(1234567, 2, 3);
        $this->assertSame('1,234,567.6666667', $r->format(7));
    }

    public function testFormat12()
    {
        $r = Rational::fromWholeAndFraction(1234567, 2, 3);
        $this->assertSame('1,234,568', $r->format(0));
    }

    public function testFormat13()
    {
        $r = Rational::fromWholeAndFraction(-1234567, -2, 3);
        $this->assertSame('-1,234,567.6666667', $r->format(7));
    }

    public function testFormat14()
    {
        $r = Rational::fromWholeAndFraction(-1234567, -2, 3);
        $this->assertSame('-1,234,568', $r->format(0));
    }
}
